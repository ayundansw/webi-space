<?php

namespace App\Services\Webi;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Orchestrates one reactive chat turn (spesifikasi-webi.md 3.1, Mode A):
 * conversation/session handling, message persistence, Gemini call,
 * guardrail validation, personalization context injection, and
 * proactive-greeting reuse of activeConversationFor()/history().
 *
 * [EVALUATION_BANK] scoping reads ONLY current_unit_id, not the full
 * USER_CONTEXT block — the guardrail can't function without the current
 * unit, the rest of personalization is injected separately.
 */
class ChatService
{
    public function __construct(
        private readonly GeminiClient $gemini,
        private readonly SystemPromptBuilder $promptBuilder,
        private readonly EvaluationBankBuilder $bankBuilder,
        private readonly GuardrailService $guardrail,
        private readonly PersonalizationContextBuilder $personalization,
        private readonly CurriculumContextBuilder $curriculumContext,
    ) {}

    /**
     * A new session starts when the gap since the last message exceeds the
     * configured timeout (docs/v_2.0/archive/sumber-konsolidasi/spesifikasi-webi.md 2.1) — otherwise the user
     * keeps talking in their existing conversation.
     */
    public function activeConversationFor(User $user): Conversation
    {
        $latest = Conversation::where('user_id', $user->id)->orderByDesc('started_at')->first();

        $timeoutMinutes = config('webi.session_timeout_minutes');

        if ($latest && $latest->last_message_at && $latest->last_message_at->diffInMinutes(Carbon::now()) < $timeoutMinutes) {
            return $latest;
        }

        return Conversation::create([
            'user_id' => $user->id,
            'started_at' => now(),
            'last_message_at' => now(),
        ]);
    }

    /**
     * Redesign halaman WEBI Chat penuh (sidebar "Percakapan Baru"):
     * activeConversationFor() di atas SENGAJA reuse sesi yang masih dalam
     * window waktu aktif -- itu perilaku yang benar untuk "lanjutkan
     * ngobrol", tapi salah untuk tombol yang eksplisit dilabeli "Baru".
     * Method ini SELALU membuat Conversation baru, tidak pernah reuse --
     * satu-satunya bedanya dari activeConversationFor() adalah tidak ada
     * pengecekan sesi lama sama sekali.
     */
    public function startNewConversationFor(User $user): Conversation
    {
        return Conversation::create([
            'user_id' => $user->id,
            'started_at' => now(),
            'last_message_at' => now(),
        ]);
    }

    /**
     * 2.2.3 (riwayat percakapan): a user's past sessions, newest first, each
     * with its messages eager-loaded so callers can build a lightweight
     * preview (first message, count) without N+1 — safe at personal scale
     * (one user's own conversation count), unlike the admin-wide monitoring
     * queries elsewhere which page through many users at once.
     *
     * @return Collection<int, Conversation>
     */
    public function pastConversationsFor(User $user, ?string $excludeConversationId = null): Collection
    {
        return Conversation::where('user_id', $user->id)
            ->when($excludeConversationId, fn ($query) => $query->where('id', '!=', $excludeConversationId))
            ->with(['messages' => fn ($query) => $query->orderBy('created_at')])
            ->orderByDesc('started_at')
            ->get();
    }

    /**
     * @return array<int, array{role: string, text: string}>
     */
    public function historyFor(Conversation $conversation): array
    {
        $limit = config('webi.context_window_messages');

        return $conversation->messages()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn (Message $message) => [
                'role' => $message->sender === 'webi' ? 'model' : 'user',
                'text' => $message->content,
            ])
            ->values()
            ->all();
    }

    /**
     * docs/v_2.0/archive/sumber-konsolidasi/PRD.md 5.10 / docs/v_2.0/archive/sumber-konsolidasi/spesifikasi-webi.md 5.2: max messages per user per
     * day, counted across all of the user's conversations (not just the active one).
     */
    public function messagesSentToday(User $user): int
    {
        return Message::whereHas('conversation', fn ($q) => $q->where('user_id', $user->id))
            ->where('sender', 'user')
            ->whereDate('created_at', Carbon::today())
            ->count();
    }

    /**
     * @param  ?Unit  $contextUnit  2.2.3 (WEBI kontekstual): explicit unit the
     *      message was sent about — passed when Chat is embedded in a slide-over
     *      on a unit page (App\Livewire\Eksplorasi\UnitShow), so WEBI discusses
     *      THAT unit regardless of what the user's global "current unit"
     *      (UserExplorationProgress.current_unit_id) happens to be. Left null
     *      for the default full-chat-page path, which keeps its exact prior
     *      behavior: falls back to $user->explorationProgress?->currentUnit.
     *
     * @throws RateLimitExceededException
     * @throws GeminiApiException
     */
    public function sendMessage(User $user, Conversation $conversation, string $text, bool $voiceMode = false, ?Unit $contextUnit = null): Message
    {
        $limit = config('webi.daily_message_limit');

        if ($this->messagesSentToday($user) >= $limit) {
            throw new RateLimitExceededException(
                "Kamu sudah kirim {$limit} pesan ke WEBI hari ini, batas hariannya sampai situ dulu ya. Lanjut lagi besok!",
            );
        }

        $history = $this->historyFor($conversation);
        $currentUnit = $contextUnit ?? $user->explorationProgress?->currentUnit;
        $evaluationBank = $this->bankBuilder->build($currentUnit);

        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'user',
            'content' => $text,
            'unit_context' => $currentUnit?->id,
            'voice_mode' => $voiceMode,
        ]);
        $conversation->touch();

        if ($evaluationBank->isNotEmpty()) {
            $inputMatch = $this->guardrail->checkEvalDetectionInput($text, $evaluationBank);

            if ($inputMatch) {
                $this->guardrail->logFlag($userMessage, 'eval_detection', $inputMatch['unit_id'], [
                    'matched_question' => $inputMatch['soal'],
                    'similarity' => $inputMatch['similarity'],
                ]);
            }
        }

        $userContextBlock = $this->personalization->build($user, $voiceMode);
        $curriculumContextBlock = $this->curriculumContext->build($currentUnit, $text);

        $systemPrompt = $this->promptBuilder->build($evaluationBank, $userContextBlock, $curriculumContextBlock, $voiceMode);
        $replyText = $this->stripInternalArtifacts($this->gemini->generate($systemPrompt, $history, $text));

        $outputMatch = $evaluationBank->isNotEmpty()
            ? $this->guardrail->checkOutputAgainstAnswers($replyText, $evaluationBank)
            : null;

        if ($outputMatch) {
            // Retry once with an explicit correction instruction, per
            // docs/v_2.0/archive/sumber-konsolidasi/spesifikasi-webi.md 5.2.
            $retryPrompt = $systemPrompt."\n\nRespons sebelumnya terdeteksi mengandung jawaban evaluasi. Ulangi tanpa memberikan jawaban langsung.";
            $retryReplyText = $this->stripInternalArtifacts($this->gemini->generate($retryPrompt, $history, $text));

            $stillFlagged = $this->guardrail->checkOutputAgainstAnswers($retryReplyText, $evaluationBank);

            // A retry that still leaks the answer must never reach the user —
            // confirmed with the user (2026-07-04) as a required fix, not
            // "best effort" like the original Batch 3 implementation.
            $replyText = $stillFlagged ? $this->guardrail->genericRefusalMessage() : $retryReplyText;

            $webiMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sender' => 'webi',
                'content' => $replyText,
                'unit_context' => $currentUnit?->id,
                'voice_mode' => $voiceMode,
            ]);

            $this->guardrail->logFlag($webiMessage, 'output_validation', $outputMatch['unit_id'], [
                'similarity' => $outputMatch['similarity'],
                'retried' => true,
                'still_flagged_after_retry' => (bool) $stillFlagged,
                'generic_refusal_override_applied' => (bool) $stillFlagged,
            ]);
        } else {
            $webiMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sender' => 'webi',
                'content' => $replyText,
                'unit_context' => $currentUnit?->id,
                'voice_mode' => $voiceMode,
            ]);
        }

        $domainRejection = $this->guardrail->checkDomainRejection($replyText);

        if ($domainRejection) {
            $this->guardrail->logFlag($webiMessage, 'domain_rejection', null, [
                'category' => $domainRejection,
            ]);
        }

        $conversation->touch();

        return $webiMessage;
    }

    /**
     * Defense-in-depth backstop (2026-07-04): SystemPromptBuilder no longer
     * writes a literal "[VOICE_MODE=true]"-style tag into the prompt (that
     * was the actual bug — the model would echo it back verbatim since it
     * reads exactly like the other structural markers used elsewhere in the
     * prompt, e.g. [EVALUATION_BANK]). This strips any residual instance
     * anyway, the same way GuardrailService is a second line of defense
     * behind the system-prompt instructions rather than the only one.
     */
    private function stripInternalArtifacts(string $text): string
    {
        return trim(preg_replace('/\[VOICE_MODE[^\]]*\]/i', '', $text));
    }
}

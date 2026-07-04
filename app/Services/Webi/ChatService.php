<?php

namespace App\Services\Webi;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Orchestrates one reactive chat turn (docs/spesifikasi-webi.md 3.1, Mode A).
 * Extended across task 2.5's batches:
 * - Batch 2: conversation/session handling, message persistence, plain Gemini call.
 * - Batch 3: guardrail (system prompt tier + backend output validation + rate limit).
 * - Batch 4: personalization context injection.
 * - Batch 5: proactive greetings reuse activeConversationFor()/history().
 *
 * Batch 3 note: [EVALUATION_BANK] scoping needs to know the user's current unit,
 * which is technically progress/personalization data (Batch 4's territory) —
 * but the guardrail can't function without it, so this batch reads ONLY
 * current_unit_id (not the full USER_CONTEXT block: level, points, interest_field,
 * completed_units) to scope which evaluation questions to inject. Batch 4 adds
 * the rest. Flagged as a sequencing note in the task 2.5 report.
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
     * configured timeout (docs/spesifikasi-webi.md 2.1) — otherwise the user
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
     * docs/PRD.md 5.10 / docs/spesifikasi-webi.md 5.2: max messages per user per
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
     * @throws RateLimitExceededException
     * @throws GeminiApiException
     */
    public function sendMessage(User $user, Conversation $conversation, string $text, bool $voiceMode = false): Message
    {
        $limit = config('webi.daily_message_limit');

        if ($this->messagesSentToday($user) >= $limit) {
            throw new RateLimitExceededException(
                "Kamu sudah kirim {$limit} pesan ke WEBI hari ini, batas hariannya sampai situ dulu ya. Lanjut lagi besok!",
            );
        }

        $history = $this->historyFor($conversation);
        $currentUnit = $user->explorationProgress?->currentUnit;
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
            // docs/spesifikasi-webi.md 5.2.
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

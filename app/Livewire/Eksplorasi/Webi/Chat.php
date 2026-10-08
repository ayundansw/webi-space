<?php

namespace App\Livewire\Eksplorasi\Webi;

use App\Models\Conversation;
use App\Models\Unit;
use App\Services\Webi\ChatService;
use App\Services\Webi\GeminiApiException;
use App\Services\Webi\MessageRenderer;
use App\Services\Webi\ProactiveService;
use App\Services\Webi\RateLimitExceededException;
use App\Services\Webi\RecommendationParser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Chat WEBI')]
class Chat extends Component
{
    public Conversation $conversation;

    public string $messageText = '';

    public ?string $errorMessage = null;

    /**
     * Set from the client only when the browser actually supports the Web
     * Speech API and the user turned the mic on (docs/v_2.0/archive/sumber-konsolidasi/spesifikasi-webi.md 6.3:
     * voice mode is opt-in, user-selected, and can be toggled mid-conversation).
     * Never assume true — the JS side is the only thing that can know browser
     * support, per the mandatory fallback rule.
     */
    public bool $voiceMode = false;

    public ?string $lastReplyText = null;

    /**
     * 2.2.3 (riwayat percakapan): true only when this conversation was opened
     * explicitly via history (an older, already-timed-out session) rather
     * than the default active-session path. Purely a display flag — the
     * "kamu sedang melihat percakapan lama" banner + back-to-active link —
     * sendMessage() behaves identically either way, and proactive greetings
     * are deliberately skipped here (see mount()) since injecting a fresh
     * nudge into a dormant old thread the user is just reading back through
     * would be a confusing side effect, not something docs/v_2.0/archive/sumber-konsolidasi/spesifikasi-webi.md
     * 3.2 asks for.
     */
    public bool $isHistoryView = false;

    /**
     * 2.2.3 (WEBI kontekstual): set only when this component is embedded as
     * the slide-over panel on a unit page (App\Livewire\Eksplorasi\UnitShow),
     * mounted with an explicit unit rather than route-bound. Passed straight
     * through to ChatService::sendMessage() so WEBI discusses THIS unit —
     * see that method's docblock for why this is more accurate than falling
     * back to the user's global "current unit". Null on the default full
     * chat page, which preserves that path's exact prior behavior.
     */
    public ?Unit $contextUnit = null;

    public function mount(ChatService $service, ProactiveService $proactive, ?Conversation $conversation = null, ?Unit $contextUnit = null): void
    {
        $this->contextUnit = $contextUnit;

        if ($conversation) {
            abort_unless($conversation->user_id === Auth::id(), 403);

            $this->conversation = $conversation;
            $this->isHistoryView = true;

            return;
        }

        $this->conversation = $service->activeConversationFor(Auth::user());

        // Same reasoning as the isHistoryView branch above: a proactive
        // nudge/greeting popping into a small contextual panel the user
        // opened just to ask about the unit they're reading would be a
        // confusing non-sequitur, not something docs/v_2.0/archive/sumber-konsolidasi/spesifikasi-webi.md 3.2
        // asks for. Proactive greetings stay exclusive to the default
        // full-chat-page path.
        if (! $contextUnit) {
            $proactive->checkAndDeliver(Auth::user(), $this->conversation);
        }

        $this->conversation->refresh();
    }

    /**
     * Redesign halaman WEBI Chat penuh: tombol "Percakapan Baru" di
     * sidebar. Sebelumnya cuma link ke /eksplorasi/webi (reuse sesi aktif
     * kalau masih dalam window waktu -- tidak selalu benar-benar baru,
     * dikonfirmasi ke Aye sebagai perilaku sesi berbasis waktu yang sudah
     * ada). Aye eksplisit minta ini SELALU mengarah ke sesi baru & bersih
     * -- pakai ChatService::startNewConversationFor() (SELALU create, tidak
     * pernah reuse), lalu redirect ke /eksplorasi/webi TANPA id supaya
     * tidak memicu $isHistoryView (yang cuma benar untuk sesi LAMA, bukan
     * yang baru saja dibuat) -- begitu redirect, activeConversationFor()
     * akan menemukan Conversation yang baru dibuat ini sebagai sesi aktif
     * (last_message_at = now(), masih dalam window), jadi kosong & bersih.
     */
    public function newConversation(ChatService $service): void
    {
        $service->startNewConversationFor(Auth::user());

        $this->redirect(url('/eksplorasi/webi'), navigate: false);
    }

    public function sendMessage(ChatService $service, MessageRenderer $renderer, RecommendationParser $recommendationParser): void
    {
        $this->errorMessage = null;

        $validated = $this->validate([
            'messageText' => ['required', 'string', 'max:4000'],
        ]);

        try {
            $reply = $service->sendMessage(Auth::user(), $this->conversation, $validated['messageText'], $this->voiceMode, $this->contextUnit);

            if ($this->voiceMode) {
                // Strip the recommendation tag (if any) before stripping
                // markdown — TTS must never read out "REKOMENDASI_UNIT" or
                // "asterisk asterisk" (bug fixed 2026-07-04). The client's
                // webiVoice().speak() also strips markdown defensively, same
                // two-layer pattern used everywhere else in this module.
                $cleanText = $recommendationParser->parse($reply->content)['text'];
                $spokenText = $renderer->toPlainText($cleanText);
                $this->lastReplyText = $spokenText;
                $this->dispatch('webi-reply-ready', text: $spokenText);
            }
        } catch (GeminiApiException|RateLimitExceededException $e) {
            $this->errorMessage = $e->userMessage;
        }

        // Reset regardless of success/failure once validation has passed — a
        // failed send still counts as "submitted" from the user's point of
        // view (docs/v_2.0/archive/sumber-konsolidasi/spesifikasi-webi.md doesn't address this, but leaving a
        // failed message sitting in the box invites an accidental double-send
        // if the user doesn't notice the error banner and hits Kirim again).
        // Also dispatched as a browser event: Livewire's morph skips updating
        // a focused input's DOM value on its own, so the visible field doesn't
        // actually clear just from resetting the server-side property while
        // the input still has focus (which it normally does right after Kirim).
        $this->reset('messageText');
        $this->dispatch('webi-message-sent');

        $this->conversation->refresh();
    }

    public function render(MessageRenderer $renderer, RecommendationParser $recommendationParser, ChatService $service)
    {
        $messages = $this->conversation->messages()->orderBy('created_at')->get()
            ->map(function ($message) use ($renderer, $recommendationParser) {
                $parsed = $recommendationParser->parse($message->content);

                return (object) [
                    'model' => $message,
                    'safeHtml' => $renderer->toSafeHtml($parsed['text']),
                    'recommendedUnit' => $parsed['unit'],
                    'recommendedModule' => $parsed['module'],
                ];
            });

        // Panel kontekstual (dropdown "Riwayat"): kecualikan percakapan aktif
        // seperti semula, tidak diubah -- daftarnya memang cuma dimaksudkan
        // untuk sesi LAIN. Halaman chat penuh (redesign sidebar): percakapan
        // aktif SENGAJA DIIKUTSERTAKAN supaya bisa ditandai/di-highlight di
        // sidebar (pola chatbot modern) -- tanpa ini, item yang sedang
        // dibuka tidak akan pernah muncul di listnya sama sekali untuk
        // ditandai. $contextUnit membedakan keduanya, sama seperti
        // pembeda lain di seluruh komponen ini.
        $pastConversations = $service->pastConversationsFor(Auth::user(), $this->contextUnit ? $this->conversation->id : null)
            ->map(fn (Conversation $c) => [
                'conversation' => $c,
                'preview' => Str::limit(
                    optional($c->messages->firstWhere('sender', 'user'))->content
                        ?? optional($c->messages->first())->content
                        ?? 'Belum ada pesan',
                    60
                ),
                'message_count' => $c->messages->count(),
            ]);

        return view('livewire.eksplorasi.webi.chat', [
            'messages' => $messages,
            'pastConversations' => $pastConversations,
        ]);
    }
}

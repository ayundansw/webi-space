<?php

namespace App\Livewire\Eksplorasi\Webi;

use App\Models\Conversation;
use App\Services\Webi\ChatService;
use App\Services\Webi\GeminiApiException;
use App\Services\Webi\MessageRenderer;
use App\Services\Webi\ProactiveService;
use App\Services\Webi\RateLimitExceededException;
use App\Services\Webi\RecommendationParser;
use Illuminate\Support\Facades\Auth;
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
     * Speech API and the user turned the mic on (docs/spesifikasi-webi.md 6.3:
     * voice mode is opt-in, user-selected, and can be toggled mid-conversation).
     * Never assume true — the JS side is the only thing that can know browser
     * support, per the mandatory fallback rule.
     */
    public bool $voiceMode = false;

    public ?string $lastReplyText = null;

    public function mount(ChatService $service, ProactiveService $proactive): void
    {
        $this->conversation = $service->activeConversationFor(Auth::user());
        $proactive->checkAndDeliver(Auth::user(), $this->conversation);
        $this->conversation->refresh();
    }

    public function sendMessage(ChatService $service, MessageRenderer $renderer, RecommendationParser $recommendationParser): void
    {
        $this->errorMessage = null;

        $validated = $this->validate([
            'messageText' => ['required', 'string', 'max:4000'],
        ]);

        try {
            $reply = $service->sendMessage(Auth::user(), $this->conversation, $validated['messageText'], $this->voiceMode);

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
        // view (docs/spesifikasi-webi.md doesn't address this, but leaving a
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

    public function render(MessageRenderer $renderer, RecommendationParser $recommendationParser)
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

        return view('livewire.eksplorasi.webi.chat', ['messages' => $messages]);
    }
}

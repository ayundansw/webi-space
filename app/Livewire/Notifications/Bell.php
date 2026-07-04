<?php

namespace App\Livewire\Notifications;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Task 2.6b: bell icon + unread badge, embedded in the shared app layout for
 * all three roles (each only ever sees their own `recipient_id` rows — the
 * `Notification` data itself has been correct since 2.6, this is purely the
 * missing UI surface). No websocket/broadcast infrastructure exists in this
 * stack (docs/tech-stack.md never sets one up), so "near real-time" is done
 * via a cheap `wire:poll`, not a new dependency.
 */
class Bell extends Component
{
    private const RECENT_LIMIT = 6;

    #[On('notifications-updated')]
    public function refresh(): void
    {
        // no state to recompute here — listening is enough to force a re-render
    }

    public function markAsRead(string $notificationId): void
    {
        Notification::where('id', $notificationId)
            ->where('recipient_id', Auth::id())
            ->update(['is_read' => true]);
    }

    public function render()
    {
        $recipientId = Auth::id();

        return view('livewire.notifications.bell', [
            'unreadCount' => Notification::where('recipient_id', $recipientId)->where('is_read', false)->count(),
            'recent' => Notification::where('recipient_id', $recipientId)
                ->latest()
                ->limit(self::RECENT_LIMIT)
                ->get(),
        ]);
    }
}

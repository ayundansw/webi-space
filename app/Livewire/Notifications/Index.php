<?php

namespace App\Livewire\Notifications;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Task 2.6b Part C: full notification history for the logged-in user, with
 * pagination — the Bell dropdown only ever shows a handful of recent items.
 */
#[Layout('components.layouts.app')]
#[Title('Notifikasi')]
class Index extends Component
{
    use WithPagination;

    public function markAsRead(string $notificationId): void
    {
        Notification::where('id', $notificationId)
            ->where('recipient_id', Auth::id())
            ->update(['is_read' => true]);

        $this->dispatch('notifications-updated');
    }

    public function markAllAsRead(): void
    {
        Notification::where('recipient_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $this->dispatch('notifications-updated');
    }

    public function render()
    {
        $notifications = Notification::where('recipient_id', Auth::id())
            ->latest()
            ->paginate(15);

        return view('livewire.notifications.index', ['notifications' => $notifications]);
    }
}

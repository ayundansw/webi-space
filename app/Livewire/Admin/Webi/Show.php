<?php

namespace App\Livewire\Admin\Webi;

use App\Models\Message;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Log Percakapan WEBI')]
class Show extends Component
{
    public User $user;

    public function mount(User $user): void
    {
        abort_unless($user->role === 'exploration_member', 404);

        $this->user = $user;
    }

    public function render()
    {
        $conversationIds = $this->user->conversations()->pluck('id');

        $messages = Message::whereIn('conversation_id', $conversationIds)
            ->with('guardrailFlags', 'unitContext')
            ->orderBy('created_at')
            ->get();

        return view('livewire.admin.webi.show', ['messages' => $messages]);
    }
}

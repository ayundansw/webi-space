<?php

namespace App\Livewire\Eksplorasi\Forum;

use App\Models\ForumReply;
use App\Models\ForumThread;
use App\Services\Exploration\Notifier;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public ForumThread $thread;

    public string $replyContent = '';

    public function mount(ForumThread $thread): void
    {
        $this->thread = $thread;
    }

    public function reply(Notifier $notifier): void
    {
        $this->validate([
            'replyContent' => ['required', 'string'],
        ]);

        ForumReply::create([
            'thread_id' => $this->thread->id,
            'user_id' => Auth::id(),
            'content' => $this->replyContent,
        ]);

        // PRD 3.1.8 "Balasan di thread forum yang diikuti anggota" — scoped to
        // the thread creator (the one member unambiguously "following" their
        // own thread; there's no separate thread-subscription mechanism in
        // the schema), same as TaskService::addComment()'s equivalent pattern.
        if ($this->thread->created_by !== Auth::id()) {
            $notifier->send(
                $this->thread->creator,
                'forum_reply_received',
                'Balasan baru di thread kamu',
                Auth::user()->name.' membalas thread "'.$this->thread->title.'".',
                $this->thread,
            );
        }

        $this->replyContent = '';
    }

    public function render()
    {
        return view('livewire.eksplorasi.forum.show', [
            'replies' => $this->thread->replies()->with('user')->oldest()->get(),
        ]);
    }
}

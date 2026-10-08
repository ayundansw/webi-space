<?php

namespace App\Livewire\Eksekusi\Forum;

use App\Models\ForumThread;
use App\Services\Execution\Notifier;
use App\Services\Forum\ForumService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Forum General Eksekusi ("utang Fase 7 Batch 4") thread detail + reply.
 * `App\Livewire\Eksplorasi\Forum\Show`'s structural twin — same ForumService
 * write path, same "not a real resource here" 404 guard pattern, just
 * scoped to the opposite portal/RBAC.
 */
#[Layout('components.layouts.app')]
class Show extends Component
{
    public ForumThread $thread;

    public string $replyContent = '';

    public function mount(ForumThread $thread): void
    {
        // A thread that's either Forum Proyek (project_id set) or an
        // Eksplorasi thread (portal='exploration') is not a valid resource
        // for this page — 404 rather than leaking it through here.
        abort_if($thread->project_id !== null || $thread->portal !== 'execution', 404);

        $this->thread = $thread;
    }

    public function reply(ForumService $service, Notifier $notifier): void
    {
        $this->validate([
            'replyContent' => ['required', 'string'],
        ]);

        $service->addReply($this->thread, Auth::user(), $this->replyContent);

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
        return view('livewire.eksekusi.forum.show', [
            'replies' => $this->thread->replies()->with('user')->oldest()->get(),
        ]);
    }
}

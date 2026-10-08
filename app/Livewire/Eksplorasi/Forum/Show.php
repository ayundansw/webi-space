<?php

namespace App\Livewire\Eksplorasi\Forum;

use App\Models\ForumThread;
use App\Services\Exploration\Notifier;
use App\Services\Forum\ForumService;
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
        // Fase 7 Batch 4: a thread with project_id set is a Forum Proyek
        // thread (Eksekusi-only) — not a valid resource for this
        // Eksplorasi page's route/RBAC/layout at all, so a guessed or
        // leaked project-thread id here 404s rather than rendering (this
        // model-bound route has no project-membership context to check
        // even if it wanted to allow it). Mirrors the same "not a real
        // resource here" 404 used by AttachmentDownloadController for a
        // link/text Attachment id.
        //
        // Forum General Eksekusi ("utang Fase 7") also has project_id
        // null, so `portal` is checked explicitly too now — without this,
        // a Forum General thread id would slip past the project_id check
        // above and render (wrongly) as if it were an Eksplorasi thread.
        abort_if($thread->project_id !== null || $thread->portal !== 'exploration', 404);

        $this->thread = $thread;
    }

    public function reply(ForumService $service, Notifier $notifier): void
    {
        // Fase 8 Batch 3 (§2.2.A): forum.show is now open to read-only
        // mode, so replying here needs its own explicit guard.
        abort_if(Auth::user()->isReadOnlyExploration(), 403);

        $this->validate([
            'replyContent' => ['required', 'string'],
        ]);

        $service->addReply($this->thread, Auth::user(), $this->replyContent);

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
            'isReadOnlyExploration' => Auth::user()->isReadOnlyExploration(),
        ]);
    }
}

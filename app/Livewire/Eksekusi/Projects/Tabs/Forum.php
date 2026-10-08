<?php

namespace App\Livewire\Eksekusi\Projects\Tabs;

use App\Models\Project;
use App\Services\Execution\Notifier;
use App\Services\Forum\ForumService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * "Forum Proyek" — threads scoped to this project (ForumThread.project_id).
 * Writes go through App\Services\Forum\ForumService, shared with
 * Eksplorasi\Forum\{Create,Show}. RBAC is entirely the `project.member`
 * route middleware, no mount() check.
 *
 * Thread detail + reply happens INLINE ($openThreadId, mirrors Board's
 * $openTaskId); `?thread=` is read in mount() for notification deep-links.
 * Every query also filters `portal = 'execution'` explicitly for
 * consistency, though project_id scoping already makes cross-portal leaks
 * structurally impossible here.
 */
#[Layout('components.layouts.app')]
#[Title('Forum Proyek')]
class Forum extends Component
{
    public Project $project;

    public ?string $openThreadId = null;

    public string $newThreadTitle = '';

    public string $newThreadContent = '';

    public bool $showCreateForm = false;

    public string $replyContent = '';

    public function mount(Project $project): void
    {
        $this->project = $project;

        $queryThreadId = request()->query('thread');

        if (is_string($queryThreadId) && $project->forumThreads()->where('portal', 'execution')->where('id', $queryThreadId)->exists()) {
            $this->openThreadId = $queryThreadId;
        }
    }

    public function openThread(string $threadId): void
    {
        if ($this->project->forumThreads()->where('portal', 'execution')->where('id', $threadId)->exists()) {
            $this->openThreadId = $threadId;
            $this->reset('replyContent');
        }
    }

    public function closeThread(): void
    {
        $this->openThreadId = null;
    }

    public function createThread(ForumService $service): void
    {
        $validated = $this->validate([
            'newThreadTitle' => ['required', 'string', 'max:255'],
            'newThreadContent' => ['required', 'string'],
        ]);

        $thread = $service->createThread([
            'project_id' => $this->project->id,
            'portal' => 'execution',
            'title' => $validated['newThreadTitle'],
            'content' => $validated['newThreadContent'],
        ], Auth::user());

        $this->reset(['newThreadTitle', 'newThreadContent', 'showCreateForm']);
        $this->openThreadId = $thread->id;
    }

    public function reply(ForumService $service, Notifier $notifier): void
    {
        // Scoped through $this->project->forumThreads() so a crafted
        // openThreadId pointing at a thread from a DIFFERENT project can
        // never be replied to from here — same defensive pattern as
        // Board::changeStatus() scoping through $this->project->tasks()
        // (Fase 7 Batch 1a/2a). `portal` filter added defensively too
        // (Forum General migration) — every project-scoped thread is
        // already 'execution' by construction, but this keeps the query
        // explicit rather than relying on project_id alone.
        $thread = $this->project->forumThreads()->where('portal', 'execution')->findOrFail($this->openThreadId);

        $validated = $this->validate(['replyContent' => ['required', 'string']]);

        $service->addReply($thread, Auth::user(), $validated['replyContent']);

        if ($thread->created_by !== Auth::id()) {
            $notifier->send(
                $thread->creator,
                'forum_reply_received',
                'Balasan baru di thread proyekmu',
                Auth::user()->name." membalas thread '{$thread->title}' di proyek '{$this->project->title}'.",
                $thread,
            );
        }

        $this->reset('replyContent');
    }

    public function render()
    {
        return view('livewire.eksekusi.projects.tabs.forum', [
            'threads' => $this->project->forumThreads()->where('portal', 'execution')->with('creator')->withCount('replies')->latest()->get(),
            'openThread' => $this->openThreadId
                ? $this->project->forumThreads()->where('portal', 'execution')->with(['creator', 'replies.user'])->find($this->openThreadId)
                : null,
        ]);
    }
}

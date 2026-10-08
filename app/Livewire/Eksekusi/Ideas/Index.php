<?php

namespace App\Livewire\Eksekusi\Ideas;

use App\Models\ProjectIdea;
use App\Services\Execution\ProjectIdeaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Project Ideas')]
class Index extends Component
{
    /** @var array<string, string> keyed by idea id, holds the reject reason being typed */
    public array $rejectReasons = [];

    public string $search = '';

    public string $statusFilter = '';

    /**
     * Project Idea + Project independence (2026-07-17): approve()/reject()
     * no longer have any Project side effect, so status is free to change
     * in ANY direction from ANY card (draft/approved/rejected), not just
     * draft -> approved/rejected like before. Replaces the old dedicated
     * reject()-only action AND the separate Approve page/route entirely --
     * one method for every status button on every card.
     */
    public function changeStatus(string $ideaId, string $newStatus, ProjectIdeaService $service): void
    {
        abort_unless(Auth::user()->role === 'admin', 403);
        abort_unless(in_array($newStatus, ['draft', 'approved', 'rejected'], true), 400);

        $idea = ProjectIdea::findOrFail($ideaId);

        if ($newStatus === 'rejected') {
            $reason = trim($this->rejectReasons[$ideaId] ?? '');

            if ($reason === '') {
                $this->addError('rejectReasons.'.$ideaId, 'Alasan penolakan wajib diisi.');

                return;
            }

            $service->changeStatus($idea, Auth::user(), 'rejected', $reason);
            unset($this->rejectReasons[$ideaId]);

            return;
        }

        $service->changeStatus($idea, Auth::user(), $newStatus);
    }

    public function render()
    {
        $base = ProjectIdea::with(['proposer', 'promotedToProject'])
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'));

        // A specific status picked -- one flat, already-filtered list reads
        // more clearly than splitting the same narrow result set across two
        // sections that would then mostly just echo each other.
        if ($this->statusFilter !== '') {
            return view('livewire.eksekusi.ideas.index', [
                'filteredIdeas' => (clone $base)->where('status', $this->statusFilter)->orderByDesc('created_at')->get(),
                'pendingIdeas' => null,
                'historyIdeas' => null,
            ]);
        }

        return view('livewire.eksekusi.ideas.index', [
            'filteredIdeas' => null,
            'pendingIdeas' => (clone $base)->where('status', 'draft')->orderByDesc('created_at')->get(),
            'historyIdeas' => (clone $base)->whereIn('status', ['approved', 'rejected'])->orderByDesc('created_at')->get(),
        ]);
    }
}

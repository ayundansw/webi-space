<?php

namespace App\Livewire\Eksekusi\Projects\Tabs;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Fase 7 Batch 3b: high-level timeline of this project's Milestones
 * (sort_order), each showing derived status + progress. Deliberately reuses
 * Milestone::progressPercentage() (Fase 7 Batch 2a: already excludes
 * subtasks) rather than recomputing progress here — this component only
 * adds a "status" classification (completed/active/not_started) on top of
 * data that already exists, no new persistence, Milestone CRUD untouched
 * (still lives in ProjectHeader from Batch 1a).
 */
#[Layout('components.layouts.app')]
#[Title('Roadmap Proyek')]
class Roadmap extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function render()
    {
        $milestones = $this->project->milestones()->orderBy('sort_order')->get();

        $entries = $milestones->map(fn (Milestone $milestone) => $this->milestoneEntry($milestone));

        return view('livewire.eksekusi.projects.tabs.roadmap', ['entries' => $entries]);
    }

    /**
     * @return array{milestone: Milestone, status: string, percentage: int, tasks: \Illuminate\Support\Collection}
     */
    private function milestoneEntry(Milestone $milestone): array
    {
        // Same audited exclusion as the Kanban board and progressPercentage()
        // itself (Fase 7 Batch 2a) — a subtask being in_progress shouldn't
        // make its parent's milestone look "active" on the Roadmap, that's
        // the parent task's own status to report.
        $tasks = $milestone->tasks()->whereNull('parent_task_id')->with('assignments.user')->get();

        $status = match (true) {
            $tasks->isNotEmpty() && $tasks->every(fn (Task $task) => $task->status === 'done') => 'completed',
            $tasks->contains(fn (Task $task) => in_array($task->status, ['in_progress', 'in_review'], true)) => 'active',
            default => 'not_started',
        };

        return [
            'milestone' => $milestone,
            'status' => $status,
            'percentage' => $milestone->progressPercentage(),
            'tasks' => $tasks,
        ];
    }
}

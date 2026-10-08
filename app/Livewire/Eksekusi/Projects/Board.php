<?php

namespace App\Livewire\Eksekusi\Projects;

use App\Models\Project;
use App\Services\Execution\TaskService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Kanban board is a view layer only — reads Task.status, groups into
 * columns via buttons (not drag-and-drop), same TaskService::changeStatus()
 * used by the task detail page. Routed as the "Kanban" tab.
 *
 * SECURITY: `project.member` middleware checks membership only — openTask()
 * and the `?task=` query param are the ONLY guard against opening a task
 * from another project, so both MUST validate against $this->project.
 * Task lookups also filter to task besar only; subtasks never appear here,
 * only nested inside their parent task's panel.
 */
#[Layout('components.layouts.app')]
#[Title('Kanban Board')]
class Board extends Component
{
    public Project $project;

    public ?string $openTaskId = null;

    public function mount(Project $project): void
    {
        $this->project = $project;

        $queryTaskId = request()->query('task');

        if (is_string($queryTaskId) && $project->tasks()->whereNull('parent_task_id')->where('id', $queryTaskId)->exists()) {
            $this->openTaskId = $queryTaskId;
        }
    }

    public function openTask(string $taskId): void
    {
        if ($this->project->tasks()->whereNull('parent_task_id')->where('id', $taskId)->exists()) {
            $this->openTaskId = $taskId;
        }
    }

    public function closeTaskPanel(): void
    {
        $this->openTaskId = null;
    }

    public function changeStatus(string $taskId, string $newStatus, TaskService $service): void
    {
        $task = $this->project->tasks()->whereNull('parent_task_id')->findOrFail($taskId);

        try {
            $service->changeStatus($task, $newStatus, Auth::user());
        } catch (ValidationException $e) {
            $this->addError('status', collect($e->errors())->flatten()->first());
        }
    }

    public function render()
    {
        $tasks = $this->project->tasks()->whereNull('parent_task_id')->with(['assignments.user', 'milestone'])->orderBy('deadline')->get();

        $columns = [
            'todo' => $tasks->where('status', 'todo')->values(),
            'in_progress' => $tasks->where('status', 'in_progress')->values(),
            'in_review' => $tasks->where('status', 'in_review')->values(),
            'done' => $tasks->where('status', 'done')->values(),
        ];

        return view('livewire.eksekusi.projects.board', [
            'columns' => $columns,
            'openTask' => $this->openTaskId ? $this->project->tasks()->whereNull('parent_task_id')->find($this->openTaskId) : null,
        ]);
    }
}

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
 * Kanban board is a view layer only (docs/struktur-eksekusi.md 3.6 note) — it
 * reads Task.status and groups into columns, no separate board entity. Status
 * changes here use buttons rather than drag-and-drop: more stable to build
 * and test right now (per explicit instruction), same underlying
 * TaskService::changeStatus() used by the task detail page.
 */
#[Layout('components.layouts.app')]
#[Title('Kanban Board')]
class Board extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        $user = Auth::user();

        if ($user->role !== 'admin' && ! $project->members()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        $this->project = $project;
    }

    public function changeStatus(string $taskId, string $newStatus, TaskService $service): void
    {
        $task = $this->project->tasks()->findOrFail($taskId);

        try {
            $service->changeStatus($task, $newStatus, Auth::user());
        } catch (ValidationException $e) {
            $this->addError('status', collect($e->errors())->flatten()->first());
        }
    }

    public function render()
    {
        $tasks = $this->project->tasks()->with(['assignments.user', 'milestone'])->orderBy('deadline')->get();

        $columns = [
            'todo' => $tasks->where('status', 'todo')->values(),
            'in_progress' => $tasks->where('status', 'in_progress')->values(),
            'in_review' => $tasks->where('status', 'in_review')->values(),
            'done' => $tasks->where('status', 'done')->values(),
        ];

        return view('livewire.eksekusi.projects.board', ['columns' => $columns]);
    }
}

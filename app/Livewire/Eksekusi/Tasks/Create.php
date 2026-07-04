<?php

namespace App\Livewire\Eksekusi\Tasks;

use App\Models\Milestone;
use App\Models\Project;
use App\Services\Execution\TaskService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * docs/struktur-eksekusi.md Tahap 4 says tasks are created by "Admin, atau
 * anggota eksekusi yang diberi wewenang oleh admin" — but there is no schema
 * field anywhere (ProjectMember has none) that tracks per-member authorization
 * to create tasks. Resolved: any ProjectMember of this project (plus admin)
 * can create tasks here. Flagged in the final report — a finer-grained
 * "authorized to create tasks" flag would need a schema change (out of scope
 * without explicit confirmation, per CLAUDE.md).
 */
#[Layout('components.layouts.app')]
#[Title('Buat Task Baru')]
class Create extends Component
{
    public Project $project;

    public string $milestoneId = '';

    public string $title = '';

    public string $description = '';

    public string $priority = 'medium';

    public string $deadline = '';

    /** @var array<int, string> */
    public array $assigneeIds = [];

    public function mount(Project $project): void
    {
        $user = Auth::user();

        if ($user->role !== 'admin' && ! $project->members()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        abort_if($project->status === 'archived', 403);

        $this->project = $project;
    }

    public function save(TaskService $service): void
    {
        $validated = $this->validate([
            'milestoneId' => ['required', 'uuid'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,medium,high'],
            'deadline' => ['required', 'date'],
            'assigneeIds' => ['array'],
        ]);

        $milestone = Milestone::where('project_id', $this->project->id)->findOrFail($validated['milestoneId']);

        $task = $service->create($this->project, $milestone, Auth::user(), [
            'title' => $validated['title'],
            'description' => $validated['description'] ?: null,
            'priority' => $validated['priority'],
            'deadline' => $validated['deadline'],
        ], $validated['assigneeIds']);

        $this->redirect('/eksekusi/tasks/'.$task->id, navigate: false);
    }

    public function render()
    {
        return view('livewire.eksekusi.tasks.create', [
            'milestones' => $this->project->milestones()->orderBy('sort_order')->get(),
            'members' => $this->project->members()->with('user')->get(),
        ]);
    }
}

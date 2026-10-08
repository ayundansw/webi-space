<?php

namespace App\Livewire\Eksekusi\Projects;

use App\Models\Project;
use App\Services\Execution\ProjectService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Shared header rendered above EVERY project tab — nested Livewire
 * component, absorbing project metadata display + status-change +
 * Milestone CRUD.
 *
 * No mount() membership check here — this component is never independently
 * routable, only ever embedded inside a parent tab page whose OWN route
 * already sits behind the `project.member` middleware.
 */
class ProjectHeader extends Component
{
    public Project $project;

    public string $milestoneTitle = '';

    public string $milestoneDescription = '';

    public string $milestoneTargetDate = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function addMilestone(ProjectService $service): void
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $validated = $this->validate([
            'milestoneTitle' => ['required', 'string', 'max:255'],
            'milestoneDescription' => ['nullable', 'string'],
            'milestoneTargetDate' => ['required', 'date'],
        ]);

        $service->addMilestone($this->project, Auth::user(), [
            'title' => $validated['milestoneTitle'],
            'description' => $validated['milestoneDescription'] ?: null,
            'target_date' => $validated['milestoneTargetDate'],
        ]);

        $this->reset(['milestoneTitle', 'milestoneDescription', 'milestoneTargetDate']);
        $this->project->refresh();

        // Task 3 (Tahap B, Isi Proyek): form pindah ke modal popup -- dispatch
        // ini yang dikonsumsi Alpine (x-on:milestone-added.window) untuk
        // menutup modal otomatis setelah submit sukses. TIDAK pernah terpanggil
        // di jalur validasi gagal (return di atas sebelum baris ini), jadi
        // modal tetap terbuka menampilkan error kalau validasi gagal — pola
        // yang sama dipakai Profile\Edit::changePassword()'s 'password-changed'.
        $this->dispatch('milestone-added');
    }

    public function changeStatus(string $newStatus, ProjectService $service): void
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        try {
            $service->changeStatus($this->project, $newStatus, Auth::user());
        } catch (ValidationException $e) {
            $this->addError('status', collect($e->errors())->flatten()->first());

            return;
        }

        $this->project->refresh();
    }

    public function render()
    {
        return view('livewire.eksekusi.projects.project-header', [
            'milestones' => $this->project->milestones()->orderBy('sort_order')->get(),
        ]);
    }
}

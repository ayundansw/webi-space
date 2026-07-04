<?php

namespace App\Livewire\Eksekusi\Projects;

use App\Services\Execution\ProjectService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Buat Proyek Baru')]
class Create extends Component
{
    public string $title = '';

    public string $description = '';

    public string $objective = '';

    public string $projectType = 'internal';

    public string $startDate = '';

    public string $targetEndDate = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->role === 'admin', 403);
    }

    public function save(ProjectService $service): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'objective' => ['required', 'string'],
            'projectType' => ['required', 'in:internal,competition'],
            'startDate' => ['required', 'date'],
            'targetEndDate' => ['required', 'date', 'after_or_equal:startDate'],
        ]);

        $project = $service->createDirect(Auth::user(), [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'objective' => $validated['objective'],
            'project_type' => $validated['projectType'],
            'start_date' => $validated['startDate'],
            'target_end_date' => $validated['targetEndDate'],
        ]);

        $this->redirect('/eksekusi/projects/'.$project->id, navigate: false);
    }

    public function render()
    {
        return view('livewire.eksekusi.projects.create');
    }
}

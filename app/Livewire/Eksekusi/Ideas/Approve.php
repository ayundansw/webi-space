<?php

namespace App\Livewire\Eksekusi\Ideas;

use App\Models\ProjectIdea;
use App\Services\Execution\ProjectIdeaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * docs/struktur-eksekusi.md stages idea-approval (Tahap 2) and project setup
 * (Tahap 3, project_type/dates/members/milestones) as separate steps. But
 * Project.project_type/start_date/target_end_date are NOT NULL in the schema,
 * so a Project can't be created at approval time without them. Resolved by
 * collecting the three required fields here, at approval — members and
 * milestones stay a later step (Batch 2's project setup page). Flagged in the
 * final report as a decision needing confirmation, not assumed silently final.
 */
#[Layout('components.layouts.app')]
#[Title('Approve Ide Proyek')]
class Approve extends Component
{
    public ProjectIdea $idea;

    public string $projectType = 'internal';

    public string $startDate = '';

    public string $targetEndDate = '';

    public function mount(ProjectIdea $idea): void
    {
        abort_unless(Auth::user()->role === 'admin', 403);
        abort_unless($idea->status === 'draft', 404);

        $this->idea = $idea;
    }

    public function save(ProjectIdeaService $service): void
    {
        $validated = $this->validate([
            'projectType' => ['required', 'in:internal,competition'],
            'startDate' => ['required', 'date'],
            'targetEndDate' => ['required', 'date', 'after_or_equal:startDate'],
        ]);

        $project = $service->approve($this->idea, Auth::user(), [
            'project_type' => $validated['projectType'],
            'start_date' => $validated['startDate'],
            'target_end_date' => $validated['targetEndDate'],
        ]);

        $this->redirect('/eksekusi/projects/'.$project->id, navigate: false);
    }

    public function render()
    {
        return view('livewire.eksekusi.ideas.approve');
    }
}

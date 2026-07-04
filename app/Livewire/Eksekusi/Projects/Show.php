<?php

namespace App\Livewire\Eksekusi\Projects;

use App\Models\Project;
use App\Models\User;
use App\Services\Execution\ProjectService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Detail Proyek')]
class Show extends Component
{
    public Project $project;

    public string $newMemberId = '';

    public string $milestoneTitle = '';

    public string $milestoneDescription = '';

    public string $milestoneTargetDate = '';

    public function mount(Project $project): void
    {
        $user = Auth::user();

        if ($user->role !== 'admin' && ! $project->members()->where('user_id', $user->id)->exists()) {
            abort(403);
        }

        $this->project = $project;
    }

    public function addMember(ProjectService $service): void
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $validated = $this->validate([
            'newMemberId' => ['required', 'uuid'],
        ]);

        $member = User::where('id', $validated['newMemberId'])->where('role', 'execution_member')->firstOrFail();

        if ($this->project->members()->where('user_id', $member->id)->exists()) {
            $this->addError('newMemberId', 'Anggota ini sudah ada di proyek.');

            return;
        }

        $service->addMember($this->project, $member, Auth::user());
        $this->newMemberId = '';
        $this->project->refresh();
    }

    public function removeMember(string $userId, ProjectService $service): void
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $member = User::findOrFail($userId);
        $service->removeMember($this->project, $member, Auth::user());
        $this->project->refresh();
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
        $availableMembers = User::where('role', 'execution_member')
            ->whereNotIn('id', $this->project->members()->pluck('user_id'))
            ->get();

        return view('livewire.eksekusi.projects.show', [
            'members' => $this->project->members()->with('user')->get(),
            'milestones' => $this->project->milestones()->orderBy('sort_order')->get(),
            'availableMembers' => $availableMembers,
        ]);
    }
}

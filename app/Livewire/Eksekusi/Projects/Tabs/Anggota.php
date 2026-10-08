<?php

namespace App\Livewire\Eksekusi\Projects\Tabs;

use App\Models\Project;
use App\Models\User;
use App\Services\Execution\ProjectService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Fase 7 Batch 1a: "Anggota" tab, real content (not a placeholder) — moved
 * as-is from the now-retired Projects\Show page. No mount() membership
 * check: this route sits behind the `project.member` middleware (see
 * routes/web.php).
 */
#[Layout('components.layouts.app')]
#[Title('Anggota Proyek')]
class Anggota extends Component
{
    public Project $project;

    public string $newMemberId = '';

    public function mount(Project $project): void
    {
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

    public function render()
    {
        $availableMembers = User::where('role', 'execution_member')
            ->whereNotIn('id', $this->project->members()->pluck('user_id'))
            ->get();

        return view('livewire.eksekusi.projects.tabs.anggota', [
            'members' => $this->project->members()->with('user')->get(),
            'availableMembers' => $availableMembers,
        ]);
    }
}

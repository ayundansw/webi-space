<?php

namespace App\Livewire\Eksekusi\Projects;

use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Proyek')]
class Index extends Component
{
    public function render()
    {
        $user = Auth::user();

        $query = Project::query()->orderByDesc('created_at');

        if ($user->role !== 'admin') {
            $query->whereHas('members', fn ($q) => $q->where('user_id', $user->id));
        }

        $projects = $query->get();

        return view('livewire.eksekusi.projects.index', [
            'projects' => $projects,
            'summary' => $this->summary($projects),
        ]);
    }

    /**
     * Task 2 (Tahap B, Isi Proyek): summary card above the list — same
     * $projects collection the list itself renders (already scoped to
     * admin-sees-all vs member-sees-own above), so the numbers here can
     * never drift from what's actually listed below.
     */
    private function summary(Collection $projects): array
    {
        $statuses = ['planning', 'active', 'on_hold', 'completed', 'archived'];

        return [
            'total' => $projects->count(),
            'by_status' => collect($statuses)->mapWithKeys(
                fn (string $status) => [$status => $projects->where('status', $status)->count()]
            )->all(),
            'average_progress' => $projects->isEmpty() ? 0 : (int) round($projects->avg(fn (Project $p) => $p->progressPercentage())),
        ];
    }
}

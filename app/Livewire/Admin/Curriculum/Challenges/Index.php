<?php

namespace App\Livewire\Admin\Curriculum\Challenges;

use App\Models\Challenge;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Kelola Praktik: Challenge')]
class Index extends Component
{
    public string $search = '';

    public string $levelFilter = '';

    public string $statusFilter = '';

    public function render()
    {
        $challenges = Challenge::withCount('challengeSteps')
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->levelFilter !== '', fn ($query) => $query->where('level', $this->levelFilter))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('title')
            ->get();

        return view('livewire.admin.curriculum.challenges.index', [
            'challenges' => $challenges,
        ]);
    }
}

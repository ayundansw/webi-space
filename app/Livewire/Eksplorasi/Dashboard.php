<?php

namespace App\Livewire\Eksplorasi;

use App\Services\Exploration\ProgressService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard Eksplorasi')]
class Dashboard extends Component
{
    public function render(ProgressService $progress)
    {
        $user = Auth::user();
        $userProgress = $progress->ensureProgress($user);
        $nextUnit = $progress->nextUnitFor($user);

        return view('livewire.eksplorasi.dashboard', [
            'userProgress' => $userProgress,
            'overallPercentage' => $progress->overallProgressPercentage($user),
            'nextUnit' => $nextUnit,
            'feed' => $progress->feedFor($user),
        ]);
    }
}

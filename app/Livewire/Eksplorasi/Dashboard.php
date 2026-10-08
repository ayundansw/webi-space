<?php

namespace App\Livewire\Eksplorasi;

use App\Models\Challenge;
use App\Models\ForumThread;
use App\Services\Exploration\FoxAvatarService;
use App\Services\Exploration\ProgressService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard Eksplorasi')]
class Dashboard extends Component
{
    public function render(ProgressService $progress, FoxAvatarService $foxAvatar)
    {
        $user = Auth::user();
        $userProgress = $progress->ensureProgress($user);
        $nextUnit = $progress->nextUnitFor($user);
        $leaderboard = $progress->memberLeaderboard($user);

        return view('livewire.eksplorasi.dashboard', [
            'userProgress' => $userProgress,
            'foxTier' => $foxAvatar->tierFor($user),
            'overallPercentage' => $progress->overallProgressPercentage($user),
            'nextUnit' => $nextUnit,
            'feed' => $progress->feedFor($user),
            'leaderboardTop5' => $leaderboard['top5'],
            'leaderboardSelf' => $leaderboard['self'],
            // Rancangan Final §3.1 baris 4 — preview thread terbaru LINTAS
            // seluruh Forum (bukan cuma milik user ini), murni query baru,
            // tidak menyentuh Forum\Index/Show sama sekali.
            'latestThreads' => ForumThread::with(['module', 'unit', 'creator'])
                ->latest()
                ->limit(3)
                ->get(),
            // Praktik 2: same "preview card, full list lives on its own
            // page" pattern as Forum Terbaru above — published-only, newest
            // first, murni query baru.
            'latestChallenges' => Challenge::where('status', 'published')
                ->latest()
                ->limit(3)
                ->get(),
        ]);
    }
}

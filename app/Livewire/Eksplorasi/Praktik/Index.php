<?php

namespace App\Livewire\Eksplorasi\Praktik;

use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Praktik')]
class Index extends Component
{
    #[Url]
    public string $levelFilter = '';

    public function render()
    {
        $user = Auth::user();

        $challenges = Challenge::where('status', 'published')
            ->when($this->levelFilter !== '', fn ($q) => $q->where('level', $this->levelFilter))
            ->orderBy('level')
            ->orderBy('title')
            ->get();

        $latestSubmissionByChallenge = ChallengeSubmission::where('user_id', $user->id)
            ->whereIn('challenge_id', $challenges->pluck('id'))
            ->get()
            ->groupBy('challenge_id')
            ->map(fn ($submissions) => $submissions->sortByDesc('attempt_number')->first());

        return view('livewire.eksplorasi.praktik.index', [
            'challenges' => $challenges,
            'latestSubmissionByChallenge' => $latestSubmissionByChallenge,
        ]);
    }
}

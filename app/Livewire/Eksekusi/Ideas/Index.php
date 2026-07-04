<?php

namespace App\Livewire\Eksekusi\Ideas;

use App\Models\ProjectIdea;
use App\Services\Execution\ProjectIdeaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Project Ideas')]
class Index extends Component
{
    public string $statusFilter = 'draft';

    /** @var array<string, string> keyed by idea id, holds the reject reason being typed */
    public array $rejectReasons = [];

    public function reject(string $ideaId, ProjectIdeaService $service): void
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $reason = trim($this->rejectReasons[$ideaId] ?? '');

        if ($reason === '') {
            $this->addError('rejectReasons.'.$ideaId, 'Alasan penolakan wajib diisi.');

            return;
        }

        $idea = ProjectIdea::findOrFail($ideaId);
        $service->reject($idea, Auth::user(), $reason);

        unset($this->rejectReasons[$ideaId]);
    }

    public function render()
    {
        $ideas = ProjectIdea::with('proposer')
            ->where('status', $this->statusFilter)
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.eksekusi.ideas.index', ['ideas' => $ideas]);
    }
}

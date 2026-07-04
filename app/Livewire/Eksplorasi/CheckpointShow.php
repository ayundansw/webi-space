<?php

namespace App\Livewire\Eksplorasi;

use App\Models\Checkpoint;
use App\Models\CheckpointCompletion;
use App\Services\Exploration\ProgressService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class CheckpointShow extends Component
{
    public Checkpoint $checkpoint;

    public bool $unitsDone = false;

    public ?CheckpointCompletion $completion = null;

    public array $checklistAnswers = [];

    public array $intermezoAnswers = [];

    public string $formTanggapan = '';

    public function mount(Checkpoint $checkpoint, ProgressService $progress): void
    {
        $this->checkpoint = $checkpoint;
        $user = Auth::user();

        $this->unitsDone = $progress->allUnitsCompleted($checkpoint->module, $user);

        $this->completion = CheckpointCompletion::where('user_id', $user->id)
            ->where('checkpoint_id', $checkpoint->id)
            ->first();
    }

    public function submit(ProgressService $progress): void
    {
        $this->validate([
            'formTanggapan' => ['required', 'string'],
        ]);

        foreach ($this->checkpoint->intermezo_questions as $index => $question) {
            if (empty($this->intermezoAnswers[$index])) {
                $this->addError('intermezoAnswers.'.$index, 'Yuk isi dulu, sedikit saja tidak masalah.');

                return;
            }
        }

        $completion = $progress->completeCheckpoint(Auth::user(), $this->checkpoint, [
            'checklist_answers' => $this->checklistAnswers,
            'intermezo_answers' => $this->intermezoAnswers,
            'form_tanggapan' => $this->formTanggapan,
        ]);

        $this->completion = $completion;
    }

    public function render()
    {
        return view('livewire.eksplorasi.checkpoint-show');
    }
}

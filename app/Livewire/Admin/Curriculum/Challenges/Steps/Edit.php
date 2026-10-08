<?php

namespace App\Livewire\Admin\Curriculum\Challenges\Steps;

use App\Models\Challenge;
use App\Models\ChallengeStep;
use App\Services\Content\CurriculumReorderService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Kelola Step')]
class Edit extends Component
{
    public Challenge $challenge;

    public ChallengeStep $step;

    public string $order_number = '';

    public string $title = '';

    /**
     * Snapshot of order_number as of mount() — see Units\Edit for why this
     * must stay a PUBLIC property. Steps don't move between challenges (no
     * challenge_id field on this form), so unlike Units\Edit there's only
     * ever the same-scope forward/backward case, never the cross-scope one.
     */
    public int $originalOrderNumber = 0;

    public function mount(Challenge $challenge, ChallengeStep $step): void
    {
        abort_if($step->challenge_id !== $challenge->id, 404);

        $this->challenge = $challenge;
        $this->step = $step;
        $this->order_number = (string) $step->order_number;
        $this->originalOrderNumber = $step->order_number;
        $this->title = $step->title;
    }

    public function save(CurriculumReorderService $reorder): void
    {
        $validated = $this->validate([
            'order_number' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
        ]);

        $newOrderNumber = (int) $validated['order_number'];

        DB::transaction(function () use ($validated, $reorder, $newOrderNumber) {
            $reorder->moveToPosition(ChallengeStep::class, ['challenge_id' => $this->challenge->id], $this->step->id, $this->originalOrderNumber, $newOrderNumber);
            $this->step->update($validated);
        });

        $this->originalOrderNumber = $newOrderNumber;

        session()->flash('status', 'Perubahan step disimpan.');
    }

    public function render()
    {
        return view('livewire.admin.curriculum.challenges.steps.edit');
    }
}

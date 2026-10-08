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
#[Title('Tambah Step')]
class Create extends Component
{
    public Challenge $challenge;

    public string $order_number = '';

    public string $title = '';

    public function mount(Challenge $challenge): void
    {
        $this->challenge = $challenge;
    }

    public function save(CurriculumReorderService $reorder): void
    {
        $validated = $this->validate([
            'order_number' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated, $reorder) {
            $reorder->makeRoomForNewPosition(ChallengeStep::class, ['challenge_id' => $this->challenge->id], (int) $validated['order_number']);
            $this->challenge->challengeSteps()->create([
                'title' => $validated['title'],
                'order_number' => (int) $validated['order_number'],
            ]);
        });

        session()->flash('status', 'Step baru berhasil dibuat.');

        $this->redirect('/admin/curriculum/challenges/'.$this->challenge->id.'/steps', navigate: false);
    }

    public function render()
    {
        return view('livewire.admin.curriculum.challenges.steps.create');
    }
}

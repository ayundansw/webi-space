<?php

namespace App\Livewire\Admin\Curriculum\Challenges\Steps;

use App\Models\Challenge;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Track Map Challenge')]
class Index extends Component
{
    public Challenge $challenge;

    public function mount(Challenge $challenge): void
    {
        $this->challenge = $challenge;
    }

    public function render()
    {
        return view('livewire.admin.curriculum.challenges.steps.index', [
            'steps' => $this->challenge->challengeSteps,
        ]);
    }
}

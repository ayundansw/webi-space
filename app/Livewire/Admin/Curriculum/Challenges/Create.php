<?php

namespace App\Livewire\Admin\Curriculum\Challenges;

use App\Models\Challenge;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Buat Challenge Baru')]
class Create extends Component
{
    public string $title = '';

    public string $description = '';

    public string $level = 'low';

    public string $points_reward = '';

    public string $status = 'draft';

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'level' => ['required', 'in:low,mid,high'],
            'points_reward' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published'],
        ]);

        Challenge::create($validated);

        session()->flash('status', 'Challenge baru berhasil dibuat.');

        $this->redirect('/admin/curriculum/challenges', navigate: false);
    }

    public function render()
    {
        return view('livewire.admin.curriculum.challenges.create');
    }
}

<?php

namespace App\Livewire\Eksekusi\Ideas;

use App\Services\Execution\ProjectIdeaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Usulkan Ide Proyek')]
class Create extends Component
{
    public string $title = '';

    public string $description = '';

    public string $purpose = '';

    public function save(ProjectIdeaService $service): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'purpose' => ['required', 'string'],
        ]);

        $service->propose(Auth::user(), $validated);

        $this->redirect('/eksekusi/ideas', navigate: false);
    }

    public function render()
    {
        return view('livewire.eksekusi.ideas.create');
    }
}

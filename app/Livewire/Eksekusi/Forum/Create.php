<?php

namespace App\Livewire\Eksekusi\Forum;

use App\Services\Forum\ForumService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Forum General Eksekusi ("utang Fase 7 Batch 4"). Deliberately simpler
 * than Eksplorasi's Forum\Create — no module/unit picker (Eksekusi has no
 * curriculum concept), no `target` peer/pic choice (that field is
 * Eksplorasi-only per Fase 7 Batch 4's own confirmed decision, stays null
 * here — ForumService::createThread() already defaults it to null when
 * omitted).
 */
#[Layout('components.layouts.app')]
#[Title('Buat Thread Baru')]
class Create extends Component
{
    public string $title = '';

    public string $content = '';

    public function save(ForumService $service): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        $thread = $service->createThread([
            'portal' => 'execution',
            'title' => $validated['title'],
            'content' => $validated['content'],
        ], Auth::user());

        $this->redirect('/eksekusi/forum/'.$thread->id, navigate: false);
    }

    public function render()
    {
        return view('livewire.eksekusi.forum.create');
    }
}

<?php

namespace App\Livewire\Eksplorasi\Forum;

use App\Models\ForumThread;
use App\Models\Module;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Buat Thread Baru')]
class Create extends Component
{
    public string $title = '';

    public string $content = '';

    public string $target = 'peer';

    public string $moduleId = '';

    public string $unitId = '';

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'target' => ['required', 'in:peer,pic'],
            'moduleId' => ['nullable', 'uuid'],
            'unitId' => ['nullable', 'uuid'],
        ]);

        if (empty($validated['moduleId']) && empty($validated['unitId'])) {
            $this->addError('moduleId', 'Pilih dulu modul atau unit yang mau kamu bahas.');

            return;
        }

        $thread = ForumThread::create([
            'module_id' => $validated['moduleId'] ?: null,
            'unit_id' => $validated['unitId'] ?: null,
            'created_by' => Auth::id(),
            'title' => $validated['title'],
            'content' => $validated['content'],
            'target' => $validated['target'],
        ]);

        $this->redirect('/eksplorasi/forum/'.$thread->id, navigate: false);
    }

    public function render()
    {
        return view('livewire.eksplorasi.forum.create', [
            'modules' => Module::orderBy('order_number')->with(['units' => fn ($q) => $q->orderBy('order_number')])->get(),
        ]);
    }
}

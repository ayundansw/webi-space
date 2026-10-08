<?php

namespace App\Livewire\Eksplorasi\Forum;

use App\Models\Module;
use App\Services\Forum\ForumService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Modul/unit are OPTIONAL — leave both empty for a "General" thread (not
 * tied to any module/unit). No migration needed, `module_id`/`unit_id`
 * were already nullable; only the business rule rejecting both-empty was
 * lifted.
 *
 * Standalone at /eksplorasi/forum/create AND embedded as a modal inside
 * Forum\Index (<livewire:eksplorasi.forum.create />) — one source of
 * logic, two access points, no duplicated field/validation.
 */
#[Layout('components.layouts.app')]
#[Title('Buat Thread Baru')]
class Create extends Component
{
    public string $title = '';

    public string $content = '';

    public string $target = 'peer';

    public string $moduleId = '';

    public string $unitId = '';

    /**
     * Fase 8 Batch 3: guarded even though /eksplorasi/forum/create's OWN
     * route stays closed to read-only mode (RECON_fase8_mode_ganda.md
     * poin 3) — this component is ALSO embedded as a modal inside
     * Forum\Index (see that class's docblock), which IS open to read-only
     * users now. Without this guard, a read-only user could still create
     * a thread through that modal despite never being able to reach
     * /create directly — exactly the kind of nested-write trap the recon
     * warned about.
     */
    public function save(ForumService $service): void
    {
        abort_if(Auth::user()->isReadOnlyExploration(), 403);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'target' => ['required', 'in:peer,pic'],
            'moduleId' => ['nullable', 'uuid'],
            'unitId' => ['nullable', 'uuid'],
        ]);

        $thread = $service->createThread([
            'module_id' => $validated['moduleId'] ?: null,
            'unit_id' => $validated['unitId'] ?: null,
            'portal' => 'exploration',
            'title' => $validated['title'],
            'content' => $validated['content'],
            'target' => $validated['target'],
        ], Auth::user());

        $this->redirect('/eksplorasi/forum/'.$thread->id, navigate: false);
    }

    public function render()
    {
        return view('livewire.eksplorasi.forum.create', [
            'modules' => Module::orderBy('order_number')->with(['units' => fn ($q) => $q->orderBy('order_number')])->get(),
        ]);
    }
}

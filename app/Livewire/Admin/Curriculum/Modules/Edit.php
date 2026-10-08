<?php

namespace App\Livewire\Admin\Curriculum\Modules;

use App\Models\Module;
use App\Services\Content\CurriculumReorderService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Kelola Modul')]
class Edit extends Component
{
    public Module $module;

    public string $order_number = '';

    public string $title = '';

    public string $description = '';

    public string $level_number = '';

    /**
     * Snapshot of order_number as of mount() — needed to compute the
     * forward/backward shift in save(). Must stay a PUBLIC property: Livewire
     * only round-trips public state through its snapshot between requests,
     * mount() does not re-run before save() fires.
     */
    public int $originalOrderNumber = 0;

    public function mount(Module $module): void
    {
        $this->module = $module;
        $this->order_number = (string) $module->order_number;
        $this->originalOrderNumber = $module->order_number;
        $this->title = $module->title;
        $this->description = $module->description;
        $this->level_number = (string) $module->level_number;
    }

    public function save(CurriculumReorderService $reorder): void
    {
        $validated = $this->validate([
            'order_number' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'level_number' => ['required', 'integer', 'min:1'],
        ]);

        $newOrderNumber = (int) $validated['order_number'];

        DB::transaction(function () use ($validated, $reorder, $newOrderNumber) {
            $reorder->moveToPosition(Module::class, [], $this->module->id, $this->originalOrderNumber, $newOrderNumber);
            $this->module->update($validated);
        });

        $this->originalOrderNumber = $newOrderNumber;

        session()->flash('status', 'Perubahan modul disimpan.');
    }

    public function render()
    {
        return view('livewire.admin.curriculum.modules.edit');
    }
}

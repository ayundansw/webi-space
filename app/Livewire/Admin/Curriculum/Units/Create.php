<?php

namespace App\Livewire\Admin\Curriculum\Units;

use App\Models\Module;
use App\Models\Unit;
use App\Services\Content\CurriculumReorderService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Buat Unit Baru')]
class Create extends Component
{
    public string $module_id = '';

    public string $order_number = '';

    public string $title = '';

    public string $estimated_minutes = '15';

    public string $unit_type = 'concept';

    public string $point_value = '';

    public string $evaluation_type = 'none';

    public string $prerequisite_unit_id = '';

    public function save(CurriculumReorderService $reorder): void
    {
        $validated = $this->validate([
            'module_id' => ['required', 'exists:modules,id'],
            'order_number' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'estimated_minutes' => ['required', 'integer', 'min:1'],
            'unit_type' => ['required', 'in:concept,practice'],
            'point_value' => ['required', 'integer', 'min:0'],
            'evaluation_type' => ['required', 'in:quiz_multiple_choice,quiz_matching,quiz_ordering,essay,practice,none'],
            'prerequisite_unit_id' => ['nullable', 'exists:units,id'],
        ]);

        // Legacy plain-text column, NOT NULL at the DB level — this batch is
        // metadata-only (Editor Blok Konten, Batch 2, fills real content via
        // content_blocks). Left empty rather than exposed in this form.
        $validated['content'] = '';

        // An unselected dropdown validates fine as '' (Laravel skips non-implicit
        // rules like `exists` on empty strings) but must not be persisted as '' —
        // the FK column expects a real uuid or null.
        $validated['prerequisite_unit_id'] = $validated['prerequisite_unit_id'] ?: null;

        DB::transaction(function () use ($validated, $reorder) {
            $reorder->makeRoomForNewPosition(Unit::class, ['module_id' => $validated['module_id']], (int) $validated['order_number']);
            Unit::create($validated);
        });

        session()->flash('status', 'Unit baru berhasil dibuat.');

        $this->redirect('/admin/curriculum/units', navigate: false);
    }

    public function render()
    {
        return view('livewire.admin.curriculum.units.create', [
            'modules' => Module::orderBy('order_number')->get(),
            'prerequisiteOptions' => Unit::with('module')->orderBy('module_id')->orderBy('order_number')->get(),
        ]);
    }
}

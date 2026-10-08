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
#[Title('Kelola Unit')]
class Edit extends Component
{
    public Unit $unit;

    public string $module_id = '';

    public string $order_number = '';

    public string $title = '';

    public string $estimated_minutes = '';

    public string $unit_type = '';

    public string $point_value = '';

    public string $evaluation_type = '';

    public string $prerequisite_unit_id = '';

    /**
     * Snapshot of module_id/order_number as of mount() — needed to compute
     * the shift in save(). Must stay PUBLIC (see Modules\Edit for why).
     * Moving to a DIFFERENT module is treated as a fresh insert into that
     * module's ordering (no shift back in the old module — see
     * CurriculumReorderService); only a same-module reorder uses the
     * forward/backward move.
     */
    public string $originalModuleId = '';

    public int $originalOrderNumber = 0;

    public function mount(Unit $unit): void
    {
        $this->unit = $unit;
        $this->module_id = $unit->module_id;
        $this->order_number = (string) $unit->order_number;
        $this->originalModuleId = $unit->module_id;
        $this->originalOrderNumber = $unit->order_number;
        $this->title = $unit->title;
        $this->estimated_minutes = (string) $unit->estimated_minutes;
        $this->unit_type = $unit->unit_type;
        $this->point_value = (string) $unit->point_value;
        $this->evaluation_type = $unit->evaluation_type;
        $this->prerequisite_unit_id = (string) $unit->prerequisite_unit_id;
    }

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

        if ($validated['prerequisite_unit_id'] === $this->unit->id) {
            $this->addError('prerequisite_unit_id', 'Unit tidak bisa menjadi prasyarat untuk dirinya sendiri.');

            return;
        }

        $validated['prerequisite_unit_id'] = $validated['prerequisite_unit_id'] ?: null;

        $newModuleId = $validated['module_id'];
        $newOrderNumber = (int) $validated['order_number'];

        DB::transaction(function () use ($validated, $reorder, $newModuleId, $newOrderNumber) {
            if ($newModuleId === $this->originalModuleId) {
                $reorder->moveToPosition(Unit::class, ['module_id' => $newModuleId], $this->unit->id, $this->originalOrderNumber, $newOrderNumber);
            } else {
                $reorder->makeRoomForNewPosition(Unit::class, ['module_id' => $newModuleId], $newOrderNumber);
            }

            $this->unit->update($validated);
        });

        $this->originalModuleId = $newModuleId;
        $this->originalOrderNumber = $newOrderNumber;

        session()->flash('status', 'Perubahan unit disimpan.');
    }

    public function render()
    {
        return view('livewire.admin.curriculum.units.edit', [
            'modules' => Module::orderBy('order_number')->get(),
            'prerequisiteOptions' => Unit::with('module')
                ->where('id', '!=', $this->unit->id)
                ->orderBy('module_id')
                ->orderBy('order_number')
                ->get(),
        ]);
    }
}

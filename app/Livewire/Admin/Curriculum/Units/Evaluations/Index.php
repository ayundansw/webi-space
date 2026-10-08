<?php

namespace App\Livewire\Admin\Curriculum\Units\Evaluations;

use App\Models\Unit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Kelola Evaluasi')]
class Index extends Component
{
    public Unit $unit;

    public function mount(Unit $unit): void
    {
        $this->unit = $unit;
    }

    public function delete(string $evaluationId): void
    {
        $this->unit->evaluations()->where('id', $evaluationId)->delete();

        session()->flash('status', 'Soal berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.admin.curriculum.units.evaluations.index', [
            'evaluations' => $this->unit->evaluations()->orderBy('sort_order')->get(),
        ]);
    }
}

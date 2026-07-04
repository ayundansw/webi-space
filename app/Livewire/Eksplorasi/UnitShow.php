<?php

namespace App\Livewire\Eksplorasi;

use App\Models\Unit;
use App\Services\Exploration\ProgressService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class UnitShow extends Component
{
    public Unit $unit;

    public bool $locked = false;

    public function mount(Unit $unit, ProgressService $progress): void
    {
        $this->unit = $unit;
        $user = Auth::user();

        $this->locked = $progress->unitLocked($unit, $user);

        if ($this->locked) {
            return;
        }

        $progress->recordUnitOpened($user, $unit);
    }

    public function render()
    {
        return view('livewire.eksplorasi.unit-show');
    }
}

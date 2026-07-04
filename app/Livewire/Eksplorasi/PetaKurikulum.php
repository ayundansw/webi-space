<?php

namespace App\Livewire\Eksplorasi;

use App\Models\Module;
use App\Models\UserUnitProgress;
use App\Services\Exploration\ProgressService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Peta Kurikulum')]
class PetaKurikulum extends Component
{
    public function render(ProgressService $progress)
    {
        $user = Auth::user();

        $unitProgressByUnitId = UserUnitProgress::where('user_id', $user->id)->get()->keyBy('unit_id');

        $modules = Module::orderBy('order_number')
            ->with(['units' => fn ($query) => $query->orderBy('order_number')])
            ->get()
            ->map(function (Module $module) use ($progress, $user, $unitProgressByUnitId) {
                return [
                    'module' => $module,
                    'status' => $progress->moduleStatus($module, $user),
                    'percentage' => $progress->moduleProgressPercentage($module, $user),
                    'units' => $module->units->map(function ($unit) use ($progress, $user, $unitProgressByUnitId) {
                        $unitProgress = $unitProgressByUnitId->get($unit->id);

                        return [
                            'unit' => $unit,
                            'locked' => $progress->unitLocked($unit, $user),
                            'completed' => $unitProgress?->status === 'completed',
                        ];
                    }),
                ];
            });

        return view('livewire.eksplorasi.peta-kurikulum', ['modules' => $modules]);
    }
}

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
        $isReadOnly = $user->isReadOnlyExploration();

        // Fase 8 Batch 3 (§2.2.A): "sistem lock/unlock DILEWATI" for
        // read-only mode — every module 'active' (open), every unit
        // unlocked. Falling through to the real moduleStatus()/unitLocked()
        // logic here would do the OPPOSITE of what's wanted: a read-only
        // user has zero UserUnitProgress rows, so the real logic would
        // report almost everything LOCKED, not open.
        $unitProgressByUnitId = $isReadOnly
            ? collect()
            : UserUnitProgress::where('user_id', $user->id)->get()->keyBy('unit_id');

        $modules = Module::orderBy('order_number')
            ->with(['units' => fn ($query) => $query->orderBy('order_number')])
            ->get()
            ->map(function (Module $module) use ($progress, $user, $unitProgressByUnitId, $isReadOnly) {
                return [
                    'module' => $module,
                    'status' => $isReadOnly ? 'active' : $progress->moduleStatus($module, $user),
                    'percentage' => $isReadOnly ? 0 : $progress->moduleProgressPercentage($module, $user),
                    'units' => $module->units->map(function ($unit) use ($progress, $user, $unitProgressByUnitId, $isReadOnly) {
                        $unitProgress = $unitProgressByUnitId->get($unit->id);

                        return [
                            'unit' => $unit,
                            'locked' => $isReadOnly ? false : $progress->unitLocked($unit, $user),
                            'completed' => $unitProgress?->status === 'completed',
                            // "Kamu di sini" marker (2.2.3 redesign) — read
                            // directly off the same $unitProgress row already
                            // fetched above, no new query and no change to
                            // ProgressService's lock/unlock logic.
                            'in_progress' => $unitProgress?->status === 'in_progress',
                        ];
                    }),
                ];
            });

        return view('livewire.eksplorasi.peta-kurikulum', [
            'modules' => $modules,
            // Fase 8 Batch 3 (§2.2.A): NEVER ensureProgress() (which
            // firstOrCreate()s a real row) for a read-only user — see
            // ProgressService::blankProgress()'s docblock.
            'userProgress' => $isReadOnly ? $progress->blankProgress() : $progress->ensureProgress($user),
            'overallPercentage' => $isReadOnly ? 0 : $progress->overallProgressPercentage($user),
            'isReadOnlyExploration' => $isReadOnly,
        ]);
    }
}

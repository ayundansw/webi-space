<?php

namespace App\Livewire\Eksplorasi;

use App\Models\Unit;
use App\Services\Exploration\ProgressService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Bagian B (Fase 3): restrukturisasi 3-kolom (Daftar Isi Modul | Materi |
 * WEBI). `moduleUnits`/`moduleProgress`/`userProgress` BARU ditambahkan di
 * sini untuk kolom kiri (Daftar Isi) dan indikator "stack poin progres" --
 * semuanya turunan `ProgressService` yang SAMA dipakai Dashboard/Peta
 * Kurikulum, tidak ada logic lock/unlock baru yang diciptakan ulang.
 */
#[Layout('components.layouts.app')]
class UnitShow extends Component
{
    public Unit $unit;

    public bool $locked = false;

    public function mount(Unit $unit, ProgressService $progress): void
    {
        $this->unit = $unit;
        $user = Auth::user();

        // Fase 8 Batch 3 (§2.2.A): read-only mode skips the lock system
        // entirely (always unlocked) AND must never call recordUnitOpened()
        // — that method writes/updates UserUnitProgress (and touches
        // ensureProgress() internally) just from OPENING the page, which
        // is exactly the "progress created for a read-only user" bug
        // RECON_fase8_mode_ganda.md poin 6 flagged.
        if ($user->isReadOnlyExploration()) {
            $this->locked = false;

            return;
        }

        $this->locked = $progress->unitLocked($unit, $user);

        if ($this->locked) {
            return;
        }

        $progress->recordUnitOpened($user, $unit);
    }

    public function render(ProgressService $progress)
    {
        $user = Auth::user();
        $isReadOnly = $user->isReadOnlyExploration();

        if ($this->locked) {
            return view('livewire.eksplorasi.unit-show');
        }

        return view('livewire.eksplorasi.unit-show', [
            'moduleUnits' => $isReadOnly ? $this->moduleUnitsOpenForReadOnly() : $this->moduleUnitsWithStatus($progress, $user),
            'moduleProgressPercentage' => $isReadOnly ? 0 : $progress->moduleProgressPercentage($this->unit->module, $user),
            // Fase 8 Batch 3: never ensureProgress() for a read-only user —
            // see ProgressService::blankProgress()'s docblock.
            'userProgress' => $isReadOnly ? $progress->blankProgress() : $progress->ensureProgress($user),
            'isReadOnlyExploration' => $isReadOnly,
        ]);
    }

    /**
     * Read-only counterpart to moduleUnitsWithStatus() below — every unit
     * in the module shown unlocked/not-completed (no progress exists to
     * derive those flags from), same array shape so the shared blade
     * partial doesn't need its own read-only branch.
     */
    private function moduleUnitsOpenForReadOnly()
    {
        return $this->unit->module->units->sortBy('order_number')->map(fn (Unit $moduleUnit) => [
            'unit' => $moduleUnit,
            'locked' => false,
            'completed' => false,
            'in_progress' => false,
            'is_current' => $moduleUnit->id === $this->unit->id,
        ])->values();
    }

    /**
     * Same shape as PetaKurikulum::render()'s per-unit array (locked/
     * completed/in_progress), scoped to just THIS unit's module -- powers
     * the new left-column Daftar Isi Modul.
     */
    private function moduleUnitsWithStatus(ProgressService $progress, $user)
    {
        $unitProgressByUnitId = \App\Models\UserUnitProgress::where('user_id', $user->id)
            ->whereIn('unit_id', $this->unit->module->units->pluck('id'))
            ->get()
            ->keyBy('unit_id');

        return $this->unit->module->units->sortBy('order_number')->map(function (Unit $moduleUnit) use ($progress, $user, $unitProgressByUnitId) {
            $unitProgress = $unitProgressByUnitId->get($moduleUnit->id);

            return [
                'unit' => $moduleUnit,
                'locked' => $progress->unitLocked($moduleUnit, $user),
                'completed' => $unitProgress?->status === 'completed',
                'in_progress' => $unitProgress?->status === 'in_progress',
                'is_current' => $moduleUnit->id === $this->unit->id,
            ];
        })->values();
    }
}

<?php

namespace App\Services\Exploration;

use App\Models\CheckpointCompletion;
use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserExplorationProgress;
use App\Models\UserUnitProgress;
use Illuminate\Support\Collection;

class ProgressService
{
    public function __construct(private Notifier $notifier) {}

    public function ensureProgress(User $user): UserExplorationProgress
    {
        return UserExplorationProgress::firstOrCreate(
            ['user_id' => $user->id],
            [
                'current_level' => 1,
                'level_name' => config('exploration.level_names')[1],
                'total_points' => 0,
            ]
        );
    }

    public function moduleStatus(Module $module, User $user): string
    {
        $previousModule = Module::where('order_number', '<', $module->order_number)
            ->orderByDesc('order_number')
            ->first();

        if ($previousModule && ! $this->moduleCompleted($previousModule, $user)) {
            return 'locked';
        }

        return $this->moduleCompleted($module, $user) ? 'completed' : 'active';
    }

    public function moduleCompleted(Module $module, User $user): bool
    {
        if (! $this->allUnitsCompleted($module, $user)) {
            return false;
        }

        $checkpoint = $module->checkpoint;

        if (! $checkpoint) {
            return true;
        }

        return CheckpointCompletion::where('user_id', $user->id)
            ->where('checkpoint_id', $checkpoint->id)
            ->exists();
    }

    public function allUnitsCompleted(Module $module, User $user): bool
    {
        $unitIds = $module->units()->pluck('id');

        if ($unitIds->isEmpty()) {
            return false;
        }

        $completedCount = UserUnitProgress::where('user_id', $user->id)
            ->whereIn('unit_id', $unitIds)
            ->where('status', 'completed')
            ->count();

        return $completedCount >= $unitIds->count();
    }

    public function unitLocked(Unit $unit, User $user): bool
    {
        if ($this->moduleStatus($unit->module, $user) === 'locked') {
            return true;
        }

        if ($unit->prerequisite_unit_id) {
            $prerequisiteDone = UserUnitProgress::where('user_id', $user->id)
                ->where('unit_id', $unit->prerequisite_unit_id)
                ->where('status', 'completed')
                ->exists();

            if (! $prerequisiteDone) {
                return true;
            }
        }

        return false;
    }

    public function nextUnitFor(User $user): ?Unit
    {
        $modules = Module::orderBy('order_number')
            ->with(['units' => fn ($query) => $query->orderBy('order_number')])
            ->get();

        foreach ($modules as $module) {
            foreach ($module->units as $unit) {
                if ($this->unitLocked($unit, $user)) {
                    continue;
                }

                $progress = UserUnitProgress::where('user_id', $user->id)->where('unit_id', $unit->id)->first();

                if (! $progress || $progress->status !== 'completed') {
                    return $unit;
                }
            }
        }

        return null;
    }

    public function recordUnitOpened(User $user, Unit $unit): UserUnitProgress
    {
        $progress = UserUnitProgress::firstOrNew(['user_id' => $user->id, 'unit_id' => $unit->id]);

        if (! $progress->exists) {
            $progress->status = 'in_progress';
            $progress->open_count_without_completion = 0;
            $progress->save();
        } elseif ($progress->status !== 'completed') {
            $progress->status = 'in_progress';
            $progress->open_count_without_completion += 1;
            $progress->save();
        }

        $this->ensureProgress($user)->update(['current_unit_id' => $unit->id]);

        return $progress;
    }

    public function completeUnit(User $user, Unit $unit): UserUnitProgress
    {
        $progress = UserUnitProgress::firstOrNew(['user_id' => $user->id, 'unit_id' => $unit->id]);
        $alreadyCompleted = $progress->exists && $progress->status === 'completed';

        $progress->status = 'completed';
        $progress->completed_at = $progress->completed_at ?? now();
        $progress->open_count_without_completion ??= 0;
        $progress->save();

        // Idempotent: a quiz retry re-calls this after the unit is already completed,
        // and must not double-award points earned on the first attempt.
        if (! $alreadyCompleted) {
            $this->awardPoints($user, $unit->point_value);
            $this->notifyNewlyUnlockedUnits($user, $unit);
        }

        $this->refreshCurrentUnit($user);

        return $progress;
    }

    public function completeCheckpoint(User $user, \App\Models\Checkpoint $checkpoint, array $data): CheckpointCompletion
    {
        $completion = CheckpointCompletion::create([
            'user_id' => $user->id,
            'checkpoint_id' => $checkpoint->id,
            'checklist_answers' => $data['checklist_answers'],
            'intermezo_answers' => $data['intermezo_answers'],
            'form_tanggapan' => $data['form_tanggapan'],
            'points_awarded' => 25,
        ]);

        $this->awardPoints($user, 25);
        $this->refreshCurrentUnit($user);

        $this->notifier->send(
            $user,
            'checkpoint_completed',
            'Checkpoint tuntas!',
            "Selamat! Kamu menuntaskan checkpoint Modul {$checkpoint->module->title} dan dapat 25 poin bonus.",
            $checkpoint,
        );

        return $completion;
    }

    public function awardPoints(User $user, int $points): UserExplorationProgress
    {
        $progress = $this->ensureProgress($user);
        $levelBefore = $progress->current_level;

        $progress->total_points += $points;
        [$level, $name] = $this->resolveLevel($progress->total_points);
        $progress->current_level = $level;
        $progress->level_name = $name;
        $progress->save();

        if ($level > $levelBefore) {
            $this->notifier->send(
                $user,
                'level_up',
                'Naik level!',
                "Keren! Kamu naik ke Level {$level}: {$name}.",
            );
        }

        return $progress;
    }

    /**
     * PRD 3.1.8 "Pengingat unit baru tersedia (setelah menyelesaikan unit
     * sebelumnya)" — scoped narrowly to the direct prerequisite chain (units
     * whose prerequisite_unit_id is the one just completed), not every unit
     * that happens to become reachable. Crossing a module boundary via a
     * checkpoint completion is a separate, rarer case not covered here to
     * avoid speculative scope beyond the literal PRD wording.
     */
    private function notifyNewlyUnlockedUnits(User $user, Unit $completedUnit): void
    {
        $unlockedNextUnits = Unit::where('prerequisite_unit_id', $completedUnit->id)->get();

        foreach ($unlockedNextUnits as $nextUnit) {
            if (! $this->unitLocked($nextUnit, $user)) {
                $this->notifier->send(
                    $user,
                    'new_unit_unlocked',
                    'Unit baru terbuka',
                    "Unit \"{$nextUnit->title}\" sudah bisa kamu akses sekarang.",
                    $nextUnit,
                );
            }
        }
    }

    protected function refreshCurrentUnit(User $user): void
    {
        $next = $this->nextUnitFor($user);
        $this->ensureProgress($user)->update(['current_unit_id' => $next?->id]);
    }

    protected function resolveLevel(int $totalPoints): array
    {
        $thresholds = config('exploration.level_thresholds');
        $level = 1;

        foreach ($thresholds as $lvl => $minPoints) {
            if ($totalPoints >= $minPoints) {
                $level = $lvl;
            }
        }

        return [$level, config('exploration.level_names')[$level]];
    }

    public function overallProgressPercentage(User $user): int
    {
        $total = Unit::count();

        if ($total === 0) {
            return 0;
        }

        $completed = UserUnitProgress::where('user_id', $user->id)->where('status', 'completed')->count();

        return (int) round($completed / $total * 100);
    }

    public function moduleProgressPercentage(Module $module, User $user): int
    {
        $unitIds = $module->units()->pluck('id');

        if ($unitIds->isEmpty()) {
            return 0;
        }

        $completed = UserUnitProgress::where('user_id', $user->id)
            ->whereIn('unit_id', $unitIds)
            ->where('status', 'completed')
            ->count();

        return (int) round($completed / $unitIds->count() * 100);
    }

    public function feedFor(User $user, int $limit = 15): Collection
    {
        $unitEvents = UserUnitProgress::with('unit')
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->get()
            ->map(fn (UserUnitProgress $progress) => [
                'timestamp' => $progress->completed_at,
                'message' => "Selamat! Kamu mendapatkan {$progress->unit->point_value} poin karena menuntaskan {$progress->unit->title}. Terus jaga semangatmu!",
            ]);

        $checkpointEvents = CheckpointCompletion::with('checkpoint.module')
            ->where('user_id', $user->id)
            ->get()
            ->map(fn (CheckpointCompletion $completion) => [
                'timestamp' => $completion->completed_at,
                'message' => "Modul {$completion->checkpoint->module->title} tuntas! Kamu dapat {$completion->points_awarded} poin bonus checkpoint. Keren banget progresnya!",
            ]);

        return $unitEvents->concat($checkpointEvents)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();
    }
}

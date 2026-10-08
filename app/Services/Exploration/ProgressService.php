<?php

namespace App\Services\Exploration;

use App\Models\CheckpointCompletion;
use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserExplorationProgress;
use App\Models\UserUnitProgress;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProgressService
{
    public function __construct(private Notifier $notifier, private PointService $pointService) {}

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

    /**
     * Fase 8 Batch 3: for an execution_member in read-only Eksplorasi mode
     * (§2.2.A — "TIDAK ada UserExplorationProgress yang dibuat/diubah").
     * Same default shape ensureProgress() would create, but NEVER saved —
     * callers that would otherwise call ensureProgress() (PetaKurikulum,
     * UnitShow) use this instead when User::isReadOnlyExploration() is
     * true, so the view always has a UserExplorationProgress-shaped object
     * to read from without ever persisting a row for a user who was only
     * ever reading, never participating.
     */
    public function blankProgress(): UserExplorationProgress
    {
        return new UserExplorationProgress([
            'current_level' => 1,
            'level_name' => config('exploration.level_names')[1],
            'total_points' => 0,
        ]);
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
            $this->pointService->award($user, $unit->point_value);
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

        $this->pointService->award($user, 25);
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

    /**
     * Per-day activity counts for the "Kalender Aktivitas" heatmap — grouped
     * by calendar day via SQL to avoid loading every row for a multi-month
     * window.
     *
     * @return array<string, int> keyed by 'Y-m-d', summed across both
     *     tables when a day has both a unit and a checkpoint completion.
     *     Days with zero activity are simply absent from the array — the
     *     caller fills gaps when building the display grid.
     */
    public function activityHeatmap(User $user, int $days): array
    {
        $since = now()->subDays($days - 1)->startOfDay();

        $unitDays = UserUnitProgress::where('user_id', $user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $since)
            ->selectRaw('DATE(completed_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $checkpointDays = CheckpointCompletion::where('user_id', $user->id)
            ->where('completed_at', '>=', $since)
            ->selectRaw('DATE(completed_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $counts = [];

        foreach ([$unitDays, $checkpointDays] as $dayCounts) {
            foreach ($dayCounts as $day => $total) {
                $counts[$day] = ($counts[$day] ?? 0) + (int) $total;
            }
        }

        return $counts;
    }

    /**
     * Member-facing leaderboard: Top 5 by total_points, tie-broken by
     * whoever reached that total FIRST (deterministic, never a shared rank).
     *
     * Tie-break timestamp is deliberately NOT UserExplorationProgress.updated_at
     * (also bumped by recordUnitOpened() with zero points earned). The real
     * point-earning events are UserUnitProgress.completed_at and
     * CheckpointCompletion.completed_at; the most recent of those is when
     * the current total_points was reached.
     *
     * SECURITY: computes ranking over ALL exploration_members, but only the
     * top 5 rows plus the requesting user's own rank/points are ever
     * returned — every other member's data stays local to this method and
     * must never be exposed via a public Livewire property.
     *
     * @return array{
     *     top5: array<int, array{rank: int, name: string, points: int, is_self: bool}>,
     *     self: array{rank: int, points: int}|null,
     * }
     */
    public function memberLeaderboard(User $currentUser): array
    {
        $lastEarnedAt = function (string $userId): ?Carbon {
            $unitTimestamp = UserUnitProgress::where('user_id', $userId)
                ->where('status', 'completed')
                ->max('completed_at');

            $checkpointTimestamp = CheckpointCompletion::where('user_id', $userId)->max('completed_at');

            return collect([$unitTimestamp, $checkpointTimestamp])
                ->filter()
                ->map(fn (string $timestamp) => Carbon::parse($timestamp))
                ->max();
        };

        $ranked = User::where('role', 'exploration_member')
            ->get()
            ->map(fn (User $member) => [
                'user_id' => $member->id,
                'name' => $member->name,
                'points' => $this->ensureProgress($member)->total_points,
                'reached_at' => $lastEarnedAt($member->id),
            ])
            ->sort(function (array $a, array $b) {
                if ($a['points'] !== $b['points']) {
                    return $b['points'] <=> $a['points'];
                }

                // Nobody has earned anything yet on either side — order doesn't matter.
                if ($a['reached_at'] === null && $b['reached_at'] === null) {
                    return 0;
                }

                // A member with points but no recorded completion timestamp
                // (shouldn't normally happen) sorts after one that has one.
                if ($a['reached_at'] === null) {
                    return 1;
                }

                if ($b['reached_at'] === null) {
                    return -1;
                }

                return $a['reached_at'] <=> $b['reached_at'];
            })
            ->values();

        $top5 = $ranked->take(5)->values()->map(fn (array $row, int $index) => [
            'rank' => $index + 1,
            'name' => $row['name'],
            'points' => $row['points'],
            'is_self' => $row['user_id'] === $currentUser->id,
        ])->all();

        $selfIndex = $ranked->search(fn (array $row) => $row['user_id'] === $currentUser->id);

        $self = null;
        if ($selfIndex !== false && $selfIndex >= 5) {
            $self = [
                'rank' => $selfIndex + 1,
                'points' => $ranked[$selfIndex]['points'],
            ];
        }

        return ['top5' => $top5, 'self' => $self];
    }
}

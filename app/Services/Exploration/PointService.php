<?php

namespace App\Services\Exploration;

use App\Models\User;
use App\Models\UserExplorationProgress;

class PointService
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

    public function award(User $user, int $points): UserExplorationProgress
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
     * Public (Fase 6) so App\Console\Commands\RecalculateExplorationLevels
     * can reuse the exact same threshold-lookup logic instead of
     * reimplementing it — the level/level_name pair a given total_points
     * resolves to must never be computed two different ways.
     *
     * @return array{0: int, 1: string}
     */
    public function resolveLevel(int $totalPoints): array
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
}

<?php

namespace App\Services\Exploration;

use App\Models\User;

/**
 * Tingkat Fox (1-5), murni derived dari total_points — read-only bagi user,
 * tidak ada mekanisme pilih. Threshold di config('exploration.fox_avatar_tiers')
 * SENGAJA terpisah dari `level_thresholds` (sistem level 6-tingkat) meski
 * keduanya membaca total_points yang sama — dua sistem independen per
 * RANCANGAN_FINAL Modul 2 §2.4.
 */
class FoxAvatarService
{
    public function __construct(private ProgressService $progressService) {}

    public function tierFor(User $user): int
    {
        return $this->tierForPoints($this->progressService->ensureProgress($user)->total_points);
    }

    public function tierForPoints(int $totalPoints): int
    {
        $tiers = config('exploration.fox_avatar_tiers');
        $tier = 1;

        foreach ($tiers as $level => $minPoints) {
            if ($totalPoints >= $minPoints) {
                $tier = $level;
            }
        }

        return $tier;
    }
}

<?php

namespace Tests\Feature\Console;

use App\Models\User;
use App\Models\UserExplorationProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 6: `exploration:recalculate-levels` re-resolves current_level/
 * level_name for every UserExplorationProgress row from its EXISTING
 * total_points against whatever config('exploration.level_thresholds')
 * currently is — simulates a "stale" row (level stored under an old/wrong
 * threshold) by writing current_level directly, bypassing PointService, the
 * same way a real member's row would go stale after a live threshold change
 * (their total_points doesn't move, only what level that total resolves to).
 */
class RecalculateExplorationLevelsTest extends TestCase
{
    use RefreshDatabase;

    private function memberWithProgress(int $points, int $storedLevel, string $storedName = 'Pengenal'): UserExplorationProgress
    {
        $user = User::create([
            'name' => 'Member '.$points, 'email' => 'member'.$points.'@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ]);

        return UserExplorationProgress::create([
            'user_id' => $user->id, 'total_points' => $points,
            'current_level' => $storedLevel, 'level_name' => $storedName,
        ]);
    }

    public function test_recalculates_a_stale_level_to_match_current_thresholds(): void
    {
        // 204 points resolves to Level 2 under current thresholds, but this
        // row is stored as if it were still Level 1 — the exact situation a
        // threshold change leaves behind.
        $progress = $this->memberWithProgress(204, storedLevel: 1);

        $this->artisan('exploration:recalculate-levels')->assertExitCode(0);

        $progress->refresh();
        $this->assertSame(2, $progress->current_level);
        $this->assertSame('Penyiap', $progress->level_name);
    }

    public function test_does_not_touch_a_row_that_already_matches_current_thresholds(): void
    {
        $progress = $this->memberWithProgress(50, storedLevel: 1, storedName: 'Pengenal');
        $originalUpdatedAt = $progress->updated_at;

        $this->artisan('exploration:recalculate-levels')->assertExitCode(0);

        $progress->refresh();
        $this->assertSame(1, $progress->current_level);
        $this->assertEquals($originalUpdatedAt, $progress->updated_at);
    }

    public function test_dry_run_reports_changes_without_saving_them(): void
    {
        $progress = $this->memberWithProgress(918, storedLevel: 1);

        $this->artisan('exploration:recalculate-levels', ['--dry-run' => true])->assertExitCode(0);

        // Still Level 1 in the DB — dry run must not persist anything.
        $this->assertSame(1, $progress->fresh()->current_level);
    }

    public function test_running_twice_in_a_row_is_idempotent(): void
    {
        $this->memberWithProgress(918, storedLevel: 1);

        $this->artisan('exploration:recalculate-levels')->assertExitCode(0);
        $this->artisan('exploration:recalculate-levels')
            ->expectsOutputToContain('tidak ada yang perlu diubah')
            ->assertExitCode(0);
    }

    public function test_a_downgrade_is_flagged_with_a_warning(): void
    {
        // Stored as Level 6, but total_points only supports Level 1 — a
        // downgrade, the exact case the task's communication concern is about.
        $this->memberWithProgress(0, storedLevel: 6, storedName: 'Lulusan Eksplorasi');

        $this->artisan('exploration:recalculate-levels')
            ->expectsOutputToContain('PENURUNAN level')
            ->assertExitCode(0);
    }
}

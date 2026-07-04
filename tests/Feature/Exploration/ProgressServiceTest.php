<?php

namespace Tests\Feature\Exploration;

use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use App\Services\Exploration\ProgressService;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProgressService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExplorationSampleSeeder::class);
        $this->service = app(ProgressService::class);
    }

    private function member(): User
    {
        return User::create([
            'name' => 'Member',
            'email' => 'member@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
            'membership_status' => 'active',
        ]);
    }

    public function test_first_module_first_unit_is_unlocked_by_default(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();
        $firstUnit = $moduleA->units()->orderBy('order_number')->first();

        $this->assertSame('active', $this->service->moduleStatus($moduleA, $user));
        $this->assertFalse($this->service->unitLocked($firstUnit, $user));
    }

    public function test_second_unit_locked_until_prerequisite_completed(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();
        $units = $moduleA->units()->orderBy('order_number')->get();

        $this->assertTrue($this->service->unitLocked($units[1], $user));

        $this->service->completeUnit($user, $units[0]);

        $this->assertFalse($this->service->unitLocked($units[1], $user));
    }

    public function test_second_module_locked_until_first_module_and_checkpoint_done(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();
        $moduleB = Module::where('order_number', 2)->first();

        $this->assertSame('locked', $this->service->moduleStatus($moduleB, $user));

        foreach ($moduleA->units()->orderBy('order_number')->get() as $unit) {
            $this->service->completeUnit($user, $unit);
        }

        // all units done but checkpoint not yet done -> module A still not "completed"
        $this->assertSame('locked', $this->service->moduleStatus($moduleB, $user));

        $this->service->completeCheckpoint($user, $moduleA->checkpoint, [
            'checklist_answers' => ['0' => true],
            'intermezo_answers' => ['0' => 'jawaban reflektif'],
            'form_tanggapan' => 'seru!',
        ]);

        $this->assertSame('completed', $this->service->moduleStatus($moduleA, $user));
        $this->assertSame('active', $this->service->moduleStatus($moduleB, $user));
    }

    public function test_points_accumulate_correctly(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();

        $progress = $this->service->ensureProgress($user);
        $this->assertSame(0, $progress->total_points);
        $this->assertSame(1, $progress->current_level);

        $totalModuleAUnitPoints = 0;
        foreach ($moduleA->units()->orderBy('order_number')->get() as $unit) {
            $this->service->completeUnit($user, $unit);
            $totalModuleAUnitPoints += $unit->point_value;
        }

        $this->service->completeCheckpoint($user, $moduleA->checkpoint, [
            'checklist_answers' => ['0' => true],
            'intermezo_answers' => ['0' => 'x'],
            'form_tanggapan' => 'x',
        ]);

        $progress->refresh();
        $this->assertSame($totalModuleAUnitPoints + 25, $progress->total_points);

        // The 2-module sample dataset's entire max (140 pts) sits below the real
        // Level 2 threshold (150, from config/exploration.php — computed from the
        // real 67-unit curriculum in task 2.3). Completing just module A (70 pts)
        // correctly keeps the member at Level 1; this is expected, not a bug.
        $this->assertSame(1, $progress->current_level);
    }

    public function test_level_resolves_correctly_against_real_thresholds(): void
    {
        $user = $this->member();

        $progress = $this->service->awardPoints($user, 149);
        $this->assertSame(1, $progress->current_level);
        $this->assertSame('Pengenal', $progress->level_name);

        $progress = $this->service->awardPoints($user, 1); // total now 150
        $this->assertSame(2, $progress->current_level);
        $this->assertSame('Penyiap', $progress->level_name);

        $progress = $this->service->awardPoints($user, 755); // total now 905
        $this->assertSame(6, $progress->current_level);
        $this->assertSame('Lulusan Eksplorasi', $progress->level_name);
    }

    public function test_next_unit_for_tracks_current_position(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();
        $units = $moduleA->units()->orderBy('order_number')->get();

        $next = $this->service->nextUnitFor($user);
        $this->assertTrue($next->is($units[0]));

        $this->service->completeUnit($user, $units[0]);
        $next = $this->service->nextUnitFor($user);
        $this->assertTrue($next->is($units[1]));
    }

    public function test_open_count_increments_only_on_repeat_opens_without_completion(): void
    {
        $user = $this->member();
        $unit = Unit::where('order_number', 1)->first();

        $progress = $this->service->recordUnitOpened($user, $unit);
        $this->assertSame(0, $progress->open_count_without_completion);
        $this->assertSame('in_progress', $progress->status);

        $progress = $this->service->recordUnitOpened($user, $unit);
        $this->assertSame(1, $progress->open_count_without_completion);

        $progress = $this->service->recordUnitOpened($user, $unit);
        $this->assertSame(2, $progress->open_count_without_completion);
    }
}

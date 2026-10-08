<?php

namespace Tests\Feature\Exploration;

use App\Models\Checkpoint;
use App\Models\CheckpointCompletion;
use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserUnitProgress;
use App\Services\Exploration\ProgressService;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
        // Level 2 threshold (204, from config/exploration.php — recomputed Fase 6
        // once Materi+Checkpoint+Praktik were all live). Completing just module A
        // (70 pts) correctly keeps the member at Level 1; this is expected, not a bug.
        $this->assertSame(1, $progress->current_level);
    }

    /**
     * Bagian A (perbaikan lanjutan, Fase 3): "pastikan akumulasi
     * level/poin/progres realtime dan terintegrasi" -- dibuktikan konkret
     * di sini, bukan cuma diasumsikan. Dashboard dan Peta Kurikulum
     * SAMA-SAMA memanggil `ProgressService::ensureProgress()` /
     * `overallProgressPercentage()` langsung dari DB setiap kali di-render
     * (tidak ada cache/computed-cached di antaranya) -- begitu satu unit
     * selesai, request BERIKUTNYA ke halaman manapun otomatis melihat
     * angka baru, tanpa perlu invalidasi cache manual karena memang tidak
     * pernah di-cache dari awal.
     */
    public function test_points_level_and_percentage_are_consistent_across_dashboard_and_peta_kurikulum_immediately(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();
        $firstUnit = $moduleA->units()->orderBy('order_number')->first();

        $this->service->completeUnit($user, $firstUnit);
        $progress = $this->service->ensureProgress($user);

        // No intermediate cache-clear call anywhere between these two --
        // both components must reflect the exact same fresh numbers, read
        // straight from the DB on each independent render() call.
        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Eksplorasi\Dashboard::class)
            ->assertSee((string) $progress->total_points)
            ->assertSee($progress->level_name);

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Eksplorasi\PetaKurikulum::class)
            ->assertSee((string) $progress->total_points)
            ->assertSee($progress->level_name)
            ->assertSee('Level '.$progress->current_level.' :');
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

    /**
     * Fase 3 Batch 7b (Profil heatmap): activityHeatmap() combines
     * UserUnitProgress and CheckpointCompletion `completed_at`, grouped by
     * calendar day via SQL GROUP BY — pins that (a) two completions on the
     * SAME day from DIFFERENT tables sum into one count, (b) a different
     * day gets its own separate entry, and (c) activity outside the
     * requested window is excluded entirely (not just zeroed).
     */
    public function test_activity_heatmap_groups_completions_by_calendar_day_across_both_tables(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();
        $units = $moduleA->units()->orderBy('order_number')->get();
        $checkpoint = Checkpoint::where('module_id', $moduleA->id)->first();

        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();
        $wayOutsideWindow = $today->copy()->subDays(200);

        // Two unit completions + one checkpoint completion, all "today" —
        // must sum to 3, not overwrite each other.
        UserUnitProgress::create([
            'user_id' => $user->id, 'unit_id' => $units[0]->id, 'status' => 'completed',
            'open_count_without_completion' => 0, 'completed_at' => $today->copy()->setTime(9, 0),
        ]);
        UserUnitProgress::create([
            'user_id' => $user->id, 'unit_id' => $units[1]->id, 'status' => 'completed',
            'open_count_without_completion' => 0, 'completed_at' => $today->copy()->setTime(14, 30),
        ]);
        CheckpointCompletion::create([
            'user_id' => $user->id, 'checkpoint_id' => $checkpoint->id,
            'checklist_answers' => [], 'intermezo_answers' => [], 'form_tanggapan' => 'selesai',
            'points_awarded' => 25, 'completed_at' => $today->copy()->setTime(20, 0),
        ]);

        // A separate day, own entry.
        UserUnitProgress::create([
            'user_id' => $user->id, 'unit_id' => $units[2]->id, 'status' => 'completed',
            'open_count_without_completion' => 0, 'completed_at' => $yesterday,
        ]);

        // Outside the 30-day window requested below — must not appear at all.
        UserUnitProgress::create([
            'user_id' => $user->id, 'unit_id' => $units[3]->id, 'status' => 'completed',
            'open_count_without_completion' => 0, 'completed_at' => $wayOutsideWindow,
        ]);

        $counts = $this->service->activityHeatmap($user, 30);

        $this->assertSame(3, $counts[$today->format('Y-m-d')]);
        $this->assertSame(1, $counts[$yesterday->format('Y-m-d')]);
        $this->assertArrayNotHasKey($wayOutsideWindow->format('Y-m-d'), $counts);
    }
}

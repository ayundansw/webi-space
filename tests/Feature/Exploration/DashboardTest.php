<?php

namespace Tests\Feature\Exploration;

use App\Models\Module;
use App\Models\User;
use App\Services\Exploration\ProgressService;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExplorationSampleSeeder::class);
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

    public function test_dashboard_shows_starting_state_for_new_user(): void
    {
        $user = $this->member();

        $this->actingAs($user)->get('/eksplorasi/dashboard')
            ->assertOk()
            ->assertSee('Pengenal')
            ->assertSee('0%')
            ->assertSee('Apa Itu Software Development?');
    }

    public function test_dashboard_reflects_points_level_and_feed_after_progress(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();
        $progress = app(ProgressService::class);

        foreach ($moduleA->units()->orderBy('order_number')->get() as $unit) {
            $progress->completeUnit($user, $unit);
        }
        $progress->completeCheckpoint($user, $moduleA->checkpoint, [
            'checklist_answers' => ['0' => true],
            'intermezo_answers' => ['0' => 'seru'],
            'form_tanggapan' => 'oke',
        ]);

        $userProgress = $progress->ensureProgress($user);

        $this->actingAs($user)->get('/eksplorasi/dashboard')
            ->assertOk()
            ->assertSee((string) $userProgress->total_points)
            ->assertSee($userProgress->level_name)
            ->assertSee('Selamat! Kamu mendapatkan')
            ->assertSee('poin bonus checkpoint');
    }

    public function test_dashboard_never_shows_leaderboard_or_ranking(): void
    {
        $user = $this->member();

        $response = $this->actingAs($user)->get('/eksplorasi/dashboard')->assertOk();

        $response->assertDontSee('Leaderboard', false);
        $response->assertDontSee('Ranking', false);
        $response->assertDontSee('Peringkat', false);
    }
}

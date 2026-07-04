<?php

namespace Tests\Feature\Admin;

use App\Models\Conversation;
use App\Models\GuardrailFlag;
use App\Models\Message;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\Unit;
use App\Models\User;
use App\Services\Exploration\ProgressService;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 2.6 Batch 2: the admin dashboard is now unified (PRD 3.0.3), combining
 * Eksplorasi (progress + admin-only leaderboard) and Eksekusi (built in 2.4)
 * in one page, plus a WEBI log quick-access summary.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExplorationSampleSeeder::class);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function explorationMember(string $name): User
    {
        return User::create([
            'name' => $name, 'email' => strtolower($name).'@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ]);
    }

    public function test_unified_dashboard_shows_both_eksplorasi_and_eksekusi_sections(): void
    {
        $admin = $this->admin();
        $member = $this->explorationMember('Agitsa');

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Eksplorasi')
            ->assertSee('Eksekusi')
            ->assertSee('Leaderboard')
            ->assertSee($member->name)
            ->assertSee('Log Percakapan WEBI');
    }

    public function test_leaderboard_ranks_members_by_points_descending_and_is_never_shown_to_members(): void
    {
        $admin = $this->admin();
        $progress = $this->app->make(ProgressService::class);

        $low = $this->explorationMember('Dipa');
        $high = $this->explorationMember('Bilal');

        $unit = Unit::where('order_number', 1)->first();
        $progress->completeUnit($low, $unit);

        $unit2 = Unit::where('order_number', 2)->first();
        $progress->completeUnit($high, $unit);
        $progress->completeUnit($high, $unit2);

        $response = $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

        $html = $response->getContent();
        $posHigh = strpos($html, 'Bilal');
        $posLow = strpos($html, 'Dipa');

        $this->assertNotFalse($posHigh);
        $this->assertNotFalse($posLow);
        $this->assertLessThan($posLow, $posHigh, 'Anggota dengan poin lebih tinggi (Bilal) harus muncul lebih dulu di leaderboard daripada Dipa.');

        // PRD 2.1: exploration_member never sees the leaderboard, only their own dashboard.
        $this->actingAs($low)->get('/eksplorasi/dashboard')->assertOk()->assertDontSee('Leaderboard');
    }

    public function test_exploration_progress_shown_to_admin_matches_the_members_own_dashboard(): void
    {
        $admin = $this->admin();
        $member = $this->explorationMember('Citra');
        $progress = $this->app->make(ProgressService::class);
        $unit = Unit::where('order_number', 1)->first();
        $progress->completeUnit($member, $unit);

        $memberOwnPercentage = $progress->overallProgressPercentage($member);

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee($memberOwnPercentage.'%');
    }

    public function test_webi_summary_card_links_to_the_full_log_page(): void
    {
        $admin = $this->admin();
        $member = $this->explorationMember('Dipa');
        $conversation = Conversation::create(['user_id' => $member->id, 'last_activity_at' => now()]);
        $message = Message::create([
            'conversation_id' => $conversation->id, 'sender' => 'user', 'content' => 'Halo WEBI',
        ]);
        GuardrailFlag::create(['message_id' => $message->id, 'flag_type' => 'eval_detection', 'unit_id' => null, 'details' => []]);

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('1 guardrail flag')
            ->assertSee(url('/admin/webi'), false);
    }

    public function test_eksekusi_section_still_shows_project_summary_and_alerts(): void
    {
        $admin = $this->admin();
        $project = Project::create([
            'title' => 'Website Portfolio RIT', 'description' => 'D', 'objective' => 'T',
            'project_type' => 'internal', 'status' => 'active',
            'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);
        Milestone::create(['project_id' => $project->id, 'title' => 'Setup', 'target_date' => '2026-07-15', 'sort_order' => 1]);

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Website Portfolio RIT')
            ->assertSee('Ringkasan Proyek Aktif')
            ->assertSee('Alert Panel');
    }
}

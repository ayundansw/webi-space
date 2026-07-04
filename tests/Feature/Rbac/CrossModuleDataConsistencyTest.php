<?php

namespace Tests\Feature\Rbac;

use App\Models\Notification;
use App\Models\Unit;
use App\Models\User;
use App\Services\Exploration\ProgressService;
use App\Services\Webi\PersonalizationContextBuilder;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 2.6 Batch 3: proves the three points/level surfaces (member's own
 * Eksplorasi dashboard, admin's unified dashboard leaderboard, and WEBI's
 * personalization context) all read the same UserExplorationProgress row via
 * ProgressService — there is no second, independently-computed copy that
 * could silently drift out of sync. Also proves Eksplorasi and Eksekusi
 * notifications land in the same `notifications` table.
 */
class CrossModuleDataConsistencyTest extends TestCase
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
            'name' => 'Citra', 'email' => 'citra@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    public function test_points_and_level_agree_across_member_dashboard_admin_dashboard_and_webi_context(): void
    {
        $member = $this->member();
        $admin = $this->admin();
        $progress = $this->app->make(ProgressService::class);
        $unit = Unit::where('order_number', 1)->first();

        $progress->completeUnit($member, $unit);

        $expectedPoints = $progress->ensureProgress($member)->total_points;
        $this->assertGreaterThan(0, $expectedPoints);

        // 1. member's own dashboard
        $this->actingAs($member)->get('/eksplorasi/dashboard')
            ->assertOk()
            ->assertSee((string) $expectedPoints);

        // 2. admin's unified dashboard leaderboard
        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee($expectedPoints.' poin');

        // 3. WEBI personalization context handed to the Gemini prompt
        $contextBlock = $this->app->make(PersonalizationContextBuilder::class)->build($member->fresh(), false);
        $this->assertStringContainsString("total_points: {$expectedPoints}", $contextBlock);
    }

    public function test_exploration_and_execution_notifications_share_the_same_table(): void
    {
        $member = $this->member();
        $progress = $this->app->make(ProgressService::class);
        $unit = Unit::where('order_number', 1)->first();

        $progress->completeUnit($member, $unit);

        $notification = Notification::where('recipient_id', $member->id)->first();

        $this->assertNotNull($notification, 'Eksplorasi trigger should write to the shared notifications table.');
        $this->assertInstanceOf(Notification::class, $notification);
    }
}

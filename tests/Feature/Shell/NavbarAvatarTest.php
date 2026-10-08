<?php

namespace Tests\Feature\Shell;

use App\Models\User;
use App\Services\Exploration\PointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Navbar account-menu avatar dispatch (Fase 2 Langkah 5) — must render the
 * role-appropriate avatar component: <x-avatar.fox> for exploration_member
 * (tier from FoxAvatarService, real data not a hardcoded tier), <x-avatar.eksekusi>
 * for execution_member (from users.avatar_url), generic icon for admin.
 */
class NavbarAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_exploration_member_gets_fox_avatar_at_correct_tier(): void
    {
        $member = User::factory()->create();
        app(PointService::class)->award($member, 150); // Fox tier 3 (101-300)

        $response = $this->actingAs($member)->get('/eksplorasi/dashboard');

        $response->assertOk()->assertSee('Avatar Fox tingkat 3');
    }

    public function test_exploration_member_with_zero_points_gets_tier_one_fox(): void
    {
        $member = User::factory()->create();

        $response = $this->actingAs($member)->get('/eksplorasi/dashboard');

        $response->assertOk()->assertSee('Avatar Fox tingkat 1');
    }

    public function test_execution_member_gets_chosen_eksekusi_avatar(): void
    {
        $execution = User::factory()->executionMember()->create(['avatar_url' => 'harimau']);

        $response = $this->actingAs($execution)->get('/eksekusi/dashboard');

        $response->assertOk()->assertSee('Avatar Harimau');
    }

    public function test_execution_member_with_no_selection_yet_falls_back_gracefully(): void
    {
        $execution = User::factory()->executionMember()->create();

        $response = $this->actingAs($execution)->get('/eksekusi/dashboard');

        // avatar_url is null (never chosen) -- must not crash, falls back to Elang.
        $response->assertOk()->assertSee('Avatar Elang');
    }

    public function test_admin_gets_generic_icon_not_fox_or_eksekusi(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk()
            ->assertSee('Avatar Admin')
            ->assertDontSee('Avatar Fox')
            ->assertDontSee('Avatar Elang');
    }
}

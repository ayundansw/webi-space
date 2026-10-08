<?php

namespace Tests\Feature\DualMode;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 8 Batch 6: UI mode switching — badge (<x-shell.mode-badge>, Fase 2
 * Langkah 5 slot, finally wired up), account-menu dropdown ("Beralih ke
 * Mode Eksekusi" / "Beralih ke Mode Eksplorasi" / "Kembali ke Mode Asal"),
 * and SwitchModeController itself (the real, non-tinker way to flip
 * active_mode from here on). Gate methods (canAccessExecution() /
 * canAccessExploration()) are Batch 2/3 territory and untouched — this
 * file only proves the UI built on top of them.
 */
class ModeSwitchingTest extends TestCase
{
    use RefreshDatabase;

    private function explorationMember(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Explorer', 'email' => 'explorer@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ], $overrides))->fresh();
    }

    private function executionMember(): User
    {
        return User::create([
            'name' => 'Executor', 'email' => 'exec@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    // ------------------------------------------------------------------
    // User::hasDualModeCapability() / isInSwitchedMode() / currentModeBadgeLabel()
    // ------------------------------------------------------------------

    public function test_hasDualModeCapability_is_true_for_execution_member_and_approved_exploration_member_only(): void
    {
        $this->assertTrue($this->executionMember()->hasDualModeCapability());
        $this->assertTrue($this->explorationMember(['email' => 'approved@example.test', 'dual_mode_status' => 'approved'])->hasDualModeCapability());

        $this->assertFalse($this->explorationMember(['email' => 'none@example.test', 'dual_mode_status' => 'none'])->hasDualModeCapability());
        $this->assertFalse($this->explorationMember(['email' => 'pending@example.test', 'dual_mode_status' => 'pending'])->hasDualModeCapability());
        $this->assertFalse($this->admin()->hasDualModeCapability());
    }

    public function test_approved_exploration_member_has_capability_even_before_ever_switching(): void
    {
        // active_mode still null (never switched) -- capability means "CAN
        // switch", not "is currently switched".
        $user = $this->explorationMember(['dual_mode_status' => 'approved', 'active_mode' => null]);

        $this->assertTrue($user->hasDualModeCapability());
        $this->assertFalse($user->isInSwitchedMode());
    }

    // ------------------------------------------------------------------
    // Badge — tiap kombinasi role/status
    // ------------------------------------------------------------------

    public function test_badge_is_absent_for_a_plain_exploration_member(): void
    {
        $user = $this->explorationMember();

        $this->actingAs($user)->get('/eksplorasi/dashboard')
            ->assertOk()
            ->assertDontSee('Mode: Eksplorasi')
            ->assertDontSee('Mode: Eksekusi');
    }

    public function test_badge_is_absent_for_admin(): void
    {
        $this->actingAs($this->admin())->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee('Mode: Eksplorasi')
            ->assertDontSee('Mode: Eksekusi');
    }

    public function test_badge_shows_eksplorasi_for_an_approved_exploration_member_still_at_origin(): void
    {
        $user = $this->explorationMember(['dual_mode_status' => 'approved', 'active_mode' => null]);

        $this->actingAs($user)->get('/eksplorasi/dashboard')
            ->assertOk()
            ->assertSee('Mode: Eksplorasi', escape: false)
            ->assertDontSee('Mode: Eksekusi');
    }

    public function test_badge_shows_eksekusi_for_an_approved_exploration_member_switched_into_execution(): void
    {
        $user = $this->explorationMember(['dual_mode_status' => 'approved', 'active_mode' => 'execution']);

        $this->actingAs($user)->get('/eksekusi/dashboard')
            ->assertOk()
            ->assertSee('Mode: Eksekusi', escape: false);
    }

    public function test_badge_shows_eksekusi_for_an_execution_member_on_an_eksekusi_page(): void
    {
        $this->actingAs($this->executionMember())->get('/eksekusi/dashboard')
            ->assertOk()
            ->assertSee('Mode: Eksekusi', escape: false)
            ->assertDontSee('Mode: Eksplorasi (Baca)');
    }

    public function test_badge_shows_eksplorasi_baca_for_an_execution_member_on_an_eksplorasi_page(): void
    {
        $this->actingAs($this->executionMember())->get('/eksplorasi/kurikulum')
            ->assertOk()
            ->assertSee('Mode: Eksplorasi (Baca)', escape: false);
    }

    // ------------------------------------------------------------------
    // Dropdown menu akun — teks opsi
    // ------------------------------------------------------------------

    public function test_account_menu_offers_no_switch_option_for_a_plain_exploration_member(): void
    {
        $this->actingAs($this->explorationMember())->get('/eksplorasi/dashboard')
            ->assertOk()
            ->assertDontSee('Beralih ke Mode Eksekusi')
            ->assertDontSee('Beralih ke Mode Eksplorasi')
            ->assertDontSee('Kembali ke Mode Asal');
    }

    public function test_account_menu_offers_switch_to_execution_for_an_approved_member_at_origin(): void
    {
        $user = $this->explorationMember(['dual_mode_status' => 'approved', 'active_mode' => null]);

        $this->actingAs($user)->get('/eksplorasi/dashboard')
            ->assertOk()
            ->assertSee('Beralih ke Mode Eksekusi')
            ->assertDontSee('Kembali ke Mode Asal');
    }

    public function test_account_menu_offers_return_to_origin_for_an_approved_member_in_execution(): void
    {
        $user = $this->explorationMember(['dual_mode_status' => 'approved', 'active_mode' => 'execution']);

        $this->actingAs($user)->get('/eksekusi/dashboard')
            ->assertOk()
            ->assertSee('Kembali ke Mode Asal')
            ->assertDontSee('Beralih ke Mode Eksekusi');
    }

    public function test_account_menu_offers_switch_to_eksplorasi_for_an_execution_member_at_origin(): void
    {
        $this->actingAs($this->executionMember())->get('/eksekusi/dashboard')
            ->assertOk()
            ->assertSee('Beralih ke Mode Eksplorasi')
            ->assertDontSee('Kembali ke Mode Asal');
    }

    public function test_account_menu_offers_return_to_origin_for_an_execution_member_reading_eksplorasi(): void
    {
        $this->actingAs($this->executionMember())->get('/eksplorasi/kurikulum')
            ->assertOk()
            ->assertSee('Kembali ke Mode Asal')
            ->assertDontSee('Beralih ke Mode Eksplorasi');
    }

    // ------------------------------------------------------------------
    // SwitchModeController — toggle sungguhan
    // ------------------------------------------------------------------

    public function test_an_approved_member_can_switch_into_execution_mode(): void
    {
        $user = $this->explorationMember(['dual_mode_status' => 'approved', 'active_mode' => null]);

        $response = $this->actingAs($user)->post('/mode/switch', ['mode' => 'execution']);

        $response->assertRedirect(route('eksekusi.dashboard'));
        $this->assertSame('execution', $user->fresh()->active_mode);
    }

    public function test_an_approved_member_can_switch_back_to_origin(): void
    {
        $user = $this->explorationMember(['dual_mode_status' => 'approved', 'active_mode' => 'execution']);

        $response = $this->actingAs($user)->post('/mode/switch', ['mode' => 'origin']);

        $response->assertRedirect(route('eksplorasi.dashboard'));
        $this->assertNull($user->fresh()->active_mode);
    }

    public function test_switching_into_execution_mode_immediately_unlocks_execution_access(): void
    {
        $user = $this->explorationMember(['dual_mode_status' => 'approved', 'active_mode' => null]);
        $this->assertFalse($user->fresh()->canAccessExecution());

        $this->actingAs($user)->post('/mode/switch', ['mode' => 'execution']);

        $this->assertTrue($user->fresh()->canAccessExecution());
    }

    public function test_switch_route_refuses_a_plain_exploration_member_who_was_never_approved(): void
    {
        $user = $this->explorationMember(['dual_mode_status' => 'none']);

        $this->actingAs($user)->post('/mode/switch', ['mode' => 'execution'])
            ->assertForbidden();

        $this->assertNull($user->fresh()->active_mode);
    }

    public function test_switch_route_refuses_a_pending_member(): void
    {
        $user = $this->explorationMember(['dual_mode_status' => 'pending']);

        $this->actingAs($user)->post('/mode/switch', ['mode' => 'execution'])
            ->assertForbidden();
    }

    public function test_switch_route_refuses_an_execution_member(): void
    {
        $this->actingAs($this->executionMember())->post('/mode/switch', ['mode' => 'execution'])
            ->assertForbidden();
    }

    public function test_switch_route_refuses_a_revoked_member_even_though_they_could_reapply(): void
    {
        // dual_mode_status='none' after a revoke (post-Batch-5 fix) is
        // indistinguishable from "never applied" for this guard, on purpose
        // -- they must go through submitRequest() -> approve() again first.
        $user = $this->explorationMember(['dual_mode_status' => 'none', 'active_mode' => null]);

        $this->actingAs($user)->post('/mode/switch', ['mode' => 'execution'])
            ->assertForbidden();
    }

    public function test_switch_route_rejects_an_invalid_mode_value(): void
    {
        $user = $this->explorationMember(['dual_mode_status' => 'approved']);

        $this->actingAs($user)->post('/mode/switch', ['mode' => 'not-a-real-mode'])
            ->assertSessionHasErrors('mode');
    }

    public function test_switch_route_requires_auth(): void
    {
        $this->post('/mode/switch', ['mode' => 'execution'])->assertRedirect('/login');
    }
}

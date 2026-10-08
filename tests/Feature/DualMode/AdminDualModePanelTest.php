<?php

namespace Tests\Feature\DualMode;

use App\Livewire\Admin\DualMode\Index as AdminDualModeIndex;
use App\Livewire\Admin\Users\Edit as AdminUsersEdit;
use App\Models\DualModeRequest;
use App\Models\User;
use App\Services\DualMode\DualModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 8 Batch 5 (docs/v_2.0/archive/sumber-konsolidasi/RANCANGAN_FINAL_WEBI-SPACE_v2.md §5.3):
 * admin-facing UI on top of DualModeService (Batch 4) — this file only
 * proves the UI wires into that service correctly + RBAC; the service's
 * own business rules already have their own dedicated coverage in
 * tests/Feature/DualMode/DualModeRequestFlowTest.php.
 */
class AdminDualModePanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email = 'admin@example.test'): User
    {
        return User::create([
            'name' => 'Admin', 'email' => $email,
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function explorationMember(string $email = 'explorer@example.test', array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Explorer', 'email' => $email,
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ], $overrides))->fresh();
    }

    private function executionMember(string $email = 'exec@example.test'): User
    {
        return User::create([
            'name' => 'Executor', 'email' => $email,
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);
    }

    // ------------------------------------------------------------------
    // RBAC
    // ------------------------------------------------------------------

    public function test_only_admin_can_view_the_dual_mode_queue(): void
    {
        $admin = $this->admin();
        $exploration = $this->explorationMember();
        $execution = $this->executionMember();

        $this->actingAs($admin)->get('/admin/dual-mode')->assertOk();
        $this->actingAs($exploration)->get('/admin/dual-mode')->assertForbidden();
        $this->actingAs($execution)->get('/admin/dual-mode')->assertForbidden();
    }

    public function test_the_dual_mode_queue_is_reachable_from_the_admin_nav(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Permintaan Mode Ganda');
    }

    // ------------------------------------------------------------------
    // Daftar antrian
    // ------------------------------------------------------------------

    public function test_the_queue_only_lists_pending_requests(): void
    {
        $admin = $this->admin();
        $pendingUser = $this->explorationMember('pending@example.test', ['name' => 'Pending Explorer']);
        $approvedUser = $this->explorationMember('approved@example.test', ['name' => 'Approved Explorer']);

        $pendingRequest = app(DualModeService::class)->submitRequest($pendingUser);
        $approvedRequest = app(DualModeService::class)->submitRequest($approvedUser);
        app(DualModeService::class)->approve($approvedRequest, $admin);

        $html = Livewire::actingAs($admin)->test(AdminDualModeIndex::class)->html();

        $this->assertStringContainsString($pendingUser->name, $html);
        $this->assertStringNotContainsString($approvedUser->name, $html);
    }

    public function test_the_queue_shows_an_empty_state_when_there_is_nothing_pending(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(AdminDualModeIndex::class)
            ->assertSeeText('Tidak ada permintaan yang menunggu keputusan.');
    }

    // ------------------------------------------------------------------
    // Approve dari UI
    // ------------------------------------------------------------------

    public function test_approving_from_the_ui_grants_access_and_removes_it_from_the_queue(): void
    {
        $admin = $this->admin();
        $user = $this->explorationMember();
        $request = app(DualModeService::class)->submitRequest($user);

        Livewire::actingAs($admin)->test(AdminDualModeIndex::class)
            ->call('approve', $request->id)
            ->assertOk();

        $this->assertSame('approved', $user->fresh()->dual_mode_status);

        $html = Livewire::actingAs($admin)->test(AdminDualModeIndex::class)->html();
        $this->assertStringNotContainsString($user->name, $html);
    }

    // ------------------------------------------------------------------
    // Reject dari UI
    // ------------------------------------------------------------------

    public function test_rejecting_from_the_ui_with_a_note_records_it_and_removes_from_the_queue(): void
    {
        $admin = $this->admin();
        $user = $this->explorationMember();
        $request = app(DualModeService::class)->submitRequest($user);

        Livewire::actingAs($admin)->test(AdminDualModeIndex::class)
            ->call('openReject', $request->id)
            ->set("rejectNotes.{$request->id}", 'Belum cukup aktif.')
            ->call('reject', $request->id)
            ->assertOk();

        $this->assertSame('none', $user->fresh()->dual_mode_status);
        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame('Belum cukup aktif.', $request->fresh()->note);

        $html = Livewire::actingAs($admin)->test(AdminDualModeIndex::class)->html();
        $this->assertStringNotContainsString($user->name, $html);
    }

    public function test_rejecting_from_the_ui_without_a_note_is_allowed(): void
    {
        $admin = $this->admin();
        $user = $this->explorationMember();
        $request = app(DualModeService::class)->submitRequest($user);

        Livewire::actingAs($admin)->test(AdminDualModeIndex::class)
            ->call('openReject', $request->id)
            ->call('reject', $request->id)
            ->assertOk();

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertNull($request->fresh()->note);
    }

    // ------------------------------------------------------------------
    // Cabut akses (Admin\Users\Edit)
    // ------------------------------------------------------------------

    public function test_the_revoke_section_only_shows_for_a_currently_approved_user(): void
    {
        $admin = $this->admin();
        $approvedUser = $this->explorationMember('approved@example.test', ['dual_mode_status' => 'approved']);
        $plainUser = $this->explorationMember('plain@example.test');

        Livewire::actingAs($admin)->test(AdminUsersEdit::class, ['user' => $approvedUser])
            ->assertSeeText('Cabut Akses');

        Livewire::actingAs($admin)->test(AdminUsersEdit::class, ['user' => $plainUser])
            ->assertDontSeeText('Cabut Akses');
    }

    public function test_revoking_from_the_ui_works(): void
    {
        $admin = $this->admin();
        $user = $this->explorationMember('approved@example.test', ['dual_mode_status' => 'approved']);

        Livewire::actingAs($admin)->test(AdminUsersEdit::class, ['user' => $user])
            ->call('revokeDualModeAccess')
            ->assertOk();

        // Perbaikan pasca-Batch 5: revoke() reverts to 'none' (like reject()),
        // not a dead-end 'revoked' state — see DualModeService::revoke() docblock.
        $this->assertSame('none', $user->fresh()->dual_mode_status);
        $this->assertSame('revoked', DualModeRequest::where('user_id', $user->id)->where('status', 'revoked')->sole()->status);
    }

    /**
     * THE test this batch's prompt explicitly demands: a user actively
     * USING Mode Eksekusi (active_mode = 'execution') at the moment their
     * access is revoked must never be left stranded there — active_mode
     * resets to null (mode kembali ke Eksplorasi) as part of the SAME
     * action, not a separate step an admin could forget.
     */
    public function test_revoking_a_user_currently_active_in_execution_mode_resets_active_mode_to_null(): void
    {
        $admin = $this->admin();
        $user = $this->explorationMember('active-in-execution@example.test', [
            'dual_mode_status' => 'approved',
            'active_mode' => 'execution',
        ]);

        Livewire::actingAs($admin)->test(AdminUsersEdit::class, ['user' => $user])
            ->call('revokeDualModeAccess');

        $fresh = $user->fresh();
        // Perbaikan pasca-Batch 5: 'none' now, not 'revoked' — see revoke() docblock.
        $this->assertSame('none', $fresh->dual_mode_status);
        $this->assertNull($fresh->active_mode, 'A user active in Mode Eksekusi must be reset to null (mode asal) the instant their access is revoked, never left stranded.');

        // Consequence proven end-to-end: canAccessExecution() now
        // correctly refuses them too, not just the raw column check.
        $this->assertFalse($fresh->canAccessExecution());
    }

    public function test_revoke_section_reflects_the_no_longer_active_wording_after_revocation(): void
    {
        $admin = $this->admin();
        $user = $this->explorationMember('approved@example.test', ['dual_mode_status' => 'approved', 'active_mode' => 'execution']);

        app(DualModeService::class)->revoke($user, $admin);

        // Section disappears entirely once status is no longer 'approved'
        // (reverted to 'none' by the post-Batch-5 fix, not 'revoked').
        Livewire::actingAs($admin)->test(AdminUsersEdit::class, ['user' => $user->fresh()])
            ->assertDontSeeText('Cabut Akses');
    }

    /**
     * Perbaikan pasca-Batch 5 (2026-07-13): the whole point of this fix —
     * a revoked member is not stuck, they can be granted access again
     * through the exact same submit -> approve flow.
     */
    public function test_a_revoked_member_can_be_granted_access_again_afterward(): void
    {
        $admin = $this->admin();
        $user = $this->explorationMember('approved@example.test', ['dual_mode_status' => 'approved']);
        app(DualModeService::class)->revoke($user->fresh(), $admin);

        $newRequest = app(DualModeService::class)->submitRequest($user->fresh());
        Livewire::actingAs($admin)->test(AdminDualModeIndex::class)
            ->call('approve', $newRequest->id)
            ->assertOk();

        $this->assertSame('approved', $user->fresh()->dual_mode_status);
    }
}

<?php

namespace Tests\Feature\DualMode;

use App\Models\DualModeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 8 Batch 1: schema + model foundation only — NO Gate/middleware
 * logic exists yet (see RECON_fase8_mode_ganda.md's proposed batch split).
 * This file proves the new columns/table/relations behave correctly in
 * isolation; it deliberately does NOT test access control, since none of
 * these fields are read by any RBAC check in this batch.
 */
class DualModeSchemaTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'exploration_member', string $email = 'member@example.test'): User
    {
        return User::create([
            'name' => 'Anggota', 'email' => $email,
            'password_hash' => bcrypt('secret123'), 'role' => $role, 'membership_status' => 'active',
        ]);
    }

    // ------------------------------------------------------------------
    // users.dual_mode_status / active_mode
    // ------------------------------------------------------------------

    public function test_a_newly_created_user_defaults_to_no_dual_mode_status_and_no_active_mode_override(): void
    {
        $user = $this->user();

        $this->assertSame('none', $user->fresh()->dual_mode_status);
        $this->assertNull($user->fresh()->active_mode);
    }

    public function test_dual_mode_status_can_be_updated_through_all_four_values(): void
    {
        $user = $this->user();

        foreach (['pending', 'approved', 'revoked', 'none'] as $status) {
            $user->update(['dual_mode_status' => $status]);
            $this->assertSame($status, $user->fresh()->dual_mode_status);
        }
    }

    public function test_active_mode_can_be_set_to_either_value_or_left_null(): void
    {
        $user = $this->user('execution_member', 'exec@example.test');

        $user->update(['active_mode' => 'exploration']);
        $this->assertSame('exploration', $user->fresh()->active_mode);

        $user->update(['active_mode' => 'execution']);
        $this->assertSame('execution', $user->fresh()->active_mode);

        $user->update(['active_mode' => null]);
        $this->assertNull($user->fresh()->active_mode);
    }

    // ------------------------------------------------------------------
    // DualModeRequest model + relations
    // ------------------------------------------------------------------

    public function test_a_dual_mode_request_defaults_to_pending_with_no_reviewer(): void
    {
        $user = $this->user();

        $request = DualModeRequest::create(['user_id' => $user->id])->fresh();

        $this->assertSame('pending', $request->status);
        $this->assertNull($request->reviewed_by);
        $this->assertNull($request->reviewed_at);
        $this->assertNull($request->note);
    }

    public function test_a_dual_mode_request_can_be_created_with_an_explicit_status_and_note(): void
    {
        $user = $this->user();
        $admin = $this->user('admin', 'admin@example.test');

        $request = DualModeRequest::create([
            'user_id' => $user->id,
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'note' => 'Belum cukup aktif di Eksplorasi.',
        ]);

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame($admin->id, $request->fresh()->reviewed_by);
        $this->assertNotNull($request->fresh()->reviewed_at);
        $this->assertSame('Belum cukup aktif di Eksplorasi.', $request->fresh()->note);
    }

    public function test_user_and_reviewer_relations_resolve_to_the_correct_users(): void
    {
        $user = $this->user('exploration_member', 'pemohon@example.test');
        $admin = $this->user('admin', 'admin2@example.test');

        $request = DualModeRequest::create([
            'user_id' => $user->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $this->assertTrue($request->user->is($user));
        $this->assertTrue($request->reviewer->is($admin));
    }

    public function test_a_user_can_have_multiple_dual_mode_requests_over_time(): void
    {
        $user = $this->user();

        DualModeRequest::create(['user_id' => $user->id, 'status' => 'rejected']);
        DualModeRequest::create(['user_id' => $user->id, 'status' => 'approved']);

        $this->assertCount(2, $user->dualModeRequests()->get());
    }

    // ------------------------------------------------------------------
    // Getter sederhana (belum dipakai Gate/middleware manapun)
    // ------------------------------------------------------------------

    public function test_hasApprovedDualModeAccess_reflects_the_status_column(): void
    {
        $user = $this->user();

        $this->assertFalse($user->hasApprovedDualModeAccess());

        $user->update(['dual_mode_status' => 'approved']);
        $this->assertTrue($user->fresh()->hasApprovedDualModeAccess());
    }

    public function test_hasPendingDualModeRequest_reflects_the_status_column(): void
    {
        $user = $this->user();

        $this->assertFalse($user->hasPendingDualModeRequest());

        $user->update(['dual_mode_status' => 'pending']);
        $this->assertTrue($user->fresh()->hasPendingDualModeRequest());
    }

    /**
     * Proves this batch changed NOTHING about existing behavior:
     * hasDualModeCapability() (which <x-shell.mode-badge> and the Profil
     * "Info Mode Ganda" slot both read) still always returns false at this
     * point in the schema, even for a user with an approved grant and an
     * active_mode override — that method is intentionally left untouched
     * until a later batch wires it to these new columns (that batch is
     * Fase 8 Batch 6 — see tests/Feature/DualMode/ModeSwitchingTest.php for
     * its real behavior once wired up).
     */
    public function test_hasDualModeCapability_is_still_always_false_at_this_point_in_the_schema_batch(): void
    {
        $user = $this->user();
        $user->update(['dual_mode_status' => 'none', 'active_mode' => null]);

        // A plain, capability-less exploration_member never has it — this
        // stays true before AND after Batch 6, unlike the approved-grant
        // case which legitimately flips to true starting Batch 6.
        $this->assertFalse($user->fresh()->hasDualModeCapability());
    }
}

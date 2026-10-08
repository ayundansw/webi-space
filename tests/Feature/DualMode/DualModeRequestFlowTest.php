<?php

namespace Tests\Feature\DualMode;

use App\Livewire\Profile\Edit;
use App\Models\DualModeRequest;
use App\Models\Notification;
use App\Models\User;
use App\Services\DualMode\DualModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 8 Batch 4 (docs/v_2.0/archive/sumber-konsolidasi/RANCANGAN_FINAL_WEBI-SPACE_v2.md §2.2.B): the
 * full request/approve/reject/revoke state machine, centralized in
 * App\Services\DualMode\DualModeService. Admin-facing UI for approve/reject
 * is Batch 5 — approve()/reject()/revoke() are tested directly against the
 * service here, exactly as the task prompt allows ("meski UI admin belum
 * ada, service bisa dites langsung").
 */
class DualModeRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    private function explorationMember(string $email = 'explorer@example.test', array $overrides = []): User
    {
        // ->fresh() is required here: Eloquent's create() doesn't know
        // dual_mode_status's DB default ('none') without re-fetching —
        // the in-memory attribute would otherwise be null, not 'none',
        // which submitRequest()'s `!== 'none'` check would wrongly treat
        // as "already applied".
        return User::create(array_merge([
            'name' => 'Explorer', 'email' => $email,
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ], $overrides))->fresh();
    }

    private function admin(string $email = 'admin@example.test'): User
    {
        return User::create([
            'name' => 'Admin', 'email' => $email,
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function executionMember(string $email = 'exec@example.test'): User
    {
        return User::create([
            'name' => 'Executor', 'email' => $email,
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);
    }

    // ------------------------------------------------------------------
    // submitRequest()
    // ------------------------------------------------------------------

    public function test_submitRequest_creates_a_pending_request_and_sets_the_users_status(): void
    {
        $user = $this->explorationMember();

        $request = app(DualModeService::class)->submitRequest($user);

        $this->assertSame('pending', $request->status);
        $this->assertSame($user->id, $request->user_id);
        $this->assertSame('pending', $user->fresh()->dual_mode_status);
    }

    public function test_submitRequest_notifies_every_admin_not_just_one(): void
    {
        $user = $this->explorationMember();
        $admin1 = $this->admin('admin1@example.test');
        $admin2 = $this->admin('admin2@example.test');
        $admin3 = $this->admin('admin3@example.test');

        app(DualModeService::class)->submitRequest($user);

        foreach ([$admin1, $admin2, $admin3] as $admin) {
            $this->assertTrue(
                Notification::where('recipient_id', $admin->id)->where('type', 'dual_mode_request_alert')->exists(),
                "Admin {$admin->email} did not receive the dual_mode_request_alert notification."
            );
        }
        $this->assertSame(3, Notification::where('type', 'dual_mode_request_alert')->count());
    }

    public function test_submitRequest_does_not_notify_the_requester_as_if_they_were_an_admin(): void
    {
        $user = $this->explorationMember();
        app(DualModeService::class)->submitRequest($user);

        $this->assertFalse(Notification::where('recipient_id', $user->id)->exists());
    }

    public function test_submitRequest_prevents_a_second_request_while_pending(): void
    {
        $user = $this->explorationMember();
        app(DualModeService::class)->submitRequest($user);

        $this->expectException(ValidationException::class);
        app(DualModeService::class)->submitRequest($user->fresh());
    }

    public function test_submitRequest_prevents_a_new_request_while_already_approved(): void
    {
        $user = $this->explorationMember(overrides: ['dual_mode_status' => 'approved']);

        $this->expectException(ValidationException::class);
        app(DualModeService::class)->submitRequest($user);
    }

    public function test_submitRequest_rejects_a_non_exploration_member(): void
    {
        $execution = $this->executionMember();

        $this->expectException(ValidationException::class);
        app(DualModeService::class)->submitRequest($execution);
    }

    // ------------------------------------------------------------------
    // approve()
    // ------------------------------------------------------------------

    public function test_approve_grants_status_but_does_not_touch_active_mode(): void
    {
        $user = $this->explorationMember();
        $admin = $this->admin();
        $request = app(DualModeService::class)->submitRequest($user);

        app(DualModeService::class)->approve($request, $admin);

        $fresh = $user->fresh();
        $this->assertSame('approved', $fresh->dual_mode_status);
        $this->assertNull($fresh->active_mode, 'approve() must NOT auto-switch active_mode — the member switches manually (Batch 6).');
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame($admin->id, $request->fresh()->reviewed_by);
        $this->assertNotNull($request->fresh()->reviewed_at);
    }

    public function test_approve_notifies_the_member(): void
    {
        $user = $this->explorationMember();
        $admin = $this->admin();
        $request = app(DualModeService::class)->submitRequest($user);

        app(DualModeService::class)->approve($request, $admin);

        $this->assertTrue(Notification::where('recipient_id', $user->id)->where('type', 'dual_mode_approved')->exists());
    }

    public function test_approve_cannot_be_called_twice_on_the_same_request(): void
    {
        $user = $this->explorationMember();
        $admin = $this->admin();
        $request = app(DualModeService::class)->submitRequest($user);
        app(DualModeService::class)->approve($request, $admin);

        $this->expectException(ValidationException::class);
        app(DualModeService::class)->approve($request->fresh(), $admin);
    }

    // ------------------------------------------------------------------
    // reject()
    // ------------------------------------------------------------------

    public function test_reject_reverts_status_to_none_not_a_permanent_rejected_state(): void
    {
        $user = $this->explorationMember();
        $admin = $this->admin();
        $request = app(DualModeService::class)->submitRequest($user);

        app(DualModeService::class)->reject($request, $admin, 'Belum cukup aktif.');

        $this->assertSame('none', $user->fresh()->dual_mode_status, 'users.dual_mode_status has no "rejected" value — must revert to none.');
        // The rejection itself is NOT lost -- it's permanent history on the
        // specific DualModeRequest row, only the user's CURRENT status resets.
        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame('Belum cukup aktif.', $request->fresh()->note);
    }

    public function test_reject_notifies_the_member_with_the_note_when_given(): void
    {
        $user = $this->explorationMember();
        $admin = $this->admin();
        $request = app(DualModeService::class)->submitRequest($user);

        app(DualModeService::class)->reject($request, $admin, 'Belum cukup aktif.');

        $notification = Notification::where('recipient_id', $user->id)->where('type', 'dual_mode_rejected')->sole();
        $this->assertStringContainsString('Belum cukup aktif.', $notification->message);
    }

    public function test_reject_without_a_note_still_notifies_but_omits_the_reason_line(): void
    {
        $user = $this->explorationMember();
        $admin = $this->admin();
        $request = app(DualModeService::class)->submitRequest($user);

        app(DualModeService::class)->reject($request, $admin, null);

        $notification = Notification::where('recipient_id', $user->id)->where('type', 'dual_mode_rejected')->sole();
        $this->assertStringNotContainsString('Alasan', $notification->message);
    }

    public function test_a_rejected_member_can_submit_a_fresh_request_afterward(): void
    {
        $user = $this->explorationMember();
        $admin = $this->admin();
        $firstRequest = app(DualModeService::class)->submitRequest($user);
        app(DualModeService::class)->reject($firstRequest, $admin, 'Coba lagi nanti.');

        $secondRequest = app(DualModeService::class)->submitRequest($user->fresh());

        $this->assertNotSame($firstRequest->id, $secondRequest->id);
        $this->assertSame(2, DualModeRequest::where('user_id', $user->id)->count());
        $this->assertSame('pending', $user->fresh()->dual_mode_status);
    }

    // ------------------------------------------------------------------
    // revoke()
    // ------------------------------------------------------------------

    public function test_revoke_resets_status_to_none_not_a_permanent_revoked_state(): void
    {
        $user = $this->explorationMember(overrides: ['dual_mode_status' => 'approved', 'active_mode' => 'execution']);
        $admin = $this->admin();

        app(DualModeService::class)->revoke($user, $admin);

        $fresh = $user->fresh();
        $this->assertSame('none', $fresh->dual_mode_status, 'revoke() must behave like reject() — revert to none so the member can reapply, not get stuck on a dead-end revoked state.');
        $this->assertNull($fresh->active_mode);
        $this->assertTrue(Notification::where('recipient_id', $user->id)->where('type', 'dual_mode_revoked')->exists());
    }

    public function test_revoke_rejects_a_user_who_is_not_currently_approved(): void
    {
        $user = $this->explorationMember();
        $admin = $this->admin();

        $this->expectException(ValidationException::class);
        app(DualModeService::class)->revoke($user, $admin);
    }

    /**
     * Perbaikan pasca-Batch 5 (2026-07-13): revoke() now mirrors reject()'s
     * "current status resets, the event itself stays permanent history"
     * pattern — the revocation is recorded as its OWN new DualModeRequest
     * row (status='revoked'), completely separate from and without
     * mutating the original 'approved' row, which stays untouched as the
     * permanent record of who originally approved access and when.
     */
    public function test_revoke_records_a_permanent_history_row_without_touching_the_original_approval(): void
    {
        $user = $this->explorationMember();
        $admin = $this->admin();
        $originalRequest = app(DualModeService::class)->submitRequest($user);
        app(DualModeService::class)->approve($originalRequest, $admin);

        app(DualModeService::class)->revoke($user->fresh(), $admin);

        // The original approval row is untouched — still says 'approved'.
        $this->assertSame('approved', $originalRequest->fresh()->status);

        // A NEW row records the revocation itself, permanently.
        $revocationRow = DualModeRequest::where('user_id', $user->id)->where('status', 'revoked')->sole();
        $this->assertSame($admin->id, $revocationRow->reviewed_by);
        $this->assertNotNull($revocationRow->reviewed_at);

        // Both rows coexist — the full lifecycle stays queryable.
        $this->assertSame(2, DualModeRequest::where('user_id', $user->id)->count());
    }

    public function test_a_revoked_member_can_submit_a_fresh_request_afterward(): void
    {
        $user = $this->explorationMember();
        $admin = $this->admin();
        $originalRequest = app(DualModeService::class)->submitRequest($user);
        app(DualModeService::class)->approve($originalRequest, $admin);
        app(DualModeService::class)->revoke($user->fresh(), $admin);

        $newRequest = app(DualModeService::class)->submitRequest($user->fresh());

        $this->assertSame('pending', $newRequest->status);
        $this->assertSame('pending', $user->fresh()->dual_mode_status);
        // submit + approve + revoke + submit again = 3 rows total.
        $this->assertSame(3, DualModeRequest::where('user_id', $user->id)->count());
    }

    // ------------------------------------------------------------------
    // Integrasi UI (Profile\Edit)
    // ------------------------------------------------------------------

    public function test_the_profile_page_shows_the_request_button_for_a_fresh_member(): void
    {
        $user = $this->explorationMember();

        Livewire::actingAs($user)->test(Edit::class)
            ->assertSeeText('Ajukan Akses Eksekusi');
    }

    public function test_clicking_the_button_flips_the_profile_page_to_pending_status(): void
    {
        $user = $this->explorationMember();

        Livewire::actingAs($user)->test(Edit::class)
            ->call('requestDualModeAccess')
            ->assertHasNoErrors()
            ->assertSeeText('Menunggu Keputusan Admin')
            ->assertDontSeeText('Ajukan Akses Eksekusi');
    }

    public function test_the_profile_page_prevents_a_double_submission_and_shows_a_clear_error(): void
    {
        $user = $this->explorationMember();
        app(DualModeService::class)->submitRequest($user);

        Livewire::actingAs($user->fresh())->test(Edit::class)
            ->call('requestDualModeAccess')
            ->assertHasErrors('dual_mode');

        $this->assertSame(1, DualModeRequest::where('user_id', $user->id)->count());
    }

    public function test_the_profile_page_shows_the_rejection_note_and_still_offers_a_new_request(): void
    {
        $user = $this->explorationMember();
        $admin = $this->admin();
        $request = app(DualModeService::class)->submitRequest($user);
        app(DualModeService::class)->reject($request, $admin, 'Belum cukup aktif di Eksplorasi.');

        Livewire::actingAs($user->fresh())->test(Edit::class)
            ->assertSeeText('Permintaan Sebelumnya Ditolak')
            ->assertSeeText('Belum cukup aktif di Eksplorasi.')
            ->assertSeeText('Ajukan Akses Eksekusi');
    }

    public function test_the_profile_page_shows_approved_status(): void
    {
        $user = $this->explorationMember(overrides: ['dual_mode_status' => 'approved']);

        Livewire::actingAs($user)->test(Edit::class)
            ->assertSeeText('Akses Eksekusi Disetujui');
    }

    public function test_the_profile_page_shows_revoked_history_and_still_offers_a_new_request(): void
    {
        $user = $this->explorationMember();
        $admin = $this->admin();
        $request = app(DualModeService::class)->submitRequest($user);
        app(DualModeService::class)->approve($request, $admin);
        app(DualModeService::class)->revoke($user->fresh(), $admin);

        Livewire::actingAs($user->fresh())->test(Edit::class)
            ->assertSeeText('Akses Dicabut')
            ->assertSeeText('Ajukan Akses Eksekusi');
    }
}

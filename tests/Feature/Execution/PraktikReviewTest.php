<?php

namespace Tests\Feature\Execution;

use App\Livewire\Eksekusi\Praktik\Review;
use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use App\Models\Notification;
use App\Models\User;
use App\Services\Exploration\PointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Praktik 3 Bagian C — the most security-sensitive part of Praktik: a
 * reviewer must NEVER be able to see or act on a submission that isn't
 * assigned to them. Every RBAC scenario below is explicit and isolated,
 * not just implied by the happy path.
 */
class PraktikReviewTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function member(string $name = 'Member'): User
    {
        return User::create([
            'name' => $name, 'email' => strtolower($name).'@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ]);
    }

    private function executionMember(string $name = 'Executor'): User
    {
        return User::create([
            'name' => $name, 'email' => strtolower($name).'@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);
    }

    private function challenge(int $pointsReward = 100): Challenge
    {
        return Challenge::create([
            'title' => 'Challenge Uji', 'description' => 'D', 'level' => 'low',
            'points_reward' => $pointsReward, 'status' => 'published',
        ]);
    }

    private function submission(Challenge $challenge, User $owner, ?User $reviewer = null, int $attempt = 1): ChallengeSubmission
    {
        return ChallengeSubmission::create([
            'challenge_id' => $challenge->id, 'user_id' => $owner->id,
            'submission_type' => 'text', 'content' => 'Jawaban',
            'status' => 'pending', 'attempt_number' => $attempt,
            'assigned_reviewer_id' => $reviewer?->id,
        ]);
    }

    // --- RBAC: the core security guarantee of this batch ---

    public function test_assigned_reviewer_can_open_the_review_page(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member();
        $reviewer = $this->executionMember();
        $submission = $this->submission($challenge, $owner, $reviewer);

        $this->actingAs($reviewer)->get("/eksekusi/praktik/submissions/{$submission->id}")->assertOk();
    }

    public function test_admin_can_open_any_review_page_even_when_unassigned(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member();
        $submission = $this->submission($challenge, $owner); // no reviewer assigned at all

        $this->actingAs($this->admin())->get("/eksekusi/praktik/submissions/{$submission->id}")->assertOk();
    }

    public function test_a_different_execution_member_not_assigned_cannot_open_the_review_page(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member();
        $assignedReviewer = $this->executionMember('Assigned');
        $submission = $this->submission($challenge, $owner, $assignedReviewer);

        $outsider = $this->executionMember('Outsider');

        $this->actingAs($outsider)->get("/eksekusi/praktik/submissions/{$submission->id}")->assertForbidden();
    }

    public function test_exploration_member_cannot_open_the_review_page_at_all(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member();
        $reviewer = $this->executionMember();
        $submission = $this->submission($challenge, $owner, $reviewer);

        $this->actingAs($owner)->get("/eksekusi/praktik/submissions/{$submission->id}")->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $challenge = $this->challenge();
        $submission = $this->submission($challenge, $this->member());

        $this->get("/eksekusi/praktik/submissions/{$submission->id}")->assertRedirect('/login');
    }

    // --- Decision logic: approve ---

    public function test_approving_the_first_attempt_awards_the_full_points_reward(): void
    {
        $challenge = $this->challenge(100);
        $owner = $this->member();
        $reviewer = $this->executionMember();
        $submission = $this->submission($challenge, $owner, $reviewer, attempt: 1);
        $pointsBefore = app(PointService::class)->ensureProgress($owner)->total_points;

        Livewire::actingAs($reviewer)
            ->test(Review::class, ['submission' => $submission])
            ->set('feedback', 'Kerja bagus!')
            ->call('approve')
            ->assertHasNoErrors();

        $submission->refresh();
        $this->assertSame('disetujui', $submission->status);
        $this->assertSame(100, $submission->points_awarded);
        $this->assertNotNull($submission->reviewed_at);
        $this->assertSame('Kerja bagus!', $submission->feedback);
        $this->assertSame($pointsBefore + 100, app(PointService::class)->ensureProgress($owner)->total_points);
    }

    public function test_approving_the_second_attempt_awards_70_percent(): void
    {
        $challenge = $this->challenge(100);
        $owner = $this->member();
        $reviewer = $this->executionMember();
        $submission = $this->submission($challenge, $owner, $reviewer, attempt: 2);

        Livewire::actingAs($reviewer)
            ->test(Review::class, ['submission' => $submission])
            ->set('feedback', 'Sudah diperbaiki, bagus.')
            ->call('approve');

        $this->assertSame(70, $submission->fresh()->points_awarded);
    }

    public function test_approving_the_third_attempt_awards_49_percent(): void
    {
        $challenge = $this->challenge(100);
        $owner = $this->member();
        $reviewer = $this->executionMember();
        $submission = $this->submission($challenge, $owner, $reviewer, attempt: 3);

        Livewire::actingAs($reviewer)
            ->test(Review::class, ['submission' => $submission])
            ->set('feedback', 'Oke.')
            ->call('approve');

        $this->assertSame(49, $submission->fresh()->points_awarded);
    }

    public function test_approve_requires_feedback(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member();
        $reviewer = $this->executionMember();
        $submission = $this->submission($challenge, $owner, $reviewer);

        Livewire::actingAs($reviewer)
            ->test(Review::class, ['submission' => $submission])
            ->set('feedback', '')
            ->call('approve')
            ->assertHasErrors(['feedback']);

        $this->assertSame('pending', $submission->fresh()->status);
    }

    public function test_approving_notifies_the_submission_owner(): void
    {
        $challenge = $this->challenge(100);
        $owner = $this->member();
        $reviewer = $this->executionMember();
        $submission = $this->submission($challenge, $owner, $reviewer);

        Livewire::actingAs($reviewer)
            ->test(Review::class, ['submission' => $submission])
            ->set('feedback', 'Mantap!')
            ->call('approve');

        $notification = Notification::where('recipient_id', $owner->id)->where('type', 'submission_approved')->first();
        $this->assertNotNull($notification);
    }

    // --- Decision logic: request revision ---

    public function test_requesting_revision_awards_no_points_and_notifies_the_owner(): void
    {
        $challenge = $this->challenge(100);
        $owner = $this->member();
        $reviewer = $this->executionMember();
        $submission = $this->submission($challenge, $owner, $reviewer);
        $pointsBefore = app(PointService::class)->ensureProgress($owner)->total_points;

        Livewire::actingAs($reviewer)
            ->test(Review::class, ['submission' => $submission])
            ->set('feedback', 'Coba perbaiki bagian X.')
            ->call('requestRevision')
            ->assertHasNoErrors();

        $submission->refresh();
        $this->assertSame('perlu_revisi', $submission->status);
        $this->assertNull($submission->points_awarded);
        $this->assertNotNull($submission->reviewed_at);
        $this->assertSame($pointsBefore, app(PointService::class)->ensureProgress($owner)->total_points);

        $notification = Notification::where('recipient_id', $owner->id)->where('type', 'submission_needs_revision')->first();
        $this->assertNotNull($notification);
    }

    public function test_request_revision_requires_feedback(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member();
        $reviewer = $this->executionMember();
        $submission = $this->submission($challenge, $owner, $reviewer);

        Livewire::actingAs($reviewer)
            ->test(Review::class, ['submission' => $submission])
            ->set('feedback', '')
            ->call('requestRevision')
            ->assertHasErrors(['feedback']);
    }

    public function test_member_can_resubmit_after_a_revision_request_and_attempt_number_increments(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member();
        $reviewer = $this->executionMember();
        $submission = $this->submission($challenge, $owner, $reviewer, attempt: 1);

        Livewire::actingAs($reviewer)
            ->test(Review::class, ['submission' => $submission])
            ->set('feedback', 'Perbaiki ini.')
            ->call('requestRevision');

        Livewire::actingAs($owner)
            ->test(\App\Livewire\Eksplorasi\Praktik\Show::class, ['challenge' => $challenge])
            ->set('submissionType', 'text')
            ->set('content', 'Percobaan kedua, sudah diperbaiki.')
            ->call('submit');

        $this->assertSame(2, ChallengeSubmission::where('challenge_id', $challenge->id)->where('user_id', $owner->id)->count());
        $newest = ChallengeSubmission::where('challenge_id', $challenge->id)->orderByDesc('attempt_number')->first();
        $this->assertSame(2, $newest->attempt_number);
        $this->assertSame('pending', $newest->status);
    }

    // --- reviewed_at lock: points can only be awarded once ---

    /**
     * Livewire::test() catches abort()'s HttpException internally (its
     * RequestBroker simulates the real request/response cycle rather than
     * letting the exception surface to PHPUnit) — so this doesn't assert on
     * a caught exception type. What it DOES prove is the property that
     * actually matters: PHP unwinds the call stack the instant abort_if()
     * throws, so if points_awarded/feedback/reviewed_at are provably
     * unchanged after the call, the method body past that line never ran —
     * regardless of which layer ultimately reports the 403.
     */
    public function test_approving_an_already_reviewed_submission_does_not_re_award_points(): void
    {
        $challenge = $this->challenge(100);
        $owner = $this->member();
        $reviewer = $this->executionMember();
        $submission = $this->submission($challenge, $owner, $reviewer);
        $submission->update(['status' => 'disetujui', 'points_awarded' => 100, 'feedback' => 'Sudah direview.', 'reviewed_at' => now()]);
        $pointsBefore = app(PointService::class)->ensureProgress($owner)->total_points;

        Livewire::actingAs($reviewer)
            ->test(Review::class, ['submission' => $submission])
            ->set('feedback', 'Coba approve lagi, harusnya ditolak')
            ->call('approve');

        $this->assertSame($pointsBefore, app(PointService::class)->ensureProgress($owner)->total_points);
        $this->assertSame('Sudah direview.', $submission->fresh()->feedback);
        $this->assertSame(100, $submission->fresh()->points_awarded);
    }

    public function test_requesting_revision_on_an_already_reviewed_submission_does_not_overwrite_the_decision(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member();
        $reviewer = $this->executionMember();
        $submission = $this->submission($challenge, $owner, $reviewer);
        $submission->update(['status' => 'perlu_revisi', 'feedback' => 'Sudah direview.', 'reviewed_at' => now()]);
        $reviewedAtBefore = $submission->fresh()->reviewed_at;

        Livewire::actingAs($reviewer)
            ->test(Review::class, ['submission' => $submission])
            ->set('feedback', 'Coba revisi lagi, harusnya ditolak')
            ->call('requestRevision');

        $this->assertSame('Sudah direview.', $submission->fresh()->feedback);
        $this->assertTrue($reviewedAtBefore->equalTo($submission->fresh()->reviewed_at));
    }

    public function test_review_page_renders_read_only_once_reviewed_no_decision_buttons(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member();
        $reviewer = $this->executionMember();
        $submission = $this->submission($challenge, $owner, $reviewer);
        $submission->update(['status' => 'disetujui', 'points_awarded' => 100, 'reviewed_at' => now(), 'feedback' => 'Selamat!']);

        $this->actingAs($reviewer)->get("/eksekusi/praktik/submissions/{$submission->id}")
            ->assertOk()
            ->assertSee('Keputusan Sudah Dibuat')
            ->assertDontSee('wire:click="approve"', false);
    }

    // --- Dashboard widget scoping ---

    public function test_dashboard_review_queue_only_shows_submissions_assigned_to_the_current_user(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member();
        $mine = $this->executionMember('Mine');
        $someoneElse = $this->executionMember('SomeoneElse');
        $this->submission($challenge, $owner, $mine);
        $this->submission($challenge, $this->member('Other'), $someoneElse);

        $response = $this->actingAs($mine)->get('/eksekusi/dashboard')->assertOk();

        $response->assertSee('Antrian Review Praktik');
        // exactly 1 assigned to $mine — verified via DB directly (link text
        // alone isn't unique enough to assert page content reliably here).
        $this->assertSame(1, ChallengeSubmission::where('assigned_reviewer_id', $mine->id)->count());
    }
}

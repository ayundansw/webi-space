<?php

namespace Tests\Feature\Admin\Curriculum;

use App\Livewire\Admin\Curriculum\Submissions\Index;
use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Praktik 3 Bagian C: admin's "Antrian Review Praktik" queue — assigning a
 * pending submission to an execution_member reviewer. Enables the
 * long-disabled config/navigation.php admin slot of the same name.
 */
class SubmissionAssignmentTest extends TestCase
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

    private function pendingSubmission(): ChallengeSubmission
    {
        $challenge = Challenge::create([
            'title' => 'Challenge Uji', 'description' => 'D', 'level' => 'low',
            'points_reward' => 50, 'status' => 'published',
        ]);

        return ChallengeSubmission::create([
            'challenge_id' => $challenge->id, 'user_id' => $this->member()->id,
            'submission_type' => 'text', 'content' => 'Jawaban',
            'status' => 'pending', 'attempt_number' => 1,
        ]);
    }

    public function test_non_admin_cannot_access_the_submission_queue(): void
    {
        $exploration = $this->member();
        $execution = $this->executionMember();

        foreach ([$exploration, $execution] as $user) {
            $this->actingAs($user)->get('/admin/curriculum/submissions')->assertForbidden();
        }
    }

    public function test_admin_sees_pending_submissions_in_the_queue(): void
    {
        $admin = $this->admin();
        $submission = $this->pendingSubmission();

        $this->actingAs($admin)->get('/admin/curriculum/submissions')
            ->assertOk()
            ->assertSee($submission->challenge->title);
    }

    public function test_already_reviewed_submissions_do_not_appear_in_the_queue(): void
    {
        $admin = $this->admin();
        $submission = $this->pendingSubmission();
        $submission->update(['status' => 'disetujui', 'reviewed_at' => now()]);

        $this->actingAs($admin)->get('/admin/curriculum/submissions')
            ->assertOk()
            ->assertDontSee($submission->challenge->title);
    }

    public function test_admin_can_assign_a_reviewer_and_the_reviewer_is_notified(): void
    {
        $admin = $this->admin();
        $submission = $this->pendingSubmission();
        $reviewer = $this->executionMember();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('reviewerSelections.'.$submission->id, $reviewer->id)
            ->call('assignReviewer', $submission->id)
            ->assertHasNoErrors();

        $this->assertSame($reviewer->id, $submission->fresh()->assigned_reviewer_id);

        $notification = Notification::where('recipient_id', $reviewer->id)
            ->where('type', 'submission_assigned_to_reviewer')
            ->first();
        $this->assertNotNull($notification);
        $this->assertSame('challenge_submission', $notification->context_type);
        $this->assertSame($submission->id, $notification->context_id);
    }

    public function test_assigning_without_selecting_a_reviewer_fails_validation(): void
    {
        $admin = $this->admin();
        $submission = $this->pendingSubmission();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->call('assignReviewer', $submission->id)
            ->assertHasErrors(['reviewerSelections.'.$submission->id]);

        $this->assertNull($submission->fresh()->assigned_reviewer_id);
    }

    /**
     * Fase 8 Batch 6 (gap #1 dari Batch 2's audit): a Mode Ganda member
     * (exploration_member, approved + currently active_mode='execution' --
     * i.e. canAccessExecution() === true) must now show up as an
     * assignable reviewer candidate, exactly like a native execution_member.
     */
    public function test_the_reviewer_dropdown_includes_an_approved_and_active_dual_mode_member(): void
    {
        $admin = $this->admin();
        $this->pendingSubmission(); // dropdown only renders inside the per-submission @forelse
        $dualModeMember = User::create([
            'name' => 'Dual Mode Reviewer', 'email' => 'dualmode@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
            'dual_mode_status' => 'approved', 'active_mode' => 'execution',
        ]);

        $html = Livewire::actingAs($admin)->test(Index::class)->html();

        $this->assertStringContainsString('Dual Mode Reviewer', $html);
    }

    /**
     * Counterpart to the test above: an exploration_member who is approved
     * but NOT currently switched into execution mode (active_mode still
     * null, or approved-but-back-at-origin) does not pass canAccessExecution()
     * right now, so must NOT appear as a candidate yet.
     */
    public function test_the_reviewer_dropdown_excludes_an_approved_but_not_currently_active_dual_mode_member(): void
    {
        $admin = $this->admin();
        $this->pendingSubmission();
        User::create([
            'name' => 'Not Yet Switched', 'email' => 'notyet@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
            'dual_mode_status' => 'approved', 'active_mode' => null,
        ]);

        $html = Livewire::actingAs($admin)->test(Index::class)->html();

        $this->assertStringNotContainsString('Not Yet Switched', $html);
    }

    /**
     * Server-side guard proven directly (not just the dropdown source) --
     * a crafted assignReviewer() call naming a Mode Ganda member's id must
     * succeed exactly like it would for a native execution_member.
     */
    public function test_assigning_a_dual_mode_member_as_reviewer_works_end_to_end(): void
    {
        $admin = $this->admin();
        $submission = $this->pendingSubmission();
        $dualModeMember = User::create([
            'name' => 'Dual Mode Reviewer', 'email' => 'dualmode2@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
            'dual_mode_status' => 'approved', 'active_mode' => 'execution',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('reviewerSelections.'.$submission->id, $dualModeMember->id)
            ->call('assignReviewer', $submission->id)
            ->assertHasNoErrors();

        $this->assertSame($dualModeMember->id, $submission->fresh()->assigned_reviewer_id);
    }

    public function test_assigning_a_plain_exploration_member_who_cannot_access_execution_is_refused(): void
    {
        $admin = $this->admin();
        $submission = $this->pendingSubmission();
        $plainMember = $this->member('Plain Member');

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('reviewerSelections.'.$submission->id, $plainMember->id)
            ->call('assignReviewer', $submission->id)
            ->assertForbidden();
    }
}

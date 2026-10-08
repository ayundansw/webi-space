<?php

namespace Tests\Feature\Practice;

use App\Models\Challenge;
use App\Models\ChallengeStep;
use App\Models\ChallengeSubmission;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Implementasi 2 (Fase 5): murni verifikasi skema + relasi dasar, bukan
 * business logic (RBAC/review/poin dikerjakan di task-task setelah ini).
 */
class ChallengeSchemaTest extends TestCase
{
    use RefreshDatabase;

    private function challenge(): Challenge
    {
        return Challenge::create([
            'title' => 'Bikin Landing Page Statis',
            'description' => 'Deskripsi challenge uji.',
            'level' => 'low',
            'points_reward' => 50,
            'status' => 'published',
        ]);
    }

    private function user(string $role = 'exploration_member'): User
    {
        return User::create([
            'name' => 'User Uji',
            'email' => $role.'@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => $role,
            'membership_status' => 'active',
        ]);
    }

    public function test_challenge_steps_are_returned_in_order_via_the_challenge_relation(): void
    {
        $challenge = $this->challenge();

        ChallengeStep::create(['challenge_id' => $challenge->id, 'title' => 'Langkah Kedua', 'order_number' => 2]);
        ChallengeStep::create(['challenge_id' => $challenge->id, 'title' => 'Langkah Pertama', 'order_number' => 1]);

        $this->assertSame(
            ['Langkah Pertama', 'Langkah Kedua'],
            $challenge->fresh()->challengeSteps->pluck('title')->all(),
        );
    }

    public function test_challenge_step_content_blocks_reuse_the_same_polymorphic_relation_as_unit(): void
    {
        $challenge = $this->challenge();
        $step = ChallengeStep::create(['challenge_id' => $challenge->id, 'title' => 'Langkah 1', 'order_number' => 1]);

        $step->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Instruksi'], 'order' => 1]);

        $this->assertSame('challenge_step', $step->contentBlocks()->first()->blockable_type);
    }

    public function test_submission_belongs_to_challenge_user_and_optional_assigned_reviewer(): void
    {
        $challenge = $this->challenge();
        $member = $this->user('exploration_member');
        $reviewer = $this->user('execution_member');

        $submission = ChallengeSubmission::create([
            'challenge_id' => $challenge->id,
            'user_id' => $member->id,
            'submission_type' => 'link',
            'content' => 'https://example.test/demo',
            'status' => 'pending',
            'attempt_number' => 1,
            'assigned_reviewer_id' => $reviewer->id,
        ]);

        $this->assertTrue($submission->challenge->is($challenge));
        $this->assertTrue($submission->user->is($member));
        $this->assertTrue($submission->assignedReviewer->is($reviewer));
    }

    public function test_submission_assigned_reviewer_is_nullable_meaning_pic_reviews_directly(): void
    {
        $challenge = $this->challenge();
        $member = $this->user('exploration_member');

        $submission = ChallengeSubmission::create([
            'challenge_id' => $challenge->id,
            'user_id' => $member->id,
            'submission_type' => 'text',
            'content' => 'Penjelasan hasil.',
            'status' => 'pending',
            'attempt_number' => 1,
        ]);

        $this->assertNull($submission->assigned_reviewer_id);
        $this->assertNull($submission->assignedReviewer);
    }

    public function test_notifications_table_accepts_the_new_challenge_submission_context_and_types(): void
    {
        $recipient = $this->user('execution_member');
        $submission = ChallengeSubmission::create([
            'challenge_id' => $this->challenge()->id,
            'user_id' => $this->user('exploration_member')->id,
            'submission_type' => 'link',
            'content' => 'https://example.test',
            'status' => 'pending',
            'attempt_number' => 1,
        ]);

        $notification = Notification::create([
            'recipient_id' => $recipient->id,
            'context_type' => 'challenge_submission',
            'context_id' => $submission->id,
            'type' => 'submission_assigned_to_reviewer',
            'title' => 'Submission ditugaskan',
            'message' => 'Kamu ditugaskan mereview sebuah submission Praktik.',
        ]);

        $this->assertTrue($notification->fresh()->context->is($submission));
    }
}

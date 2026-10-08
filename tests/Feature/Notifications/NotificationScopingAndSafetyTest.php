<?php

namespace Tests\Feature\Notifications;

use App\Livewire\Notifications\Bell;
use App\Livewire\Notifications\Index;
use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use App\Models\Module;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Task 2.6b explicit requirements: (1) a user must never see another user's
 * notifications, in the bell, the full list, or via a direct mark-as-read
 * call; (2) a notification whose context row has been hard-deleted must
 * render safely with no active link, never a crash.
 */
class NotificationScopingAndSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $name): User
    {
        return User::create([
            'name' => $name, 'email' => strtolower($name).'@example.test',
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

    public function test_bell_never_shows_another_users_notifications_or_counts_them_in_the_badge(): void
    {
        $owner = $this->member('Agitsa');
        $other = $this->member('Bilal');

        Notification::create([
            'recipient_id' => $other->id, 'context_type' => 'none', 'context_id' => null,
            'type' => 'level_up', 'title' => 'Rahasia Bilal', 'message' => 'Punya Bilal doang.', 'is_read' => false,
        ]);

        $component = Livewire::actingAs($owner)->test(Bell::class);
        $component->assertDontSee('Rahasia Bilal');
        $this->assertStringNotContainsString('bg-accent px-1', $component->html(), 'Owner has zero notifications of their own, so no badge should render.');
    }

    public function test_full_list_page_never_shows_another_users_notifications(): void
    {
        $owner = $this->member('Agitsa');
        $other = $this->member('Bilal');

        Notification::create([
            'recipient_id' => $other->id, 'context_type' => 'none', 'context_id' => null,
            'type' => 'level_up', 'title' => 'Rahasia Bilal', 'message' => 'Punya Bilal doang.', 'is_read' => false,
        ]);

        Livewire::actingAs($owner)->test(Index::class)
            ->assertDontSee('Rahasia Bilal')
            ->assertSee('Belum ada notifikasi.');
    }

    public function test_mark_as_read_cannot_be_used_to_mark_another_users_notification(): void
    {
        $owner = $this->member('Agitsa');
        $attacker = $this->member('Bilal');

        $victimNotification = Notification::create([
            'recipient_id' => $owner->id, 'context_type' => 'none', 'context_id' => null,
            'type' => 'level_up', 'title' => 'Milik Agitsa', 'message' => 'Punya Agitsa.', 'is_read' => false,
        ]);

        // attacker tries to mark someone else's notification id as read via both surfaces
        Livewire::actingAs($attacker)->test(Bell::class)->call('markAsRead', $victimNotification->id);
        $this->assertFalse($victimNotification->fresh()->is_read, 'Bell::markAsRead must not let another user mutate this row.');

        Livewire::actingAs($attacker)->test(Index::class)->call('markAsRead', $victimNotification->id);
        $this->assertFalse($victimNotification->fresh()->is_read, 'Index::markAsRead must not let another user mutate this row.');
    }

    public function test_mark_all_as_read_only_affects_the_acting_users_own_notifications(): void
    {
        $owner = $this->member('Agitsa');
        $other = $this->member('Bilal');

        $othersNotification = Notification::create([
            'recipient_id' => $other->id, 'context_type' => 'none', 'context_id' => null,
            'type' => 'level_up', 'title' => 'Milik Bilal', 'message' => 'x', 'is_read' => false,
        ]);

        Livewire::actingAs($owner)->test(Index::class)->call('markAllAsRead');

        $this->assertFalse($othersNotification->fresh()->is_read);
    }

    public function test_notification_pointing_at_a_hard_deleted_task_renders_without_crashing_or_an_active_link(): void
    {
        $admin = $this->admin();
        $member = $this->member('Citra');

        $project = \App\Models\Project::create([
            'title' => 'Proyek', 'description' => 'D', 'objective' => 'T', 'project_type' => 'internal',
            'status' => 'active', 'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);
        $milestone = \App\Models\Milestone::create(['project_id' => $project->id, 'title' => 'M1', 'target_date' => '2026-07-15', 'sort_order' => 1]);
        $task = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Task Sementara',
            'description' => 'x', 'priority' => 'low', 'deadline' => '2026-07-20', 'status' => 'todo', 'created_by' => $admin->id,
        ]);

        $notification = Notification::create([
            'recipient_id' => $member->id, 'context_type' => 'task', 'context_id' => $task->id,
            'type' => 'task_assigned', 'title' => 'Task baru', 'message' => 'Kamu di-assign ke task ini.', 'is_read' => false,
        ]);

        $task->delete();

        $this->assertNull($notification->fresh()->linkUrl());

        $component = Livewire::actingAs($member)->test(Bell::class);
        $component->assertOk();
        $component->assertSee('Task baru');
    }

    public function test_notification_with_context_type_none_never_touches_the_unresolvable_morph_relation(): void
    {
        $member = $this->member('Dipa');

        $notification = Notification::create([
            'recipient_id' => $member->id, 'context_type' => 'none', 'context_id' => null,
            'type' => 'custom_reminder', 'title' => 'Pengingat', 'message' => 'x', 'is_read' => false,
        ]);

        $this->assertNull($notification->linkUrl());

        Livewire::actingAs($member)->test(Bell::class)->assertOk();
    }

    /**
     * Tahap B QA (2026-07-17): linkUrl() had no match arm for
     * 'challenge_submission' at all, so all 4 submission-lifecycle
     * notification types silently rendered with no link. Recipient-dependent:
     * the admin/reviewer-facing types go to the review page (which 403s an
     * exploration_member submitter), so the submitter-facing types must land
     * on their own challenge page instead.
     */
    public function test_challenge_submission_notifications_link_to_the_right_page_for_their_recipient(): void
    {
        $admin = $this->admin();
        $member = $this->member('Elang');

        $challenge = Challenge::create([
            'title' => 'Challenge Uji', 'description' => 'D', 'level' => 'low',
            'points_reward' => 50, 'status' => 'published',
        ]);
        $submission = ChallengeSubmission::create([
            'challenge_id' => $challenge->id, 'user_id' => $member->id,
            'submission_type' => 'text', 'content' => 'x', 'status' => 'pending', 'attempt_number' => 1,
        ]);

        $reviewUrl = url('/eksekusi/praktik/submissions/'.$submission->id);
        $challengeUrl = url('/eksplorasi/praktik/'.$challenge->id);

        foreach (['submission_received_alert', 'submission_assigned_to_reviewer'] as $type) {
            $notification = Notification::create([
                'recipient_id' => $admin->id, 'context_type' => 'challenge_submission', 'context_id' => $submission->id,
                'type' => $type, 'title' => 'x', 'message' => 'x', 'is_read' => false,
            ]);
            $this->assertSame($reviewUrl, $notification->linkUrl(), "type={$type} should link to the review page.");
        }

        foreach (['submission_approved', 'submission_needs_revision'] as $type) {
            $notification = Notification::create([
                'recipient_id' => $member->id, 'context_type' => 'challenge_submission', 'context_id' => $submission->id,
                'type' => $type, 'title' => 'x', 'message' => 'x', 'is_read' => false,
            ]);
            $this->assertSame($challengeUrl, $notification->linkUrl(), "type={$type} should link to the submitter's own challenge page, not the review page.");
        }
    }
}

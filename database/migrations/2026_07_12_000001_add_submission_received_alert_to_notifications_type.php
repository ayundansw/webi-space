<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Praktik 2: additive only. Adds the one new value needed for "a member just
 * submitted, admin needs to look at it and decide who reviews it" — distinct
 * from the 3 review-lifecycle values added earlier (submission_assigned_to_reviewer/
 * submission_approved/submission_needs_revision), which all fire AFTER an
 * admin has already acted. Named with the `_alert` suffix to match the
 * existing convention for "admin needs to look at this" notifications
 * (stalled_task_alert, inactive_member_alert, idea_created_alert).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', [
                'task_assigned',
                'task_reassigned_to',
                'task_reassigned_from',
                'task_deadline_approaching',
                'task_overdue',
                'task_status_to_review',
                'task_revision_needed',
                'comment_from_admin',
                'comment_on_my_task',
                'progress_update_received',
                'idea_status_changed',
                'added_to_project',
                'project_status_changed',
                'stalled_task_alert',
                'inactive_member_alert',
                'idea_created_alert',
                'checkpoint_completed',
                'level_up',
                'new_unit_unlocked',
                'evaluation_reminder',
                'forum_reply_received',
                'custom_reminder',
                'submission_assigned_to_reviewer',
                'submission_approved',
                'submission_needs_revision',
                'submission_received_alert',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', [
                'task_assigned',
                'task_reassigned_to',
                'task_reassigned_from',
                'task_deadline_approaching',
                'task_overdue',
                'task_status_to_review',
                'task_revision_needed',
                'comment_from_admin',
                'comment_on_my_task',
                'progress_update_received',
                'idea_status_changed',
                'added_to_project',
                'project_status_changed',
                'stalled_task_alert',
                'inactive_member_alert',
                'idea_created_alert',
                'checkpoint_completed',
                'level_up',
                'new_unit_unlocked',
                'evaluation_reminder',
                'forum_reply_received',
                'custom_reminder',
                'submission_assigned_to_reviewer',
                'submission_approved',
                'submission_needs_revision',
            ])->change();
        });
    }
};

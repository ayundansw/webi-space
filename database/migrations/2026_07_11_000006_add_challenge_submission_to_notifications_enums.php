<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only: adds the Praktik/Challenge-Submission values needed for
 * reviewer-assignment and review-result notifications. Every value that
 * existed before stays, in the same order, so no existing row's
 * context_type/type ever becomes invalid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('context_type', [
                'project', 'task', 'unit', 'checkpoint', 'module', 'forum_thread', 'challenge_submission', 'none',
            ])->change();

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
            ])->change();

            $table->enum('context_type', [
                'project', 'task', 'unit', 'checkpoint', 'module', 'forum_thread', 'none',
            ])->change();
        });
    }
};

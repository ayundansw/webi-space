<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 8 Batch 4: additive only, same pattern as the Praktik 1/2 migrations
 * that widened this exact enum before (e.g.
 * 2026_07_12_000001_add_submission_received_alert_to_notifications_type.php).
 * `dual_mode_request_alert` (admin-facing, broadcast to every admin, "_alert"
 * suffix matching the existing convention for that — stalled_task_alert,
 * inactive_member_alert, idea_created_alert, submission_received_alert) +
 * `dual_mode_approved`/`dual_mode_rejected`/`dual_mode_revoked` (member-facing,
 * one per DualModeService outcome).
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
                'dual_mode_request_alert',
                'dual_mode_approved',
                'dual_mode_rejected',
                'dual_mode_revoked',
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
                'submission_received_alert',
            ])->change();
        });
    }
};

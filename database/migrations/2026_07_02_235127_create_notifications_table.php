<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('recipient_id')->constrained('users')->restrictOnDelete();
            $table->enum('context_type', ['project', 'task', 'unit', 'checkpoint', 'module', 'forum_thread', 'none']);
            $table->uuid('context_id')->nullable();
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
            ]);
            $table->string('title');
            $table->string('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

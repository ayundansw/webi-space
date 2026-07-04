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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUuid('task_id')->nullable()->constrained('tasks')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('action_type', [
                'idea_created',
                'idea_approved',
                'idea_rejected',
                'project_created',
                'project_status_changed',
                'project_member_added',
                'project_member_removed',
                'milestone_created',
                'task_created',
                'task_status_changed',
                'task_assigned',
                'task_reassigned',
                'task_deadline_changed',
                'task_priority_changed',
                'task_deleted',
                'comment_added',
                'attachment_added',
                'progress_update_added',
            ]);
            $table->string('description');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

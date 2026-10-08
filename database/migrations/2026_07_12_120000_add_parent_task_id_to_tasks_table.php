<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 7 Batch 2a: subtask. Self-referencing, nullable — a Task with
 * parent_task_id set IS a subtask; existing rows are unaffected (all get
 * null, meaning "task besar", so every one of the 18 audited query sites
 * in RECON_fase7_manajemen_proyek.md keeps returning exactly what it did
 * before this migration for pre-existing data). nullOnDelete (not cascade):
 * deleting a parent task should not silently wipe out its subtasks' own
 * history/assignments — they become orphaned top-level tasks instead,
 * consistent with the "opsional FK" pattern used elsewhere in this app
 * (e.g. learning_resources.module_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignUuid('parent_task_id')->nullable()->after('milestone_id')
                ->constrained('tasks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['parent_task_id']);
            $table->dropColumn('parent_task_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 7 Batch 3a: task_id BERGANTUNG PADA depends_on_task_id (must finish
 * first). Anti-cycle validation is deliberately NOT a DB constraint — a
 * cyclic graph can't be caught by a simple CHECK/FK, it needs a graph
 * traversal, done in App\Services\Execution\TaskDependencyService BEFORE
 * a row is ever inserted (see that class's docblock for the algorithm).
 * unique(task_id, depends_on_task_id) only stops an exact duplicate row,
 * not a cycle — that's the service's job.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignUuid('depends_on_task_id')->constrained('tasks')->cascadeOnDelete();
            $table->unique(['task_id', 'depends_on_task_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};

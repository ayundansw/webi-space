<?php

namespace App\Services\Execution;

use App\Models\Task;
use App\Models\TaskDependency;
use Illuminate\Validation\ValidationException;

/**
 * Cycle prevention is application-level, not DB-level — a cyclic
 * dependency graph can't be caught by FK/CHECK constraints, only by
 * walking the graph, done here BEFORE a row is ever inserted.
 *
 * Deliberately does NOT write to activity_logs: `action_type` is a fixed
 * DB enum with no value fitting "dependency linked", and extending it is a
 * schema change needing explicit confirmation first. Skipped, not
 * forgotten.
 */
class TaskDependencyService
{
    public function addDependency(Task $task, Task $dependsOn): TaskDependency
    {
        if ($task->id === $dependsOn->id) {
            throw ValidationException::withMessages([
                'dependency' => 'Task tidak bisa bergantung pada dirinya sendiri.',
            ]);
        }

        if ($task->project_id !== $dependsOn->project_id) {
            throw ValidationException::withMessages([
                'dependency' => 'Dependency cuma boleh antar task dalam proyek yang sama.',
            ]);
        }

        if ($task->isSubtask() || $dependsOn->isSubtask()) {
            throw ValidationException::withMessages([
                'dependency' => 'Subtask tidak punya sistem dependency sendiri, cuma task besar yang bisa.',
            ]);
        }

        if (TaskDependency::where('task_id', $task->id)->where('depends_on_task_id', $dependsOn->id)->exists()) {
            throw ValidationException::withMessages([
                'dependency' => "Task '{$task->title}' sudah bergantung pada '{$dependsOn->title}'.",
            ]);
        }

        if ($this->wouldCreateCycle($task, $dependsOn)) {
            throw ValidationException::withMessages([
                'dependency' => "Tidak bisa: '{$task->title}' bergantung pada '{$dependsOn->title}' akan membuat siklus (salah satu task pada akhirnya bergantung pada dirinya sendiri secara tidak langsung).",
            ]);
        }

        return TaskDependency::create([
            'task_id' => $task->id,
            'depends_on_task_id' => $dependsOn->id,
        ]);
    }

    /**
     * Adding edge (task -> dependsOn) — meaning task requires dependsOn to
     * finish first — closes a cycle exactly when dependsOn ALREADY
     * (transitively) requires task to finish first. So: walk forward from
     * dependsOn along the existing "depends on" edges (dependsOn's own
     * dependencies, their dependencies, and so on) and check whether task
     * ever turns up in that reachable set. If it does, the new edge would
     * complete a loop (task -> dependsOn -> ... -> task).
     */
    public function wouldCreateCycle(Task $task, Task $dependsOn): bool
    {
        if ($task->id === $dependsOn->id) {
            return true;
        }

        $visited = [];
        $stack = [$dependsOn->id];

        while ($stack !== []) {
            $currentId = array_pop($stack);

            if ($currentId === $task->id) {
                return true;
            }

            if (isset($visited[$currentId])) {
                continue;
            }
            $visited[$currentId] = true;

            $nextIds = TaskDependency::where('task_id', $currentId)->pluck('depends_on_task_id');
            foreach ($nextIds as $nextId) {
                $stack[] = $nextId;
            }
        }

        return false;
    }
}

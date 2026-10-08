<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * task -> depends_on_task: `task` cannot start before `depends_on_task` is
 * done. See App\Services\Execution\TaskDependencyService for the rules
 * enforced BEFORE a row here is ever created (same project, task besar
 * only, no cycles).
 */
#[Fillable(['task_id', 'depends_on_task_id'])]
class TaskDependency extends Model
{
    use HasUuids;

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'depends_on_task_id');
    }
}

<?php

namespace App\Services\Execution;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class ActivityLogger
{
    public function log(
        Project $project,
        ?Task $task,
        User $actor,
        string $actionType,
        string $description,
        array $metadata = [],
    ): ActivityLog {
        return ActivityLog::create([
            'project_id' => $project->id,
            'task_id' => $task?->id,
            'user_id' => $actor->id,
            'action_type' => $actionType,
            'description' => $description,
            'metadata' => $metadata ?: null,
        ]);
    }
}

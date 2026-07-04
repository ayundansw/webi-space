<?php

namespace App\Services\Execution;

use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class Notifier
{
    public function send(
        User $recipient,
        string $type,
        string $title,
        string $message,
        Project|Task|null $context = null,
    ): Notification {
        return Notification::create([
            'recipient_id' => $recipient->id,
            'context_type' => $context ? ($context instanceof Task ? 'task' : 'project') : 'none',
            'context_id' => $context?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
        ]);
    }
}

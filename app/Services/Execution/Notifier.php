<?php

namespace App\Services\Execution;

use App\Models\ForumThread;
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
        // Fase 7 Batch 4: ForumThread added for Forum Proyek reply
        // notifications — same morph map entry ('forum_thread') Exploration\Notifier
        // already registers for Eksplorasi's own forum.
        Project|Task|ForumThread|null $context = null,
    ): Notification {
        $contextType = match (true) {
            $context instanceof Task => 'task',
            $context instanceof ForumThread => 'forum_thread',
            $context instanceof Project => 'project',
            default => 'none',
        };

        return Notification::create([
            'recipient_id' => $recipient->id,
            'context_type' => $contextType,
            'context_id' => $context?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
        ]);
    }
}

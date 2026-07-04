<?php

namespace App\Services\Exploration;

use App\Models\Checkpoint;
use App\Models\ForumThread;
use App\Models\Module;
use App\Models\Notification;
use App\Models\Unit;
use App\Models\User;

/**
 * Eksplorasi-side counterpart to App\Services\Execution\Notifier — same
 * shared `notifications` table and morph map (task 2.6 Batch 3: before this,
 * only Eksekusi ever wrote to this table, even though the schema/morph map
 * from 2.0 already reserved 'unit'/'checkpoint'/'module'/'forum_thread'
 * context types and the `type` enum already listed checkpoint_completed,
 * level_up, new_unit_unlocked, forum_reply_received — clear signs this
 * wiring was intended from the start, just never connected in 2.2/2.3).
 */
class Notifier
{
    public function send(
        User $recipient,
        string $type,
        string $title,
        string $message,
        Checkpoint|Unit|Module|ForumThread|null $context = null,
    ): Notification {
        $contextType = match (true) {
            $context instanceof Checkpoint => 'checkpoint',
            $context instanceof Unit => 'unit',
            $context instanceof Module => 'module',
            $context instanceof ForumThread => 'forum_thread',
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

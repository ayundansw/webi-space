<?php

namespace App\Services\Forum;

use App\Models\ForumReply;
use App\Models\ForumThread;
use App\Models\User;

/**
 * The ONE place a ForumThread/ForumReply row is ever written from — shared
 * by Eksplorasi's forum and Eksekusi's Forum Proyek + Forum General, no
 * duplicated logic. Deliberately thin: notification-sending stays in each
 * portal's own component (different Notifier services), RBAC/layout/UI
 * don't live here.
 *
 * `portal` is REQUIRED, not inferred from `project_id` — a thread with
 * `project_id = null` is genuinely ambiguous between Eksplorasi and Forum
 * General Eksekusi without it.
 */
class ForumService
{
    /**
     * @param  array{title: string, content: string, portal: string, module_id?: ?string, unit_id?: ?string, project_id?: ?string, target?: ?string}  $data
     */
    public function createThread(array $data, User $creator): ForumThread
    {
        return ForumThread::create([
            'module_id' => $data['module_id'] ?? null,
            'unit_id' => $data['unit_id'] ?? null,
            'project_id' => $data['project_id'] ?? null,
            'portal' => $data['portal'],
            'created_by' => $creator->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'target' => $data['target'] ?? null,
        ]);
    }

    public function addReply(ForumThread $thread, User $author, string $content): ForumReply
    {
        return ForumReply::create([
            'thread_id' => $thread->id,
            'user_id' => $author->id,
            'content' => $content,
        ]);
    }
}

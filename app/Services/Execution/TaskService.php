<?php

namespace App\Services\Execution;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Milestone;
use App\Models\ProgressUpdate;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class TaskService
{
    /**
     * Valid regardless of actor role; role restriction on top of this is
     * enforced separately (only admin may move to `done` or revert
     * `in_review` back to `in_progress`).
     */
    private const ALLOWED_TRANSITIONS = [
        'todo' => ['in_progress'],
        'in_progress' => ['in_review'],
        'in_review' => ['done', 'in_progress'],
        'done' => [],
    ];

    public function __construct(
        private ActivityLogger $logger,
        private Notifier $notifier,
    ) {}

    /**
     * @param  array<int, string>  $assigneeIds
     */
    public function create(Project $project, Milestone $milestone, User $creator, array $data, array $assigneeIds = []): Task
    {
        $task = Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'todo',
            'priority' => $data['priority'],
            'deadline' => $data['deadline'],
            'created_by' => $creator->id,
        ]);

        $this->logger->log(
            $project,
            $task,
            $creator,
            'task_created',
            "Task '{$task->title}' dibuat oleh {$creator->name}",
        );

        if ($project->status === 'planning') {
            $oldStatus = $project->status;
            $project->update(['status' => 'active']);

            $this->logger->log(
                $project,
                null,
                $creator,
                'project_status_changed',
                "Status proyek '{$project->title}' diubah dari {$oldStatus} ke active (task pertama dibuat)",
                ['old_status' => $oldStatus, 'new_status' => 'active'],
            );
        }

        foreach ($assigneeIds as $userId) {
            $assignee = User::findOrFail($userId);
            $this->assign($task, $assignee, $creator);
        }

        return $task;
    }

    public function assign(Task $task, User $assignee, User $assignedBy): TaskAssignment
    {
        $assignment = TaskAssignment::create([
            'task_id' => $task->id,
            'user_id' => $assignee->id,
            'assigned_by' => $assignedBy->id,
        ]);

        $this->logger->log(
            $task->project,
            $task,
            $assignedBy,
            'task_assigned',
            "Task '{$task->title}' di-assign ke {$assignee->name}",
        );

        $this->notifier->send(
            $assignee,
            'task_assigned',
            'Task baru di-assign ke kamu',
            "Kamu di-assign ke task '{$task->title}' di proyek '{$task->project->title}'.",
            $task,
        );

        return $assignment;
    }

    public function reassign(Task $task, User $from, User $to, User $admin): void
    {
        TaskAssignment::where('task_id', $task->id)->where('user_id', $from->id)->delete();

        $this->assign($task, $to, $admin);

        $this->logger->log(
            $task->project,
            $task,
            $admin,
            'task_reassigned',
            "Task '{$task->title}' di-re-assign dari {$from->name} ke {$to->name}",
        );

        $this->notifier->send(
            $from,
            'task_reassigned_from',
            'Task kamu dipindahkan',
            "Task '{$task->title}' yang sebelumnya di-assign ke kamu sudah dipindahkan ke {$to->name}.",
            $task,
        );

        $this->notifier->send(
            $to,
            'task_reassigned_to',
            'Task baru di-assign ke kamu',
            "Task '{$task->title}' di-re-assign ke kamu dari {$from->name}.",
            $task,
        );
    }

    public function changeStatus(Task $task, string $newStatus, User $actor): Task
    {
        $oldStatus = $task->status;
        $allowed = self::ALLOWED_TRANSITIONS[$oldStatus] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Tidak bisa mengubah status task dari {$oldStatus} ke {$newStatus}.",
            ]);
        }

        $adminOnlyTransition = $newStatus === 'done' || ($oldStatus === 'in_review' && $newStatus === 'in_progress');

        if ($adminOnlyTransition && $actor->role !== 'admin') {
            throw ValidationException::withMessages([
                'status' => 'Hanya admin yang bisa melakukan perubahan status ini.',
            ]);
        }

        if ($actor->role !== 'admin' && ! $task->assignments()->where('user_id', $actor->id)->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Kamu tidak di-assign ke task ini.',
            ]);
        }

        $task->update(['status' => $newStatus]);

        $this->logger->log(
            $task->project,
            $task,
            $actor,
            'task_status_changed',
            "{$actor->name} memindahkan task '{$task->title}' dari {$oldStatus} ke {$newStatus}",
            ['old_status' => $oldStatus, 'new_status' => $newStatus],
        );

        if ($newStatus === 'in_review') {
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $this->notifier->send(
                    $admin,
                    'task_status_to_review',
                    'Task minta di-review',
                    "{$actor->name} meminta review untuk task '{$task->title}'.",
                    $task,
                );
            }
        }

        if ($oldStatus === 'in_review' && $newStatus === 'in_progress') {
            foreach ($task->assignments()->with('user')->get() as $assignment) {
                $this->notifier->send(
                    $assignment->user,
                    'task_revision_needed',
                    'Task perlu revisi',
                    "Task '{$task->title}' perlu revisi. Cek komentar admin.",
                    $task,
                );
            }
        }

        return $task;
    }

    public function changeDeadline(Task $task, string $newDeadline, User $admin): Task
    {
        $old = $task->deadline;
        $task->update(['deadline' => $newDeadline]);

        $this->logger->log(
            $task->project,
            $task,
            $admin,
            'task_deadline_changed',
            "Deadline task '{$task->title}' diubah dari {$old->format('d M Y')} ke {$task->deadline->format('d M Y')}",
            ['old_deadline' => (string) $old, 'new_deadline' => $newDeadline],
        );

        return $task;
    }

    public function changePriority(Task $task, string $newPriority, User $admin): Task
    {
        $old = $task->priority;
        $task->update(['priority' => $newPriority]);

        $this->logger->log(
            $task->project,
            $task,
            $admin,
            'task_priority_changed',
            "Prioritas task '{$task->title}' diubah dari {$old} ke {$newPriority}",
            ['old_priority' => $old, 'new_priority' => $newPriority],
        );

        return $task;
    }

    public function addComment(Task $task, User $author, string $content): Comment
    {
        $comment = Comment::create([
            'task_id' => $task->id,
            'user_id' => $author->id,
            'content' => $content,
        ]);

        $this->logger->log(
            $task->project,
            $task,
            $author,
            'comment_added',
            "{$author->name} menambahkan komentar di task '{$task->title}'",
        );

        $recipients = $task->assignments()->with('user')->get()
            ->pluck('user')
            ->filter(fn (User $u) => $u->id !== $author->id);

        /*
         * Lampiran B (docs/struktur-eksekusi.md) defines two notification types
         * that both fire on the same "comment added" event: `comment_from_admin`
         * (trigger: admin comments) and `comment_on_my_task` (trigger: "siapapun"
         * comments — which already includes admin). Sending both for a single
         * admin comment would double-notify the same assignee for one event.
         * Resolved by preferring the more specific `comment_from_admin` when the
         * author is admin, `comment_on_my_task` otherwise — flagged in the final
         * report as a doc ambiguity, not assumed silently correct.
         */
        foreach ($recipients as $recipient) {
            if ($author->role === 'admin') {
                $this->notifier->send(
                    $recipient,
                    'comment_from_admin',
                    'Komentar baru dari admin',
                    "Admin menambahkan komentar di task '{$task->title}'.",
                    $task,
                );
            } else {
                $this->notifier->send(
                    $recipient,
                    'comment_on_my_task',
                    'Komentar baru di task kamu',
                    "{$author->name} berkomentar di task '{$task->title}'.",
                    $task,
                );
            }
        }

        return $comment;
    }

    public function addAttachmentFile(Task $task, User $uploader, UploadedFile $file): Attachment
    {
        // Task 2.9: files write to the private 'attachments' disk
        // (storage/app/private, never web-accessible directly) instead of the
        // 'storage_files' disk used briefly in task 2.8. This is its OWN disk,
        // never the shared 'local' disk — Livewire's temporary file upload
        // mechanism also defaults to 'local', and an earlier version of this
        // fix that repointed 'local' itself broke every file upload in the
        // app in production. See config/filesystems.php's 'attachments' disk
        // comment. `file_url` now holds a relative disk path, not a
        // resolvable public URL — access is only ever through
        // AttachmentDownloadController (auth + project-membership checked,
        // same rule as Tasks\Show::mount()), never a raw static file request.
        $path = $file->store('attachments', 'attachments');

        $attachment = Attachment::create([
            'task_id' => $task->id,
            'uploaded_by' => $uploader->id,
            'file_name' => $file->getClientOriginalName(),
            'file_url' => $path,
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
        ]);

        $this->logAttachmentAdded($task, $uploader);

        return $attachment;
    }

    /**
     * docs/struktur-eksekusi.md 3.10: Attachment can also be an external link
     * instead of a file upload (three variants total: file, link, text) —
     * file_url holds the URL, file_name holds the label, file_type is
     * literally "link", file_size is null.
     */
    public function addAttachmentLink(Task $task, User $uploader, string $url, string $label): Attachment
    {
        $attachment = Attachment::create([
            'task_id' => $task->id,
            'uploaded_by' => $uploader->id,
            'file_name' => $label,
            'file_url' => $url,
            'file_type' => 'link',
            'file_size' => null,
        ]);

        $this->logAttachmentAdded($task, $uploader);

        return $attachment;
    }

    /**
     * Third Attachment variant confirmed by the user as a genuine tahap-1.2
     * decision (2026-07-03): a plain-text note, not a file or a link.
     * file_url holds the note body (widened to `text` via the 2026_07_03
     * migration — the varchar(255) it used to be would silently truncate a
     * long note), file_name holds a short label, file_type is literally
     * "text", file_size is null (nothing to size).
     */
    public function addAttachmentText(Task $task, User $uploader, string $content, string $label): Attachment
    {
        $attachment = Attachment::create([
            'task_id' => $task->id,
            'uploaded_by' => $uploader->id,
            'file_name' => $label,
            'file_url' => $content,
            'file_type' => 'text',
            'file_size' => null,
        ]);

        $this->logAttachmentAdded($task, $uploader);

        return $attachment;
    }

    private function logAttachmentAdded(Task $task, User $uploader): void
    {
        $this->logger->log(
            $task->project,
            $task,
            $uploader,
            'attachment_added',
            "{$uploader->name} menambahkan attachment di task '{$task->title}'",
        );
    }

    public function addProgressUpdate(Task $task, User $author, string $content, ?string $attachmentUrl = null): ProgressUpdate
    {
        $update = ProgressUpdate::create([
            'task_id' => $task->id,
            'user_id' => $author->id,
            'content' => $content,
            'attachment_url' => $attachmentUrl,
        ]);

        $this->logger->log(
            $task->project,
            $task,
            $author,
            'progress_update_added',
            "Progress update ditambahkan oleh {$author->name} di task '{$task->title}'",
        );

        foreach (User::where('role', 'admin')->get() as $admin) {
            $this->notifier->send(
                $admin,
                'progress_update_received',
                'Progress update baru',
                "{$author->name} mengirim progress update di task '{$task->title}'.",
                $task,
            );
        }

        return $update;
    }

    public function delete(Task $task, User $admin): void
    {
        // Logged against the project (task_id null) so the audit entry survives
        // the task's own deletion — activity_logs.task_id cascades on delete.
        $this->logger->log(
            $task->project,
            null,
            $admin,
            'task_deleted',
            "Task '{$task->title}' dihapus oleh admin",
        );

        $task->delete();
    }
}

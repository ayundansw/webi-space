<?php

namespace App\Services\Execution;

use App\Models\ActivityLog;
use App\Models\Milestone;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProgressUpdate;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Computes the six automatic monitoring flags from docs/struktur-eksekusi.md
 * Bagian 5.2. Business rule 5.14: on_hold projects suppress all deadline
 * notifications and alert flags for that project — every method here
 * excludes tasks/milestones belonging to on_hold projects.
 */
class AlertService
{
    public function __construct(
        private Notifier $notifier,
    ) {}

    public function overdueTasks(): Collection
    {
        return Task::query()
            ->whereHas('project', fn ($q) => $q->where('status', '!=', 'on_hold'))
            ->where('status', '!=', 'done')
            ->where('deadline', '<', now()->toDateString())
            ->with(['project', 'assignments.user'])
            ->get();
    }

    public function dueSoonTasks(): Collection
    {
        $threshold = now()->addDays(config('execution.due_soon_days'))->toDateString();

        return Task::query()
            ->whereHas('project', fn ($q) => $q->where('status', '!=', 'on_hold'))
            ->where('status', '!=', 'done')
            ->whereBetween('deadline', [now()->toDateString(), $threshold])
            ->with(['project', 'assignments.user'])
            ->get();
    }

    public function stalledTasks(): Collection
    {
        $cutoff = now()->subDays(config('execution.stalled_days'));

        return Task::query()
            ->whereHas('project', fn ($q) => $q->where('status', '!=', 'on_hold'))
            ->where('status', 'in_progress')
            ->with(['project', 'assignments.user'])
            ->get()
            ->filter(function (Task $task) use ($cutoff) {
                $lastActivity = $this->lastActivityAt($task);

                return $lastActivity === null || $lastActivity->lt($cutoff);
            })
            ->values();
    }

    public function inactiveMembers(): Collection
    {
        $cutoff = now()->subDays(config('execution.inactive_member_days'));

        return User::where('role', 'execution_member')
            ->get()
            ->filter(function (User $member) use ($cutoff) {
                $activeTasks = Task::whereHas('assignments', fn ($q) => $q->where('user_id', $member->id))
                    ->whereIn('status', ['todo', 'in_progress'])
                    ->whereHas('project', fn ($q) => $q->where('status', '!=', 'on_hold'))
                    ->get();

                if ($activeTasks->isEmpty()) {
                    return false;
                }

                $lastActivity = $activeTasks->map(fn (Task $task) => $this->lastActivityAt($task, $member))->max();

                return $lastActivity === null || $lastActivity->lt($cutoff);
            })
            ->values();
    }

    public function milestonesAtRisk(): Collection
    {
        return Milestone::query()
            ->whereHas('project', fn ($q) => $q->where('status', '!=', 'on_hold'))
            ->where('target_date', '<', now()->toDateString())
            ->with('project')
            ->get()
            ->filter(fn (Milestone $milestone) => $milestone->tasks()->where('status', '!=', 'done')->exists())
            ->values();
    }

    public function idleProjects(): Collection
    {
        $cutoff = now()->subDays(config('execution.project_idle_days'));

        return Project::where('status', 'active')
            ->get()
            ->filter(function (Project $project) use ($cutoff) {
                $lastLog = ActivityLog::where('project_id', $project->id)->max('created_at');

                return $lastLog === null || $lastLog < $cutoff;
            })
            ->values();
    }

    /**
     * Sends the "pertama kali muncul" admin-facing alert notifications
     * (stalled_task_alert, inactive_member_alert) exactly once per
     * task/member — deduplicated by checking whether an identical
     * notification (same type + context) was already ever sent, since there
     * is no separate "flag state" table to track resolution/re-triggering.
     */
    public function notifyNewAlerts(): void
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($this->stalledTasks() as $task) {
            $this->notifyOnceForContext($admins, 'stalled_task_alert', $task, function (User $admin) use ($task) {
                $this->notifier->send(
                    $admin,
                    'stalled_task_alert',
                    'Task terdeteksi stalled',
                    "Task '{$task->title}' tidak ada aktivitas selama ".config('execution.stalled_days').' hari.',
                    $task,
                );
            });
        }

        foreach ($this->overdueTasks() as $task) {
            $recipients = $task->assignments->pluck('user')->push(...$admins);

            $this->notifyOnceForContext($recipients, 'task_overdue', $task, function (User $recipient) use ($task) {
                $this->notifier->send(
                    $recipient,
                    'task_overdue',
                    'Task melewati deadline',
                    "Task '{$task->title}' sudah melewati deadline ({$task->deadline->format('d M Y')}).",
                    $task,
                );
            });
        }

        foreach ($this->dueSoonTasks() as $task) {
            foreach ($task->assignments as $assignment) {
                $this->notifyOnceForContext(collect([$assignment->user]), 'task_deadline_approaching', $task, function (User $recipient) use ($task) {
                    $this->notifier->send(
                        $recipient,
                        'task_deadline_approaching',
                        'Deadline task mendekat',
                        "Deadline task '{$task->title}' tinggal sebentar lagi ({$task->deadline->format('d M Y')}).",
                        $task,
                    );
                });
            }
        }

        foreach ($this->inactiveMembers() as $member) {
            $this->notifyMemberAlertOnce($admins, $member);
        }
    }

    private function notifyOnceForContext(Collection $recipients, string $type, Task $task, \Closure $send): void
    {
        foreach ($recipients as $recipient) {
            $alreadySent = Notification::where('recipient_id', $recipient->id)
                ->where('type', $type)
                ->where('context_type', 'task')
                ->where('context_id', $task->id)
                ->exists();

            if (! $alreadySent) {
                $send($recipient);
            }
        }
    }

    private function notifyMemberAlertOnce(Collection $admins, User $member): void
    {
        foreach ($admins as $admin) {
            $alreadySent = Notification::where('recipient_id', $admin->id)
                ->where('type', 'inactive_member_alert')
                ->where('message', 'like', "%{$member->name}%")
                ->exists();

            if (! $alreadySent) {
                $this->notifier->send(
                    $admin,
                    'inactive_member_alert',
                    'Anggota tidak aktif',
                    "{$member->name} tidak ada aktivitas di semua task selama ".config('execution.inactive_member_days').' hari.',
                );
            }
        }
    }

    private function lastActivityAt(Task $task, ?User $onlyBy = null): ?\Illuminate\Support\Carbon
    {
        $logQuery = ActivityLog::where('task_id', $task->id);
        $updateQuery = ProgressUpdate::where('task_id', $task->id);

        if ($onlyBy) {
            $logQuery->where('user_id', $onlyBy->id);
            $updateQuery->where('user_id', $onlyBy->id);
        }

        $lastLog = $logQuery->max('created_at');
        $lastUpdate = $updateQuery->max('created_at');

        $timestamps = collect([$lastLog, $lastUpdate])->filter()->map(fn ($t) => \Illuminate\Support\Carbon::parse($t));

        return $timestamps->isEmpty() ? null : $timestamps->max();
    }
}

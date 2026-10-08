<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title', 'description', 'objective', 'project_type', 'status',
    'originated_from_idea_id', 'start_date', 'target_end_date', 'actual_end_date', 'created_by',
])]
class Project extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'target_end_date' => 'date',
            'actual_end_date' => 'date',
        ];
    }

    public function originatedFromIdea(): BelongsTo
    {
        return $this->belongsTo(ProjectIdea::class, 'originated_from_idea_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    /**
     * Fase 7 Batch 4: "Forum Proyek" — ForumThread rows scoped to this
     * project (project_id set). See ForumThread's own docblock.
     */
    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }

    /**
     * Derived, not stored: percentage of this project's tasks that are
     * `done`. Fase 7 Batch 2a: excludes subtasks (whereNull parent_task_id)
     * — this is a task-besar rollup metric, same audited decision as
     * Milestone::progressPercentage() and the Kanban board.
     */
    public function progressPercentage(): int
    {
        $total = $this->tasks()->whereNull('parent_task_id')->count();

        if ($total === 0) {
            return 0;
        }

        $done = $this->tasks()->whereNull('parent_task_id')->where('status', 'done')->count();

        return (int) round($done / $total * 100);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'title', 'description', 'target_date', 'sort_order'])]
class Milestone extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Derived, not stored (docs/v_2.0/archive/sumber-konsolidasi/struktur-eksekusi.md 3.5): percentage of this
     * milestone's tasks that are `done`, always computed fresh. Fase 7
     * Batch 2a: excludes subtasks (whereNull parent_task_id) — subtasks
     * inherit their parent's milestone_id, so counting them here would
     * double up progress against the same underlying work.
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Acara" only (docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Modul_Manajemen_Proyek_v2.md
 * §Kalender) — manually entered events. "Kegiatan" (task deadlines,
 * milestones) is never stored here, see App\Services\Execution\CalendarService.
 * project_id null = personal/general event, not tied to a specific project.
 */
#[Fillable(['project_id', 'created_by', 'title', 'description', 'start_at', 'end_at', 'type'])]
class CalendarEvent extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

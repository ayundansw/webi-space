<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['module_id', 'unit_id', 'project_id', 'portal', 'created_by', 'title', 'content', 'target'])]
class ForumThread extends Model
{
    use HasUuids;

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Fase 7 Batch 4: set = an Eksekusi thread — "Forum Proyek" for this
     * specific project. null = either an Eksplorasi thread OR Forum
     * General Eksekusi (both have project_id null) — `portal` (added by
     * the Forum General migration, "utang Fase 7") is what actually tells
     * those two apart now; project_id alone is no longer sufficient and
     * must never be used as the sole discriminator in a query again.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ForumReply::class, 'thread_id');
    }
}

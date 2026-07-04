<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['title', 'description', 'purpose', 'proposed_by', 'status', 'rejection_reason', 'promoted_to_project_id'])]
class ProjectIdea extends Model
{
    use HasUuids;

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function promotedToProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'promoted_to_project_id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class, 'originated_from_idea_id');
    }
}

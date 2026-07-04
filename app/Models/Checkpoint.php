<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['module_id', 'checklist_items', 'intermezo_questions'])]
class Checkpoint extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'checklist_items' => 'array',
            'intermezo_questions' => 'array',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(CheckpointCompletion::class);
    }
}

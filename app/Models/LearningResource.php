<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['module_id', 'title', 'url', 'source_name'])]
class LearningResource extends Model
{
    use HasUuids;

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}

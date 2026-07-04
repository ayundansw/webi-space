<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'current_level', 'level_name', 'total_points', 'current_unit_id'])]
class UserExplorationProgress extends Model
{
    use HasUuids;

    const CREATED_AT = null;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'current_unit_id');
    }
}

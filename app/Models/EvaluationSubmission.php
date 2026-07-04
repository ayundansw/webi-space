<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'unit_id', 'answers', 'is_correct', 'points_awarded'])]
class EvaluationSubmission extends Model
{
    use HasUuids;

    const CREATED_AT = 'submitted_at';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'is_correct' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}

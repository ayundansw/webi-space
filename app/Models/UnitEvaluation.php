<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['unit_id', 'question_type', 'question_text', 'options', 'correct_answer', 'sort_order'])]
class UnitEvaluation extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_answer' => 'array',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}

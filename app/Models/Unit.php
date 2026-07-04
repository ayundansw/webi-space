<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'module_id', 'order_number', 'title', 'content', 'estimated_minutes',
    'unit_type', 'point_value', 'evaluation_type', 'prerequisite_unit_id',
])]
class Unit extends Model
{
    use HasUuids;

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function prerequisiteUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'prerequisite_unit_id');
    }

    public function dependentUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'prerequisite_unit_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(UnitEvaluation::class);
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserUnitProgress::class);
    }

    public function evaluationSubmissions(): HasMany
    {
        return $this->hasMany(EvaluationSubmission::class);
    }

    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'unit_context');
    }
}

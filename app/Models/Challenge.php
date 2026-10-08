<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'level', 'points_reward', 'status'])]
class Challenge extends Model
{
    use HasUuids;

    public function challengeSteps(): HasMany
    {
        return $this->hasMany(ChallengeStep::class)->orderBy('order_number');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ChallengeSubmission::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ChallengeAttachment::class)->latest();
    }
}

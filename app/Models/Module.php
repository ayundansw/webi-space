<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['order_number', 'title', 'description', 'level_number'])]
class Module extends Model
{
    use HasUuids;

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function checkpoint(): HasOne
    {
        return $this->hasOne(Checkpoint::class);
    }

    public function learningResources(): HasMany
    {
        return $this->hasMany(LearningResource::class);
    }

    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }
}

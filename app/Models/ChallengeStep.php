<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['challenge_id', 'title', 'order_number'])]
class ChallengeStep extends Model
{
    use HasUuids;

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    /**
     * Reuses the same polymorphic content_blocks table/renderer as
     * Unit::contentBlocks() (2.2.4a) — track-map guidance is composed of the
     * same heading/text/callout/code/etc. blocks used for Materi.
     */
    public function contentBlocks(): MorphMany
    {
        return $this->morphMany(ContentBlock::class, 'blockable')->orderBy('order');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'checkpoint_id', 'checklist_answers', 'intermezo_answers', 'form_tanggapan', 'points_awarded'])]
class CheckpointCompletion extends Model
{
    use HasUuids;

    const CREATED_AT = 'completed_at';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'checklist_answers' => 'array',
            'intermezo_answers' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class);
    }
}

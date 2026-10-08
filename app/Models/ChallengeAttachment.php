<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['challenge_id', 'file_name', 'file_path', 'file_size', 'uploaded_by'])]
class ChallengeAttachment extends Model
{
    use HasUuids;

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

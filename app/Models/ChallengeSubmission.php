<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'challenge_id', 'user_id', 'submission_type', 'content', 'status',
    'feedback', 'attempt_number', 'points_awarded', 'assigned_reviewer_id', 'reviewed_at',
    'file_name', 'file_path', 'file_size',
])]
class ChallengeSubmission extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Null means the PIC (admin) reviews it directly — no execution_member
     * delegate assigned. See docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Modul_Praktik_v2.md §5.
     */
    public function assignedReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_reviewer_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fase 8 Batch 1: one row per request to gain "Mode Eksekusi" access
 * (docs/v_2.0/archive/sumber-konsolidasi/RANCANGAN_FINAL_WEBI-SPACE_v2.md §2.2.B — exploration_member
 * origin only). Audit trail of every request a user has ever made; the
 * user's CURRENT effective grant lives on User.dual_mode_status instead
 * (see that migration's docblock for why it's a separate column). No
 * Gate/access-check logic reads this model yet — that's a later batch.
 */
#[Fillable(['user_id', 'status', 'reviewed_by', 'reviewed_at', 'note'])]
class DualModeRequest extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

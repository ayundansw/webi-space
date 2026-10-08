<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One block of structured content (docs/v_2.0/archive/sumber-konsolidasi/content-blocks-spec.md).
 * Polymorphic on purpose (`blockable`) so the same table/renderer serves Unit
 * materi now and ChallengeStep (Praktik track map, 2.2.6) later — see
 * docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Arsitektur_Konten_Dinamis_v2.md §3.
 */
#[Fillable(['blockable_type', 'blockable_id', 'type', 'content', 'order'])]
class ContentBlock extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }

    public function blockable(): MorphTo
    {
        return $this->morphTo();
    }
}

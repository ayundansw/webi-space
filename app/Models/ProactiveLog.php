<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'trigger_type', 'responded', 'responded_at'])]
class ProactiveLog extends Model
{
    use HasUuids;

    const CREATED_AT = 'sent_at';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'responded' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

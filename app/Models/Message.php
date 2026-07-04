<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['conversation_id', 'sender', 'content', 'unit_context', 'voice_mode'])]
class Message extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'voice_mode' => 'boolean',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function unitContext(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_context');
    }

    public function guardrailFlags(): HasMany
    {
        return $this->hasMany(GuardrailFlag::class);
    }
}

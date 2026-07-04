<?php

namespace App\Services\Webi;

use App\Models\User;
use App\Models\UserUnitProgress;

/**
 * Builds the [USER_CONTEXT] block (docs/spesifikasi-webi.md 5.1, 2.1a) — WEBI
 * reads this data, never writes to it (docs/PRD.md 5.9).
 */
class PersonalizationContextBuilder
{
    public function build(User $user, bool $voiceMode): string
    {
        $progress = $user->explorationProgress;

        $currentUnitTitle = $progress?->currentUnit?->title ?? '(belum mulai unit manapun)';
        $level = $progress?->current_level ?? 1;
        $levelName = $progress?->level_name ?? config('exploration.level_names')[1];
        $points = $progress?->total_points ?? 0;

        $completedUnits = UserUnitProgress::with('unit')
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->get()
            ->pluck('unit.title')
            ->filter()
            ->implode(', ');

        $interestField = $user->interest_field ? implode(', ', $user->interest_field) : '(belum diisi)';

        return <<<TEXT
[USER_CONTEXT]
- user_id: {$user->id}
- name: {$user->name}
- current_level: {$level} ({$levelName})
- total_points: {$points}
- current_unit: {$currentUnitTitle}
- completed_units: [{$completedUnits}]
- interest_field: {$interestField}
- voice_mode: {$this->boolText($voiceMode)}
TEXT;
    }

    private function boolText(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}

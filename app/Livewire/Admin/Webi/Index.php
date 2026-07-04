<?php

namespace App\Livewire\Admin\Webi;

use App\Models\GuardrailFlag;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * docs/spesifikasi-webi.md 5.3 / docs/PRD.md 3.2.9 (Eksekusi's dashboard) and
 * 3.0.3 ("Akses ke log percakapan WEBI dan guardrail flag") — per-member
 * summary for admin monitoring. Reached both directly (`/admin/webi`) and via
 * the "Lihat semua log" quick-access link on the unified `/admin/dashboard`
 * (task 2.6 Batch 2) — this full table stays a separate page rather than
 * being inlined wholesale, since the unified dashboard only needs a summary
 * card, not the full per-member breakdown.
 */
#[Layout('components.layouts.app')]
#[Title('Log Percakapan WEBI')]
class Index extends Component
{
    public function render()
    {
        $members = User::where('role', 'exploration_member')->orderBy('name')->get();

        $summaries = $members->map(function (User $user) {
            $conversationIds = $user->conversations()->pluck('id');

            $messageCount = Message::whereIn('conversation_id', $conversationIds)->count();
            $lastMessageAtRaw = Message::whereIn('conversation_id', $conversationIds)->max('created_at');
            $lastMessageAt = $lastMessageAtRaw ? Carbon::parse($lastMessageAtRaw) : null;
            $flagCount = GuardrailFlag::whereHas('message', fn ($q) => $q->whereIn('conversation_id', $conversationIds))->count();

            return [
                'user' => $user,
                'message_count' => $messageCount,
                'last_message_at' => $lastMessageAt,
                'flag_count' => $flagCount,
            ];
        });

        return view('livewire.admin.webi.index', ['summaries' => $summaries]);
    }
}

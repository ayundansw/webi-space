<?php

namespace App\Services\Webi;

use App\Models\CheckpointCompletion;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Module;
use App\Models\ProactiveLog;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserUnitProgress;
use Illuminate\Support\Carbon;

/**
 * Proactive greetings (docs/spesifikasi-webi.md 3.2, Mode B). Checked when the
 * user opens the WEBI chat page — the doc itself frames delivery as "muncul di
 * antarmuka chat saat user membuka WEBI-SPACE, bukan sebagai push notification
 * eksternal" (3.2), so there's no separate background scheduler; this runs
 * synchronously from `Chat::mount()`.
 *
 * Schema note: ProactiveLog has no payload field to record WHICH level or
 * checkpoint a level_up/checkpoint message already celebrated (docs/arsitektur-
 * database.md and the 2.0 migration only store trigger_type + sent_at/responded).
 * So "already celebrated" is inferred by COUNT: if the user has reached level 3
 * and only 1 level_up log exists, one level-up is still uncelebrated. This is a
 * schema-driven approximation, flagged in the task 2.5 report — same category of
 * issue as the Eksekusi module's existing "alert first-occurrence" gap
 * (AlertService::notifyOnceForContext).
 *
 * Overlap check against 2.2's `App\Services\Exploration\ProgressService::feedFor()`
 * (checked 2026-07-04, per explicit user request):
 * - Trigger 4 (level_up): NO overlap. feedFor() only ever emits unit-completion
 *   and checkpoint-completion entries — it has no level-transition event at all
 *   (the Eksplorasi dashboard shows current_level only as a static stat badge,
 *   never as a "you just leveled up" feed entry). Safe as-is.
 * - Trigger 5 (checkpoint): REAL overlap. feedFor() already emits "Modul {title}
 *   tuntas! Kamu dapat {points} poin bonus checkpoint..." for the exact same
 *   CheckpointCompletion row. Kept both (Trigger 5 is explicitly required by
 *   docs/spesifikasi-webi.md 3.2), but they're differentiated on purpose:
 *     - Channel: feedFor() is a PASSIVE list on the Eksplorasi dashboard, only
 *       seen if the user visits that page. Trigger 5 is an ACTIVE proactive
 *       message injected into the WEBI chat the next time it's opened.
 *     - Framing: checkpointMessage() below deliberately does NOT lead with
 *       "Modul X selesai!" (which would just restate what the dashboard feed
 *       already announced) — it leads as WEBI reacting to something it noticed,
 *       and its real added value is the forward-looking next-module preview +
 *       invitation to keep talking, which the passive feed can't offer.
 *     - Scope: feedFor() logs EVERY unit AND every checkpoint (dozens of
 *       entries); Trigger 5 only ever fires for checkpoints (9 total), so it's
 *       reserved for bigger milestones, not routine unit completions.
 */
class ProactiveService
{
    // L1: Modul 1-2, L2: Modul 3, L3: Modul 4-5, L4: Modul 6-7, L5: Modul 8-9, L6: Modul 10
    // per docs/kurikulum-eksplorasi.md's level-to-module mapping (confirmed in task 2.3).
    private const LEVEL_MODULE_RANGES = [
        1 => [1, 2],
        2 => [3, 3],
        3 => [4, 5],
        4 => [6, 7],
        5 => [8, 9],
        6 => [10, 10],
    ];

    public function checkAndDeliver(User $user, Conversation $conversation): ?Message
    {
        $this->reconcileResponses($user);

        $trigger = $this->determineTrigger($user);

        if (! $trigger) {
            return null;
        }

        [$type, $text] = $trigger;

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender' => 'webi',
            'content' => $text,
        ]);

        ProactiveLog::create([
            'user_id' => $user->id,
            'trigger_type' => $type,
        ]);

        $conversation->touch();

        return $message;
    }

    /**
     * A user message sent after a proactive log counts as "responded" (5.2).
     */
    private function reconcileResponses(User $user): void
    {
        ProactiveLog::where('user_id', $user->id)
            ->where('responded', false)
            ->get()
            ->each(function (ProactiveLog $log) use ($user) {
                $repliedAt = Message::whereHas('conversation', fn ($q) => $q->where('user_id', $user->id))
                    ->where('sender', 'user')
                    ->where('created_at', '>', $log->sent_at)
                    ->min('created_at');

                if ($repliedAt) {
                    $log->update(['responded' => true, 'responded_at' => $repliedAt]);
                }
            });
    }

    /**
     * @return array{0: string, 1: string}|null [trigger_type, message_text]
     */
    private function determineTrigger(User $user): ?array
    {
        if (! ProactiveLog::where('user_id', $user->id)->where('trigger_type', 'onboarding')->exists()) {
            return ['onboarding', $this->onboardingMessage()];
        }

        // Achievement triggers (4 & 5): "tetap dikirim terlepas dari cooldown ...
        // bukan nudge" — interpreted as bypassing ALL nudge throttling (daily cap,
        // cooldown, unanswered-streak pause), not just the cooldown specifically.
        // Flagged in the task 2.5 report since the doc's wording is ambiguous
        // about whether this also bypasses the "max 1 per day" cap.
        if ($levelMessage = $this->levelUpMessage($user)) {
            return ['level_up', $levelMessage];
        }

        if ($checkpointMessage = $this->checkpointMessage($user)) {
            return ['checkpoint', $checkpointMessage];
        }

        if (! $this->nudgesAllowed($user)) {
            return null;
        }

        if ($stagnationMessage = $this->stagnationMessage($user)) {
            return ['stagnation', $stagnationMessage];
        }

        if ($stuckMessage = $this->stuckMessage($user)) {
            return ['stuck', $stuckMessage];
        }

        return null;
    }

    private function nudgesAllowed(User $user): bool
    {
        $sentTodayExists = ProactiveLog::where('user_id', $user->id)
            ->whereIn('trigger_type', ['stagnation', 'stuck'])
            ->whereDate('sent_at', Carbon::today())
            ->exists();

        if ($sentTodayExists) {
            return false;
        }

        $lastNudge = ProactiveLog::where('user_id', $user->id)
            ->whereIn('trigger_type', ['stagnation', 'stuck'])
            ->orderByDesc('sent_at')
            ->first();

        if (! $lastNudge || $lastNudge->responded) {
            return true;
        }

        if ($lastNudge->sent_at->diffInDays(Carbon::now()) < config('webi.proactive_cooldown_days')) {
            return false;
        }

        $recentNudges = ProactiveLog::where('user_id', $user->id)
            ->whereIn('trigger_type', ['stagnation', 'stuck'])
            ->orderByDesc('sent_at')
            ->limit(config('webi.proactive_unanswered_limit'))
            ->get();

        $allUnanswered = $recentNudges->count() >= config('webi.proactive_unanswered_limit')
            && $recentNudges->every(fn (ProactiveLog $log) => ! $log->responded);

        if (! $allUnanswered) {
            return true;
        }

        return UserUnitProgress::where('user_id', $user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>', $lastNudge->sent_at)
            ->exists();
    }

    private function stagnationMessage(User $user): ?string
    {
        $days = config('webi.stagnation_days');
        $lastCompletion = UserUnitProgress::where('user_id', $user->id)->where('status', 'completed')->max('completed_at');
        $baseline = $lastCompletion ? Carbon::parse($lastCompletion) : $user->created_at;

        if ($baseline->diffInDays(Carbon::now()) < $days) {
            return null;
        }

        $currentUnit = $user->explorationProgress?->currentUnit;
        $unitLabel = $currentUnit?->title ?? 'unit yang sedang kamu kerjakan';

        return "Hei, terakhir kamu di Unit {$unitLabel}. Kalau ada yang bikin bingung, tanya aja. Kalau belum sempat, santai, lanjut kapan kamu siap.";
    }

    private function stuckMessage(User $user): ?string
    {
        $unit = $this->stuckUnitByOpenCount($user) ?? $this->stuckUnitByRepeatedTopic($user);

        if (! $unit) {
            return null;
        }

        return "Kayaknya Unit {$unit->title} agak tricky ya? Mau aku coba jelaskan dari sudut yang berbeda?";
    }

    private function stuckUnitByOpenCount(User $user): ?Unit
    {
        $progress = UserUnitProgress::where('user_id', $user->id)
            ->where('open_count_without_completion', '>=', config('webi.stuck_open_count_threshold'))
            ->where('status', '!=', 'completed')
            ->with('unit')
            ->first();

        return $progress?->unit;
    }

    private function stuckUnitByRepeatedTopic(User $user): ?Unit
    {
        $threshold = config('webi.stuck_topic_repeat_threshold');

        $repeated = Message::whereHas('conversation', fn ($q) => $q->where('user_id', $user->id))
            ->where('sender', 'user')
            ->whereNotNull('unit_context')
            ->selectRaw('unit_context, count(*) as message_count')
            ->groupBy('unit_context')
            ->havingRaw('count(*) >= ?', [$threshold])
            ->orderByDesc('message_count')
            ->first();

        return $repeated ? Unit::find($repeated->unit_context) : null;
    }

    private function levelUpMessage(User $user): ?string
    {
        $currentLevel = $user->explorationProgress?->current_level ?? 1;
        $celebratedLevel = 1 + ProactiveLog::where('user_id', $user->id)->where('trigger_type', 'level_up')->count();

        if ($currentLevel <= $celebratedLevel) {
            return null;
        }

        $levelName = $user->explorationProgress?->level_name ?? config('exploration.level_names')[$currentLevel];
        $range = self::LEVEL_MODULE_RANGES[$currentLevel] ?? null;
        $description = $range ? $this->moduleRangeDescription($range) : 'materi lanjutan';

        return "Selamat, kamu sekarang Level {$currentLevel} ({$levelName})! Di level ini kamu akan belajar tentang {$description}. Semangat!";
    }

    private function checkpointMessage(User $user): ?string
    {
        $completedCount = CheckpointCompletion::where('user_id', $user->id)->count();
        $celebratedCount = ProactiveLog::where('user_id', $user->id)->where('trigger_type', 'checkpoint')->count();

        if ($completedCount <= $celebratedCount) {
            return null;
        }

        $completion = CheckpointCompletion::with('checkpoint.module')
            ->where('user_id', $user->id)
            ->orderBy('completed_at')
            ->skip($celebratedCount)
            ->first();

        if (! $completion) {
            return null;
        }

        $module = $completion->checkpoint->module;
        $nextModule = Module::where('order_number', $module->order_number + 1)->first();
        $nextText = $nextModule ? "Modul berikutnya tentang {$nextModule->title}. Siap lanjut, atau masih ada yang mau didiskusikan dari Modul {$module->title}?" : 'Kamu sudah menuntaskan seluruh kurikulum!';

        // Deliberately doesn't open with "Modul X selesai!" — that fact is
        // already announced passively in the Eksplorasi dashboard's feed
        // (ProgressService::feedFor()) for the same CheckpointCompletion row.
        // This leads as WEBI reacting/checking in instead, so it reads as a
        // companion follow-up rather than a duplicate announcement.
        return "Btw, aku lihat kamu baru aja beresin checkpoint Modul {$module->title}, mantap! {$nextText}";
    }

    private function moduleRangeDescription(array $range): string
    {
        $titles = Module::whereBetween('order_number', $range)->orderBy('order_number')->pluck('title');

        return $titles->implode(', ');
    }

    private function onboardingMessage(): string
    {
        return 'Halo! Aku WEBI, teman belajarmu di sini. Kamu bisa tanya aku soal materi kurikulum, cara pakai sistem ini, atau hal apapun seputar web development yang ada di kurikulum. Kalau bingung mulai dari mana, tanya aja.';
    }
}

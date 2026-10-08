<?php

namespace App\Console\Commands;

use App\Models\UserExplorationProgress;
use App\Services\Exploration\PointService;
use Illuminate\Console\Command;

/**
 * Recomputes every member's current_level/level_name from their EXISTING
 * total_points against config('exploration.level_thresholds') — a
 * threshold change never retroactively re-resolves stored levels.
 * Idempotent: only rows whose resolved level differs get touched, so it's
 * safe to re-run any time thresholds change.
 *
 * Deliberately sends NO notification about a level change — needs its own
 * communication design (not reused "you just earned points" wording),
 * pending a decision from Aye.
 */
class RecalculateExplorationLevels extends Command
{
    protected $signature = 'exploration:recalculate-levels {--dry-run : Preview changes without saving anything}';

    protected $description = "Recalculate every exploration member's current_level/level_name from their existing total_points against the current level_thresholds config: run after level_thresholds changes.";

    public function handle(PointService $pointService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $changes = [];

        foreach (UserExplorationProgress::with('user')->get() as $progress) {
            [$newLevel, $newName] = $pointService->resolveLevel($progress->total_points);

            if ($newLevel === $progress->current_level) {
                continue;
            }

            $changes[] = [
                'name' => $progress->user?->name ?? $progress->user_id,
                'points' => $progress->total_points,
                'from_level' => $progress->current_level,
                'from_name' => $progress->level_name,
                'to_level' => $newLevel,
                'to_name' => $newName,
            ];

            if (! $dryRun) {
                $progress->update(['current_level' => $newLevel, 'level_name' => $newName]);
            }
        }

        if (empty($changes)) {
            $this->info('Semua level anggota sudah sesuai dengan threshold saat ini: tidak ada yang perlu diubah.');

            return self::SUCCESS;
        }

        $this->table(
            ['Anggota', 'Total Poin', 'Level Lama', 'Level Baru'],
            array_map(fn (array $c) => [
                $c['name'],
                $c['points'],
                "{$c['from_level']} ({$c['from_name']})",
                "{$c['to_level']} ({$c['to_name']})",
            ], $changes),
        );

        $verb = $dryRun ? 'akan terpengaruh (dry run, belum disimpan)' : 'berhasil diperbarui';
        $this->info(count($changes)." anggota {$verb}.");

        $downgrades = array_filter($changes, fn (array $c) => $c['to_level'] < $c['from_level']);

        if (! empty($downgrades)) {
            $this->warn(count($downgrades).' dari perubahan di atas adalah PENURUNAN level: pastikan sudah ada rencana komunikasi ke anggota terdampak sebelum menjalankan ini tanpa --dry-run.');
        }

        return self::SUCCESS;
    }
}

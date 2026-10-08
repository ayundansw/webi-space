<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Level Thresholds
    |--------------------------------------------------------------------------
    |
    | Minimum total_points required to reach each level. RECOMPUTED Fase 6
    | (2026-07-12) now that all three point sources (Materi, Kuis/Checkpoint,
    | Praktik) are live — the task 2.3 draft only ever accounted for
    | Materi+Checkpoint (995 max) because Praktik didn't exist yet.
    |
    | docs/v_2.0/archive/sumber-konsolidasi/kurikulum-eksplorasi.md states which modules belong to which level
    | (Level 1: Modul 1-2, Level 2: Modul 3, Level 3: Modul 4-5, Level 4:
    | Modul 6-7, Level 5: Modul 8-9, Level 6: Modul 10) but never gives an
    | explicit point number. Unlike the task 2.3 draft (which used each
    | level's EXACT cumulative unit+checkpoint sum, meaningful because both
    | are unit/module-scoped), these thresholds are a PROPORTIONAL split of
    | the grand total by module count per level — Praktik (Challenge) points
    | aren't tied to specific modules/levels at all (a member can do any
    | published Challenge whenever), so there's no "exact sum as of finishing
    | module N" to compute for that portion; proportional weighting is the
    | closest meaningful equivalent requested for this recalculation.
    |
    | Grand total (queried live from the DB, not estimated/hardcoded):
    |   Materi    (SUM units.point_value):                    770
    |   Checkpoint (25 x 9 — Modul 4 has none, see CurriculumSeeder):  225
    |   Praktik   (SUM published challenges.points_reward):    25  <- SEE NOTE
    |   TOTAL:                                                1020
    |
    | NOTE on Praktik: unlike Materi/Checkpoint (fixed once the curriculum is
    | built), this number MOVES as admin publishes more Challenges — the 25
    | above reflects whatever was published at the moment this was computed
    | (2026-07-12), not a stable ceiling. Re-run `php artisan
    | exploration:recalculate-levels` (App\Console\Commands\RecalculateExplorationLevels)
    | after updating these threshold numbers to keep every member's stored
    | current_level/level_name in sync — it's idempotent (safe to run
    | repeatedly, only touches rows whose calculated level actually changed)
    | and does NOT send any notification on its own (how to communicate a
    | threshold-driven level change to affected members was an explicit open
    | question at the time this was built — flag to Aye before wiring one up).
    |
    | Threshold(level) = TOTAL x (cumulative module count completed by the
    | END of the PREVIOUS level) / 10 total modules:
    | Level 1 (Pengenal):              0   — start (0/10 modules)
    | Level 2 (Penyiap):             204   — after Modul 1-2  (2/10 x 1020)
    | Level 3 (Kolaborator):         306   — after Modul 1-3  (3/10 x 1020)
    | Level 4 (Perakit):             510   — after Modul 1-5  (5/10 x 1020)
    | Level 5 (Praktisi):            714   — after Modul 1-7  (7/10 x 1020)
    | Level 6 (Lulusan Eksplorasi):  918   — after Modul 1-9  (9/10 x 1020)
    | (Modul 10 is the last module; there's no Level 7 to cross into after it.)
    |
    */

    'level_thresholds' => [
        1 => 0,
        2 => 204,
        3 => 306,
        4 => 510,
        5 => 714,
        6 => 918,
    ],

    'level_names' => [
        1 => 'Pengenal',
        2 => 'Penyiap',
        3 => 'Kolaborator',
        4 => 'Perakit',
        5 => 'Praktisi',
        6 => 'Lulusan Eksplorasi',
    ],

    /*
    |--------------------------------------------------------------------------
    | Fox Avatar Tiers (Fase 2 Langkah 4)
    |--------------------------------------------------------------------------
    |
    | INDEPEN dari `level_thresholds`/`level_names` di atas — ini sistem
    | terpisah (RANCANGAN_FINAL Modul 2 §2.4), threshold sendiri, JANGAN
    | disatukan meski keduanya sama-sama dibaca dari total_points. Minimum
    | total_points untuk tiap tingkat Fox (1-5), dipakai
    | App\Services\Exploration\FoxAvatarService::tierFor() (perbandingan
    | ">=", lihat method itu).
    |
    | Dikonfirmasi Aye (susulan Langkah 4): angka 50/100/300/500 adalah
    | BATAS ATAS tier di bawahnya, bukan batas bawah tier di atasnya — TEPAT
    | di 50/100/300/500 masih tier di bawahnya, tier berikutnya baru mulai
    | di angka +1:
    |   Tier 1: 0-50      Tier 2: 51-100      Tier 3: 101-300
    |   Tier 4: 301-500   Tier 5: 501+
    | Nilai di bawah sudah memakai angka minimum SEBENARNYA (51/101/301/501),
    | bukan 50/100/300/500 literal, supaya logic ">=" yang sudah ada tetap
    | benar tanpa perlu diubah jadi range eksplisit.
    |
    */

    'fox_avatar_tiers' => [
        1 => 0,
        2 => 51,
        3 => 101,
        4 => 301,
        5 => 501,
    ],

];

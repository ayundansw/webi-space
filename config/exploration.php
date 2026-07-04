<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Level Thresholds
    |--------------------------------------------------------------------------
    |
    | Minimum total_points required to reach each level. DRAFT, PENDING USER
    | CONFIRMATION (task 2.3, 2026-07-03) — NOT silently treated as final.
    |
    | docs/kurikulum-eksplorasi.md explicitly states which modules belong to
    | which level (Level 1: Modul 1-2, Level 2: Modul 3, Level 3: Modul 4-5,
    | Level 4: Modul 6-7, Level 5: Modul 8-9, Level 6: Modul 10) but never
    | gives an explicit point-threshold number for any level boundary.
    |
    | These thresholds were derived, not invented: each is the exact cumulative
    | total_points (real unit points + 25-pt checkpoint bonuses, computed from
    | the real seeded CurriculumSeeder data) at the moment all modules assigned
    | to the *previous* level are fully completed — i.e. the natural point at
    | which a member should cross into the next level given the module-to-level
    | mapping above. Grand total achievable across all 67 units + 9 checkpoints
    | (Modul 4 has no checkpoint of its own, see CurriculumSeeder docblock) is
    | 995 points.
    |
    | Level 1 (Pengenal):            0   — start
    | Level 2 (Penyiap):           150   — after Modul 1+2 done (85 + 65)
    | Level 3 (Kolaborator):       280   — after Modul 3 done (+130)
    | Level 4 (Perakit):           535   — after Modul 4+5 done (+120 +135)
    | Level 5 (Praktisi):          720   — after Modul 6+7 done (+120 +65)
    | Level 6 (Lulusan Eksplorasi):905   — after Modul 8+9 done (+125 +60)
    | (Modul 10, the last +90, brings the grand total to 995 — no Level 7.)
    |
    | Confirm with the user before relying on these as final; if confirmed,
    | update this comment to drop the "draft" framing.
    |
    */

    'level_thresholds' => [
        1 => 0,
        2 => 150,
        3 => 280,
        4 => 535,
        5 => 720,
        6 => 905,
    ],

    'level_names' => [
        1 => 'Pengenal',
        2 => 'Penyiap',
        3 => 'Kolaborator',
        4 => 'Perakit',
        5 => 'Praktisi',
        6 => 'Lulusan Eksplorasi',
    ],

];

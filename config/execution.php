<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Configurable thresholds for the automatic monitoring flags defined in
    | docs/struktur-eksekusi.md Bagian 5.2, with defaults from docs/PRD.md's
    | "Parameter Eksekusi" table.
    |
    */

    'due_soon_days' => 3,

    'stalled_days' => 7,

    'inactive_member_days' => 14,

    'project_idle_days' => 14,

];

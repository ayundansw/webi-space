<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Configurable thresholds for the automatic monitoring flags defined in
    | docs/v_2.0/archive/sumber-konsolidasi/struktur-eksekusi.md Bagian 5.2, with defaults from docs/v_2.0/archive/sumber-konsolidasi/PRD.md's
    | "Parameter Eksekusi" table.
    |
    */

    'due_soon_days' => 3,

    'stalled_days' => 7,

    'inactive_member_days' => 14,

    'project_idle_days' => 14,

];

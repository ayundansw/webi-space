<?php

/**
 * Sidebar menu per role. Single source of truth, replaces the inline
 * @if/@elseif role chain that used to live in
 * resources/views/components/layouts/app.blade.php.
 *
 * Each item: label, route (a named route, or null for a v2.0 slot that
 * has no destination yet), enabled (false = rendered as a disabled
 * placeholder, never a clickable link).
 *
 * Optional `active_when`: extra named routes (or routeIs()-style wildcard
 * patterns) that should also light up this item, for detail pages whose
 * route name doesn't share a dot-prefix with the item's own `route` (so
 * the automatic CRUD-suffix widening in sidebar.blade.php's active-check
 * doesn't already cover them). Every route in routes/web.php was audited
 * for this — see docs/v_2.0/archive/sumber-konsolidasi/RECON_v1_untuk_v2.md task 2.2.1c report
 * for the full list of what needed it vs what the suffix widening already
 * covers on its own.
 */
return [

    'admin' => [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'enabled' => true],
        ['label' => 'Manajemen Akun', 'route' => 'admin.users.index', 'enabled' => true],
        ['label' => 'Project Ideas', 'route' => 'eksekusi.ideas.index', 'enabled' => true],
        ['label' => 'Proyek', 'route' => 'eksekusi.projects.index', 'enabled' => true, 'active_when' => ['eksekusi.tasks.show']],
        ['label' => 'Log WEBI', 'route' => 'admin.webi.index', 'enabled' => true],
        ['label' => 'Kelola Kurikulum', 'route' => 'admin.curriculum.modules.index', 'enabled' => true, 'active_when' => ['admin.curriculum.units.*']],
        ['label' => 'Kelola Praktik', 'route' => 'admin.curriculum.challenges.index', 'enabled' => true],
        ['label' => 'Antrian Review Praktik', 'route' => 'admin.curriculum.submissions.index', 'enabled' => true],
        ['label' => 'Permintaan Mode Ganda', 'route' => 'admin.dual-mode.index', 'enabled' => true],
    ],

    'exploration_member' => [
        ['label' => 'Dashboard', 'route' => 'eksplorasi.dashboard', 'enabled' => true],
        ['label' => 'Peta Kurikulum', 'route' => 'eksplorasi.kurikulum', 'enabled' => true, 'active_when' => ['eksplorasi.unit.show', 'eksplorasi.checkpoint.show']],
        ['label' => 'Referensi', 'route' => 'eksplorasi.resources', 'enabled' => true],
        ['label' => 'Forum', 'route' => 'eksplorasi.forum.index', 'enabled' => true],
        ['label' => 'WEBI', 'route' => 'eksplorasi.webi', 'enabled' => true],
        ['label' => 'Praktik', 'route' => 'eksplorasi.praktik.index', 'enabled' => true],
    ],

    'execution_member' => [
        ['label' => 'Dashboard', 'route' => 'eksekusi.dashboard', 'enabled' => true],
        ['label' => 'Project Ideas', 'route' => 'eksekusi.ideas.index', 'enabled' => true],
        ['label' => 'Proyek', 'route' => 'eksekusi.projects.index', 'enabled' => true, 'active_when' => ['eksekusi.tasks.show']],
        ['label' => 'Kalender Personal', 'route' => 'eksekusi.kalender', 'enabled' => true],
        // Fase 8 Batch 6 (gap ditemukan Batch 4): akses baca Eksplorasi
        // sudah aktif sejak Batch 3, tapi belum ada jalur navigasi UI ke
        // sana sama sekali sampai sekarang — cuma bisa lewat URL langsung.
        ['label' => 'Jelajahi Eksplorasi', 'route' => 'eksplorasi.kurikulum', 'enabled' => true, 'active_when' => ['eksplorasi.unit.show', 'eksplorasi.checkpoint.show']],
        ['label' => 'Forum General', 'route' => 'eksekusi.forum.index', 'enabled' => true, 'active_when' => ['eksekusi.forum.show', 'eksekusi.forum.create']],
    ],

];

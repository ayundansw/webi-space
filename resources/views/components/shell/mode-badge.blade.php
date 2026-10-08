{{--
    Badge Status Mode (RANCANGAN_FINAL_WEBI-SPACE_v2.md §1.5) — slot navbar
    kondisional, struktural sejak Fase 2, fungsional penuh sejak Fase 8
    Batch 6. User::currentModeBadgeLabel() sudah memutuskan sendiri kapan
    tampil (null kalau user tidak punya kapabilitas mode ganda) dan teks
    apa yang ditampilkan (lihat docblock method itu untuk exploration_member
    vs execution_member).
--}}
@php
    $modeLabel = auth()->user()?->currentModeBadgeLabel();
@endphp
@if ($modeLabel)
    <span class="rounded-full border border-accent/40 bg-accent-soft px-2.5 py-1 font-mono text-xs font-semibold text-ink">
        {{ $modeLabel }}
    </span>
@endif

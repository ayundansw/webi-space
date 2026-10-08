@props([
    'tingkat' => 1,
    'size' => 'md',
])

@php
    /**
     * Fox Eksplorasi, 5 tingkat progresif (RANCANGAN_FINAL Modul 2 §2.4,
     * referensi resources/images/avatars/eksplorasi/Eksplor_Level 1-5.png).
     * `tingkat` di sini MURNI angka jadi -- semua logic unlock (poin ->
     * tingkat) ada di App\Services\Exploration\FoxAvatarService, TIDAK DI
     * SINI. Komponen ini read-only/presentational, tidak pernah memutuskan
     * tingkat sendiri.
     *
     * Grid dasar (siluet telinga-kepala-pipi-kerah-kaki) SENGAJA
     * diduplikasi, bukan partial bersama dengan <x-avatar.eksekusi>,
     * walau keduanya diturunkan dari template referensi yang sama --
     * supaya logic dua sistem yang berbeda aturan bisnis (progresif
     * otomatis vs pilihan bebas) tidak pernah tergoda saling menyentuh.
     */
    $tingkat = max(1, min(5, (int) $tingkat));

    $dimensionClass = match ($size) {
        'sm' => 'w-10 h-10',
        'lg' => 'w-32 h-32',
        'xl' => 'w-48 h-48',
        default => 'w-16 h-16',
    };

    // Tingkat 5: "helm abu-abu menutup kepala" -- override siluet
    // telinga+puncak kepala dari fox-orange jadi Muted.
    $earHeadColor = $tingkat >= 5 ? 'var(--color-muted)' : 'var(--color-fox)';

    // Kerah: kosong (menyatu badan) di tingkat 1, abu-abu di 2-4, emas di 5.
    $collarColor = match (true) {
        $tingkat >= 5 => 'var(--color-warning)',
        $tingkat >= 2 => 'var(--color-muted)',
        default => 'var(--color-fox)',
    };
    $showCollarAccent = $tingkat >= 3;
    $collarAccentX = $tingkat >= 4 ? 4 : 5;
    $collarAccentWidth = $tingkat >= 4 ? 4 : 2;
    $showTempleAccent = $tingkat >= 4;
    $showShoulderAccent = $tingkat >= 5;
    $showSparkle = $tingkat >= 5;
    $footColor = $tingkat >= 5 ? 'var(--color-warning)' : 'var(--color-warm-soft)';
@endphp

<svg {{ $attributes->merge(['class' => $dimensionClass]) }} viewBox="0 0 12 12" role="img" aria-label="Avatar Fox tingkat {{ $tingkat }}" xmlns="http://www.w3.org/2000/svg" shape-rendering="crispEdges">
    {{-- Telinga + puncak kepala --}}
    <g fill="{{ $earHeadColor }}">
        <rect x="3" y="0" width="1" height="1" />
        <rect x="8" y="0" width="1" height="1" />
        <rect x="2" y="1" width="3" height="1" />
        <rect x="7" y="1" width="3" height="1" />
        <rect x="1" y="2" width="4" height="1" />
        <rect x="7" y="2" width="4" height="1" />
    </g>

    {{-- Badan fox-orange: sisa siluet (tepi pipi, dasar kaki) --}}
    <g fill="var(--color-fox)">
        <rect x="1" y="3" width="1" height="1" />
        <rect x="10" y="3" width="1" height="1" />
        <rect x="1" y="4" width="2" height="1" />
        <rect x="9" y="4" width="2" height="1" />
        <rect x="1" y="5" width="2" height="1" />
        <rect x="9" y="5" width="2" height="1" />
        <rect x="1" y="7" width="1" height="1" />
        <rect x="4" y="7" width="1" height="1" />
        <rect x="7" y="7" width="1" height="1" />
        <rect x="10" y="7" width="1" height="1" />
        <rect x="1" y="8" width="1" height="1" />
        <rect x="4" y="8" width="1" height="1" />
        <rect x="7" y="8" width="1" height="1" />
        <rect x="10" y="8" width="1" height="1" />
        <rect x="3" y="9" width="1" height="2" />
        <rect x="8" y="9" width="1" height="2" />
    </g>

    {{-- Kerah --}}
    <rect x="1" y="6" width="10" height="1" fill="{{ $collarColor }}" />
    @if ($showCollarAccent)
        <rect x="{{ $collarAccentX }}" y="6" width="{{ $collarAccentWidth }}" height="1" fill="var(--color-accent)" />
    @endif

    {{-- Mata & hidung --}}
    <g fill="var(--color-ink)">
        <rect x="2" y="3" width="2" height="1" />
        <rect x="8" y="3" width="2" height="1" />
        <rect x="5" y="5" width="2" height="1" />
    </g>

    @if ($showTempleAccent)
        <g fill="var(--color-accent)">
            <rect x="1" y="3" width="1" height="1" />
            <rect x="10" y="3" width="1" height="1" />
        </g>
    @endif

    @if ($showShoulderAccent)
        <g fill="var(--color-accent)">
            <rect x="1" y="7" width="1" height="2" />
            <rect x="10" y="7" width="1" height="2" />
        </g>
    @endif

    {{-- Pipi & bantalan dada --}}
    <g fill="var(--color-warm-soft)">
        <rect x="4" y="3" width="4" height="1" />
        <rect x="3" y="4" width="6" height="1" />
        <rect x="3" y="5" width="2" height="1" />
        <rect x="7" y="5" width="2" height="1" />
        <rect x="2" y="7" width="2" height="1" />
        <rect x="5" y="7" width="2" height="1" />
        <rect x="8" y="7" width="2" height="1" />
        <rect x="2" y="8" width="2" height="1" />
        <rect x="5" y="8" width="2" height="1" />
        <rect x="8" y="8" width="2" height="1" />
    </g>

    {{-- Telapak kaki --}}
    <g fill="{{ $footColor }}">
        <rect x="3" y="11" width="1" height="1" />
        <rect x="8" y="11" width="1" height="1" />
    </g>

    @if ($showSparkle)
        <g fill="var(--color-accent-soft)">
            <rect x="0" y="0" width="1" height="1" />
            <rect x="11" y="0" width="1" height="1" />
        </g>
    @endif
</svg>

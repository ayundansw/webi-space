@props([
    'variant' => 'full',
    'size' => null,
])

@php
    /**
     * Maskot "Boxy Blocky" (referensi: resources/images/mascot/Webi Maskot
     * Ikon.png). Dua varian terpisah (bukan satu SVG di-scale) karena detail
     * mata dua-nada + paruh dua-segmen + kaki akan kabur/tidak terbaca kalau
     * versi penuh cuma di-resize ke ~44px -- chat-icon sengaja lebih chunky
     * & sederhana (tanpa kaki, mata satu nada, paruh satu blok), sesuai
     * anotasi "Chunkier, more Minecraft-sprite" di referensi.
     *
     * Semua warna lewat var(--color-*) dari @theme (resources/css/app.css),
     * bukan hex literal, supaya ikut berubah otomatis kalau token direvisi.
     */
    $dimensionClass = match ($size) {
        'sm' => 'w-8 h-8',
        'md' => 'w-16 h-16',
        'lg' => 'w-32 h-32',
        'xl' => 'w-48 h-48',
        default => $variant === 'chat-icon' ? 'w-11 h-11' : 'w-16 h-16',
    };
@endphp

@if ($variant === 'chat-icon')
    <svg {{ $attributes->merge(['class' => $dimensionClass]) }} viewBox="0 0 8 6" role="img" aria-label="Maskot WEBI" xmlns="http://www.w3.org/2000/svg" shape-rendering="crispEdges">
        {{-- Wajah/badan: Muted --}}
        <rect x="1" y="1" width="6" height="5" fill="var(--color-muted)" />

        {{-- Siluet: telinga kiri-kanan, Ink --}}
        <rect x="1" y="0" width="1" height="1" fill="var(--color-ink)" />
        <rect x="6" y="0" width="1" height="1" fill="var(--color-ink)" />

        {{-- Mata: satu nada Accent (disederhanakan, tanpa outer-soft di ukuran ini) --}}
        <rect x="2" y="2" width="1" height="2" fill="var(--color-accent)" />
        <rect x="5" y="2" width="1" height="2" fill="var(--color-accent)" />

        {{-- Paruh: satu blok, warna warm-mid --}}
        <rect x="3" y="4" width="2" height="1" fill="var(--color-warm-mid)" />
    </svg>
@else
    <svg {{ $attributes->merge(['class' => $dimensionClass]) }} viewBox="0 0 12 11" role="img" aria-label="Maskot WEBI" xmlns="http://www.w3.org/2000/svg" shape-rendering="crispEdges">
        {{-- Wajah/badan: Muted, termasuk 2 "tab" penyambung ke telinga (menyisakan notch tengah) dan nub kecil di bawah --}}
        <g fill="var(--color-muted)">
            <rect x="2" y="3" width="3" height="1" />
            <rect x="7" y="3" width="3" height="1" />
            <rect x="2" y="4" width="8" height="6" />
            <rect x="5" y="10" width="2" height="1" />
        </g>

        {{-- Siluet luar: telinga runcing, dinding sisi, puncak paruh, ujung kaki -- semua Ink --}}
        <g fill="var(--color-ink)">
            <rect x="1" y="0" width="1" height="2" />
            <rect x="10" y="0" width="1" height="2" />
            <rect x="1" y="2" width="2" height="1" />
            <rect x="9" y="2" width="2" height="1" />
            <rect x="1" y="3" width="1" height="7" />
            <rect x="10" y="3" width="1" height="7" />
            <rect x="5" y="5" width="2" height="1" />
            <rect x="2" y="10" width="1" height="1" />
            <rect x="9" y="10" width="1" height="1" />
        </g>

        {{-- Mata, lapisan luar: Accent-soft --}}
        <g fill="var(--color-accent-soft)">
            <rect x="2" y="4" width="2" height="3" />
            <rect x="8" y="4" width="2" height="3" />
        </g>

        {{-- Mata, pupil (sisi dalam, menghadap tengah) + kaki: Accent --}}
        <g fill="var(--color-accent)">
            <rect x="3" y="5" width="1" height="2" />
            <rect x="8" y="5" width="1" height="2" />
            <rect x="2" y="7" width="1" height="2" />
            <rect x="9" y="7" width="1" height="2" />
        </g>

        {{-- Paruh bawah: warm-mid --}}
        <rect x="5" y="6" width="2" height="3" fill="var(--color-warm-mid)" />
    </svg>
@endif

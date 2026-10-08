@props([
    'size' => 'md',
])

@php
    /**
     * Wordmark tipografis "WEBI-SPACE" (1.4.A) — belum dipasang ke shell
     * mana pun, murni komponen siap pakai untuk Langkah 5/7. Hyphen jadi
     * satu-satunya elemen ber-warna --color-accent, sisanya --color-ink,
     * supaya tetap patuh aturan "maksimal satu elemen aksen mencolok per
     * layar" (1.2.4) walau dipakai berdampingan dengan elemen cyan lain.
     */
    $sizeClass = match ($size) {
        'sm' => 'text-lg tracking-wide',
        'lg' => 'text-4xl sm:text-5xl tracking-widest',
        default => 'text-2xl tracking-wide',
    };
@endphp

<span {{ $attributes->merge(['class' => "font-pixel inline-flex items-baseline gap-0.5 font-bold leading-none whitespace-nowrap text-ink $sizeClass"]) }}>
    <span>WEBI</span><span class="text-accent">-</span><span>SPACE</span>
</span>

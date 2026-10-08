@props([
    'size' => 'sm',
])

@php
    // Admin tidak punya sistem avatar (bukan bagian Fox atau Eksekusi) --
    // ikon siluet netral, keputusan terkunci (bukan inisial nama, bukan maskot).
    $dimensionClass = match ($size) {
        'md' => 'h-16 w-16',
        'lg' => 'h-32 w-32',
        default => 'h-10 w-10',
    };
@endphp

<span {{ $attributes->merge(['class' => "$dimensionClass inline-flex shrink-0 items-center justify-center rounded-full bg-surface-alt"]) }} role="img" aria-label="Avatar Admin">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--color-muted)" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-3/5 w-3/5">
        <circle cx="12" cy="8" r="3.5" />
        <path d="M4.5 20a7.5 7.5 0 0 1 15 0" />
    </svg>
</span>

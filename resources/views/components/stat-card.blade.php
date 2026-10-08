@props([
    'label',
    'value',
    'suffix' => null,
    'helper' => null,
    'progress' => null,
    'elevated' => false,
    'accent' => null,
])

@php
    /**
     * `elevated`/`accent` are opt-in (design-tokens-v2, batch: dashboard
     * Eksplorasi). Omitting both keeps the exact original markup/classes —
     * this is what preserves the Admin dashboard's KPI row pixel-for-pixel
     * since it never passes these props.
     */
    $isWarm = $accent === 'warm';
    $isElevated = $elevated || $isWarm;

    $cardClass = match (true) {
        $isWarm => 'rounded-xl border border-warm/30 bg-warm-soft p-5 shadow-warm-md transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-lg',
        $isElevated => 'rounded-xl border border-muted/10 bg-white p-5 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md',
        default => 'rounded-xl border border-muted/25 p-5',
    };
@endphp

<div {{ $attributes->merge(['class' => $cardClass]) }}>
    <p class="text-xs text-muted">{{ $label }}</p>
    <p class="font-display mt-1 text-3xl font-bold text-ink">{{ $value }}{{ $suffix }}</p>

    @if ($helper)
        <p class="text-sm text-ink">{{ $helper }}</p>
    @endif

    @if (! is_null($progress))
        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-muted/15">
            <div class="h-full bg-accent" style="width: {{ $progress }}%"></div>
        </div>
    @endif
</div>

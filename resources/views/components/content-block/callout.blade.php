@php
    $variant = in_array($data['variant'] ?? null, ['info', 'tip', 'warning'], true) ? $data['variant'] : 'info';

    $variantClasses = match ($variant) {
        'tip' => 'border-green-200 bg-green-50',
        'warning' => 'border-amber-200 bg-amber-50',
        default => 'border-accent/40 bg-accent-soft/30',
    };
@endphp

<div class="rounded-xl border {{ $variantClasses }} p-4">
    @if (! empty($data['title']))
        <p class="font-display text-sm font-semibold text-ink">{{ $data['title'] }}</p>
    @endif
    <div class="mt-1 text-sm leading-relaxed text-ink [&_p]:my-1 first:[&_p]:mt-0 last:[&_p]:mb-0">
        {!! \App\Services\Content\SafeMarkdown::toHtml($data['body'] ?? '') !!}
    </div>
</div>

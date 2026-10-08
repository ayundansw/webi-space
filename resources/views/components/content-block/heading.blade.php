@php
    $level = max(1, min(3, (int) ($data['level'] ?? 2)));
    $sizeClass = match ($level) {
        1 => 'text-2xl',
        2 => 'text-xl',
        default => 'text-lg',
    };
    $tag = 'h'.$level;
@endphp

<{{ $tag }} class="font-display {{ $sizeClass }} font-bold text-ink">{{ $data['text'] ?? '' }}</{{ $tag }}>

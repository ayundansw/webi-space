@php
    $hour = (int) now()->format('G');

    $timeGreeting = match (true) {
        $hour >= 4 && $hour < 11 => 'Selamat pagi',
        $hour >= 11 && $hour < 15 => 'Selamat siang',
        $hour >= 15 && $hour < 18 => 'Selamat sore',
        default => 'Selamat malam',
    };

    // Rotates on every fresh render (not persisted) — one shared component
    // used by all three portal dashboards per docs/v_2.0/2.1.2 section 1
    // point 6.
    $taglines = [
        'Yuk lanjutkan langkah kecil hari ini.',
        'Konsisten itu yang penting, bukan cepat.',
        'Ada progres baru menunggu buat dilihat.',
        'Semangat, satu langkah lagi lebih dekat.',
        'Senang kamu balik lagi hari ini.',
        'Yuk cek apa yang bisa dikerjakan hari ini.',
    ];

    $tagline = $taglines[array_rand($taglines)];
@endphp

<div>
    <h1 class="font-display text-2xl font-bold text-ink">{{ $timeGreeting }}, <span class="font-pixel text-accent">{{ auth()->user()->name }}</span>!</h1>
    <p class="mt-1 text-sm text-muted">{{ $tagline }}</p>
</div>

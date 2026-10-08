@props([
    'jenis' => 'elang',
    'size' => 'md',
])

@php
    /**
     * 5 hewan Eksekusi, pilihan bebas (RANCANGAN_FINAL Modul 2 §2.4,
     * referensi resources/images/avatars/eksekusi/Eksekusi_*.png). TIDAK
     * ADA konsep unlock/tingkat di sini -- kelimanya selalu tersedia,
     * mekanisme pilih ada di App\Livewire\Eksekusi\AvatarPicker, bukan di
     * komponen ini. Grid dasar sengaja diduplikasi dari <x-avatar.fox>
     * (lihat komentar di file itu soal alasannya).
     */
    $dimensionClass = match ($size) {
        'sm' => 'w-10 h-10',
        'lg' => 'w-32 h-32',
        'xl' => 'w-48 h-48',
        default => 'w-16 h-16',
    };

    // Elang punya struktur mata/paruh berbeda dari 4 hewan lain (lihat
    // referensi) -- bukan cuma beda palet, jadi ditangani terpisah di bawah.
    $config = match ($jenis) {
        'serigala' => ['body' => 'var(--color-wolf)', 'cheek' => 'var(--color-accent-soft)', 'mane' => false, 'marks' => null, 'label' => 'Serigala'],
        'singa' => ['body' => 'var(--color-warning)', 'cheek' => 'var(--color-warm-soft)', 'mane' => true, 'marks' => null, 'label' => 'Singa'],
        'harimau' => ['body' => 'var(--color-warm)', 'cheek' => 'var(--color-warm-soft)', 'mane' => false, 'marks' => 'stripes', 'label' => 'Harimau'],
        'cheetah' => ['body' => 'var(--color-warm-mid)', 'cheek' => 'var(--color-warm-soft)', 'mane' => false, 'marks' => 'spots', 'label' => 'Cheetah'],
        default => ['body' => 'var(--color-eagle)', 'cheek' => null, 'mane' => false, 'marks' => null, 'label' => 'Elang'],
    };
    $isElang = $jenis === 'elang';
@endphp

<svg {{ $attributes->merge(['class' => $dimensionClass]) }} viewBox="0 0 12 12" role="img" aria-label="Avatar {{ $config['label'] }}" xmlns="http://www.w3.org/2000/svg" shape-rendering="crispEdges">
    {{-- Siluet penuh (telinga+kepala+badan+kaki) dalam warna dasar hewan --}}
    <g fill="{{ $config['body'] }}">
        <rect x="3" y="0" width="1" height="1" />
        <rect x="8" y="0" width="1" height="1" />
        <rect x="2" y="1" width="3" height="1" />
        <rect x="7" y="1" width="3" height="1" />
        <rect x="1" y="2" width="4" height="1" />
        <rect x="7" y="2" width="4" height="1" />
        <rect x="1" y="3" width="10" height="1" />
        <rect x="1" y="4" width="10" height="1" />
        <rect x="1" y="5" width="10" height="1" />
        <rect x="1" y="6" width="10" height="1" />
        <rect x="1" y="7" width="10" height="1" />
        <rect x="1" y="8" width="10" height="1" />
        <rect x="3" y="9" width="1" height="2" />
        <rect x="8" y="9" width="1" height="2" />
    </g>

    {{-- Surai Singa: menimpa telinga+puncak kepala jadi lebih gelap --}}
    @if ($config['mane'])
        <g fill="var(--color-lion-mane)">
            <rect x="3" y="0" width="1" height="1" />
            <rect x="8" y="0" width="1" height="1" />
            <rect x="2" y="1" width="3" height="1" />
            <rect x="7" y="1" width="3" height="1" />
            <rect x="1" y="2" width="4" height="1" />
            <rect x="7" y="2" width="4" height="1" />
        </g>
    @endif

    {{-- Pipi/wajah (Elang tidak punya inset pipi -- badan gelap mengisi penuh) --}}
    @if ($config['cheek'])
        <g fill="{{ $config['cheek'] }}">
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
    @endif

    @if (! $isElang)
        {{-- Mata & hidung standar --}}
        <g fill="var(--color-ink)">
            <rect x="2" y="3" width="2" height="1" />
            <rect x="8" y="3" width="2" height="1" />
            <rect x="5" y="5" width="2" height="1" />
        </g>
        <g fill="var(--color-accent)">
            <rect x="1" y="3" width="1" height="1" />
            <rect x="10" y="3" width="1" height="1" />
        </g>
    @else
        {{-- Elang: mata "teropong" 2-nada (sudut Ink + isi Accent), paruh gold --}}
        <g fill="var(--color-ink)">
            <rect x="2" y="3" width="1" height="1" />
            <rect x="9" y="3" width="1" height="1" />
        </g>
        <g fill="var(--color-accent)">
            <rect x="3" y="3" width="1" height="1" />
            <rect x="8" y="3" width="1" height="1" />
        </g>
        <g fill="var(--color-warning)">
            <rect x="5" y="2" width="2" height="1" />
            <rect x="5" y="5" width="2" height="1" />
        </g>
    @endif

    {{-- Aksen garis-garis (Harimau) / totol-totol (Cheetah) --}}
    @if ($config['marks'] === 'stripes')
        <g fill="var(--color-ink)">
            <rect x="3" y="2" width="1" height="1" />
            <rect x="8" y="2" width="1" height="1" />
            <rect x="1" y="4" width="1" height="1" />
            <rect x="10" y="4" width="1" height="1" />
        </g>
    @elseif ($config['marks'] === 'spots')
        <g fill="var(--color-ink)">
            <rect x="2" y="4" width="1" height="1" />
            <rect x="9" y="4" width="1" height="1" />
            <rect x="4" y="4" width="1" height="1" />
            <rect x="7" y="4" width="1" height="1" />
            <rect x="3" y="8" width="1" height="1" />
            <rect x="8" y="8" width="1" height="1" />
        </g>
    @endif
</svg>

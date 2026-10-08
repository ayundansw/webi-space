@php
    $navItems = config('navigation.'.auth()->user()->role, []);

    /**
     * Ikon dipindah dari sidebar.blade.php (2.2.1c) apa adanya — cuma satu
     * tempat sekarang (sidebar dihapus total, Fase 2 Langkah 5), jadi tidak
     * ada lagi duplikasi antara dua file.
     */
    $icons = [
        'Dashboard' => '<path d="M4 11 12 4l8 7" /><path d="M6 10v9h12v-9" />',
        'Manajemen Akun' => '<circle cx="9" cy="9" r="3" /><path d="M3.5 20a5.5 5.5 0 0 1 11 0" /><circle cx="17" cy="9" r="2.25" /><path d="M15.5 14.5A4.5 4.5 0 0 1 20 19" />',
        'Project Ideas' => '<path d="M12 3a6 6 0 0 0-3 11.2c.6.4 1 .9 1 1.8h4c0-.9.4-1.4 1-1.8A6 6 0 0 0 12 3Z" /><path d="M9.5 18.5h5" /><path d="M10.25 21h3.5" />',
        'Proyek' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" />',
        'Log WEBI' => '<path d="M7 3h7l4 4v14H7Z" /><path d="M14 3v4h4" /><path d="M9.5 12h5" /><path d="M9.5 16h5" />',
        'Kelola Kurikulum' => '<path d="M9 4 4 6v14l5-2 6 2 5-2V4l-5 2-6-2Z" /><path d="M9 4v14" /><path d="M15 6v14" />',
        'Kelola Praktik' => '<path d="m9 8-4 4 4 4" /><path d="m15 8 4 4-4 4" />',
        'Antrian Review Praktik' => '<path d="M9 4h6v3H9Z" /><path d="M7 5H6a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-1" /><path d="M9 13.5l2 2 4-4" />',
        'Permintaan Mode Ganda' => '<rect x="3" y="8" width="18" height="8" rx="4" /><circle cx="15" cy="12" r="2.5" />',
        'Peta Kurikulum' => '<path d="M9 4 4 6v14l5-2 6 2 5-2V4l-5 2-6-2Z" /><path d="M9 4v14" /><path d="M15 6v14" />',
        'Referensi' => '<path d="M4 5a2 2 0 0 1 2-2h6v18H6a2 2 0 0 1-2-2V5Z" /><path d="M20 5a2 2 0 0 0-2-2h-6v18h6a2 2 0 0 1 2 2V5Z" />',
        'Forum' => '<path d="M4 5h13v9H8l-4 4V5Z" />',
        'WEBI' => '<path d="m12 3 1.7 5.3L19 10l-5.3 1.7L12 17l-1.7-5.3L5 10l5.3-1.7Z" />',
        'Praktik' => '<path d="m9 8-4 4 4 4" /><path d="m15 8 4 4-4 4" />',
        'Kalender Personal' => '<rect x="3.5" y="5" width="17" height="15" rx="2" /><path d="M3.5 10h17" /><path d="M8 3v4" /><path d="M16 3v4" />',
        'Jelajahi Eksplorasi' => '<circle cx="12" cy="12" r="9" /><path d="m15 9-2 5-5 2 2-5 5-2Z" />',
        'Forum General' => '<path d="M3 5h12v8H8l-3 3V5Z" /><path d="M11 9h8v6l-2.5-2H11V9Z" />',
    ];

    /**
     * Ikon berwarna (§1.5 RANCANGAN_FINAL: "bukan monokrom"), dikelompokkan
     * per family token yang sudah final. `warm-mid` (peach) SENGAJA cuma
     * dipakai untuk 2 item terkait WEBI (Log WEBI, WEBI) -- brief §1.4
     * eksplisit minta peach dijaga khusus konteks WEBI, tidak menyebar jadi
     * warna UI umum. Class ditulis literal (bukan interpolasi PHP ke nama
     * class) supaya tetap terdeteksi Tailwind's static scanner.
     */
    $iconColors = [
        'Dashboard' => ['chip' => 'bg-warm-soft', 'icon' => 'text-warm', 'active' => 'ring-warm'],
        'Manajemen Akun' => ['chip' => 'bg-success-soft', 'icon' => 'text-success', 'active' => 'ring-success'],
        'Project Ideas' => ['chip' => 'bg-warning-soft', 'icon' => 'text-warning', 'active' => 'ring-warning'],
        'Proyek' => ['chip' => 'bg-accent-soft', 'icon' => 'text-accent', 'active' => 'ring-accent'],
        'Log WEBI' => ['chip' => 'bg-warm-mid/25', 'icon' => 'text-warm-mid', 'active' => 'ring-warm-mid'],
        'Kelola Kurikulum' => ['chip' => 'bg-accent-soft', 'icon' => 'text-accent', 'active' => 'ring-accent'],
        'Kelola Praktik' => ['chip' => 'bg-accent-soft', 'icon' => 'text-accent', 'active' => 'ring-accent'],
        'Antrian Review Praktik' => ['chip' => 'bg-accent-soft', 'icon' => 'text-accent', 'active' => 'ring-accent'],
        'Permintaan Mode Ganda' => ['chip' => 'bg-warning-soft', 'icon' => 'text-warning', 'active' => 'ring-warning'],
        'Peta Kurikulum' => ['chip' => 'bg-accent-soft', 'icon' => 'text-accent', 'active' => 'ring-accent'],
        'Referensi' => ['chip' => 'bg-success-soft', 'icon' => 'text-success', 'active' => 'ring-success'],
        'Forum' => ['chip' => 'bg-warning-soft', 'icon' => 'text-warning', 'active' => 'ring-warning'],
        'WEBI' => ['chip' => 'bg-warm-mid/25', 'icon' => 'text-warm-mid', 'active' => 'ring-warm-mid'],
        'Praktik' => ['chip' => 'bg-accent-soft', 'icon' => 'text-accent', 'active' => 'ring-accent'],
        'Kalender Personal' => ['chip' => 'bg-warning-soft', 'icon' => 'text-warning', 'active' => 'ring-warning'],
        'Jelajahi Eksplorasi' => ['chip' => 'bg-success-soft', 'icon' => 'text-success', 'active' => 'ring-success'],
        'Forum General' => ['chip' => 'bg-warning-soft', 'icon' => 'text-warning', 'active' => 'ring-warning'],
    ];
    $defaultColors = ['chip' => 'bg-accent-soft', 'icon' => 'text-accent', 'active' => 'ring-accent'];
@endphp

<div class="relative">
    <button
        type="button"
        @click="popupOpen = !popupOpen"
        :aria-expanded="popupOpen.toString()"
        aria-label="Buka menu navigasi"
        class="rounded-control border border-muted/40 p-2 text-ink transition-colors duration-150 hover:border-ink hover:bg-accent-soft"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
            <circle cx="6" cy="6" r="1.8" /><circle cx="12" cy="6" r="1.8" /><circle cx="18" cy="6" r="1.8" />
            <circle cx="6" cy="12" r="1.8" /><circle cx="12" cy="12" r="1.8" /><circle cx="18" cy="12" r="1.8" />
            <circle cx="6" cy="18" r="1.8" /><circle cx="12" cy="18" r="1.8" /><circle cx="18" cy="18" r="1.8" />
        </svg>
    </button>

    <div
        x-show="popupOpen"
        x-cloak
        @click.outside="popupOpen = false"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 z-40 mt-2 w-64 rounded-xl border border-muted/25 bg-white p-3 shadow-warm-lg sm:w-80"
    >
        {{--
            flex-wrap + justify-center (bukan CSS grid) SENGAJA dipilih --
            grid tidak bisa memusatkan baris terakhir yang tidak penuh tanpa
            trik tambahan (kolom dihitung per role, dsb), sedangkan flexbox
            memusatkan tiap baris yang di-wrap SECARA NATIF, jadi 5 item
            (3+2) atau 6 item (3+3, atau 4+2 di layar lebih lebar) otomatis
            rapi tanpa logic kolom dinamis per role. Lebar item tetap (w-16)
            supaya titik wrap konsisten di semua ukuran popup (w-64/w-80).
        --}}
        <div class="flex flex-wrap justify-center gap-2">
            @foreach ($navItems as $item)
                @php
                    $active = \App\Support\NavigationMatcher::isActive($item);
                    $colors = $iconColors[$item['label']] ?? $defaultColors;
                @endphp

                @if ($item['enabled'])
                    <a
                        href="{{ route($item['route']) }}"
                        wire:navigate
                        @click="popupOpen = false"
                        title="{{ $item['label'] }}"
                        class="flex w-16 flex-col items-center justify-start gap-1.5 rounded-lg p-2.5 text-center transition-colors duration-150 hover:bg-surface-alt {{ $active ? 'ring-1 ring-offset-4 ring-offset-white '.$colors['active'] : '' }}"
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $colors['chip'] }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 {{ $colors['icon'] }}">
                                {!! $icons[$item['label']] ?? '' !!}
                            </svg>
                        </span>
                        <span class="text-xs leading-tight font-medium text-ink">{{ $item['label'] }}</span>
                    </a>
                @else
                    <span
                        title="{{ $item['label'] }} (segera hadir)"
                        aria-disabled="true"
                        class="flex w-16 cursor-not-allowed flex-col items-center justify-start gap-1.5 rounded-lg p-2 text-center opacity-40 grayscale"
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $colors['chip'] }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 {{ $colors['icon'] }}">
                                {!! $icons[$item['label']] ?? '' !!}
                            </svg>
                        </span>
                        <span class="text-xs leading-tight font-medium text-muted">{{ $item['label'] }}</span>
                    </span>
                @endif
            @endforeach
        </div>
    </div>
</div>

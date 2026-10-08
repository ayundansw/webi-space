@php
    $crumbs = \App\Support\Breadcrumbs::trail();
@endphp

@if (count($crumbs) > 0)
    {{--
        Card kecil mengambang kanan-atas (revisi desain, menyimpang sengaja
        dari full-width kiri di RANCANGAN_FINAL_WEBI-SPACE_v2.md §1.6 --
        keputusan sadar Aye, dokumen akan diupdate terpisah). `absolute`
        posisinya relatif ke <main> (parent terdekat yang `relative`, lihat
        app.blade.php) -- offset top/right dihitung dari padding-box <main>,
        BUKAN dari mana konten halaman (h1 dst) mulai, jadi murni overlay,
        tidak mendorong konten. Styling meniru pola "elevated" <x-stat-card>
        yang sudah ada (rounded-xl + border-muted/10 + shadow-warm-xs),
        bukan gaya card baru.
    --}}
    {{--
        Bagian A (perbaikan lanjutan): `@stack('breadcrumb-actions')` -- slot
        generik supaya halaman manapun bisa taruh CTA-nya SEJAJAR dengan
        card breadcrumb ini (bukan cuma di bawahnya, yang sebelumnya bikin
        dempet -- lihat Referensi/Forum). Halaman yang butuh cukup
        `@push('breadcrumb-actions') ... @endpush` di view-nya sendiri,
        tidak perlu ubah komponen ini lagi per halaman baru.
    --}}
    <div class="absolute top-2 right-4 z-10 flex max-w-[calc(100vw-2rem)] flex-wrap items-center justify-end gap-2 sm:top-3 sm:right-6">
        @stack('breadcrumb-actions')

        <nav aria-label="Breadcrumb">
            <div class="flex w-max max-w-full flex-wrap items-center gap-1 rounded-xl border border-muted/10 bg-white px-3 py-1.5 text-xs text-muted shadow-warm-xs sm:text-sm">
                @foreach ($crumbs as $index => $crumb)
                    @if ($index > 0)
                        <span aria-hidden="true" class="text-muted/50">/</span>
                    @endif

                    @if ($crumb['url'])
                        <a href="{{ $crumb['url'] }}" wire:navigate class="hover:text-accent">{{ $crumb['label'] }}</a>
                    @else
                        <span class="font-medium text-ink" aria-current="page">{{ $crumb['label'] }}</span>
                    @endif
                @endforeach
            </div>
        </nav>
    </div>
@endif

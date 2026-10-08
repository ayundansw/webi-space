@if ($locked)
    {{--
        Breadcrumb sudah menyediakan link balik ke "Peta Kurikulum" (crumb
        tengah) -- link "Kembali" terpisah yang dulu ada di sini dihapus,
        redundant.
    --}}
    <div class="mt-10 rounded-2xl border border-muted/20 bg-white p-8 text-center shadow-warm-xs">
        <p class="text-lg font-medium text-ink">Unit ini belum bisa dibuka</p>
        <p class="mt-2 text-sm text-muted">Selesaikan dulu unit sebelumnya ya, baru unit ini kebuka. Santai, tidak perlu buru-buru.</p>
    </div>
@else
    {{--
        Bagian B (Fase 3): restrukturisasi total halaman Materi+WEBI, ganti
        mekanisme slide-over lama (webiPanelOpen, FAB fixed bottom-right,
        backdrop, translate-x) dengan layout 3-kolom (wireframe Aye):
        Daftar Isi Modul (kiri, bisa minimize) | Materi (tengah) | WEBI
        (kanan, bisa minimize -- collapsed = strip di bawah). State Alpine
        LOKAL murni (tocOpen/webiOpen), sengaja TIDAK di-persist lintas
        halaman -- reset tiap unit dibuka, sesuai instruksi eksplisit.

        Mobile (<lg): TOC & WEBI jadi drawer/overlay (max-lg:fixed ...)
        alih-alih kolom sejajar -- 3 kolom tidak realistis di layar sempit.
        Materi tetap kolom tunggal biasa di semua ukuran layar.
    --}}
    <div x-data="{ tocOpen: true, webiOpen: false }">
        @if ($isReadOnlyExploration)
            <x-eksplorasi.read-only-banner />
        @endif

        {{--
            "Stack poin progres" (wireframe Aye, posisi ambigu -- asumsi
            yang dipakai: progres MODUL yang sedang dibuka + total poin
            user, gaya visual senada card poin lain di aplikasi). Sejajar
            breadcrumb (top-2/top-3, sama seperti breadcrumb.blade.php),
            containing block-nya <main> (relative), disembunyikan di layar
            paling sempit (hidden, muncul dari sm: ke atas) supaya tidak
            berebut ruang dengan breadcrumb di layar HP kecil.
        --}}
        <div class="absolute top-2 left-4 z-10 hidden sm:top-3 sm:block">
            <div class="flex items-center gap-2 rounded-xl border border-muted/10 bg-white px-3 py-1.5 shadow-warm-xs">
                <span class="font-mono text-xs text-caption">Modul {{ $unit->module->order_number }}</span>
                <div class="h-1.5 w-14 overflow-hidden rounded-full bg-muted/15">
                    <div class="h-full rounded-full bg-accent" style="width: {{ $moduleProgressPercentage }}%"></div>
                </div>
                <span class="font-pixel text-xs text-accent">{{ $moduleProgressPercentage }}%</span>
                <span class="text-muted/30">|</span>
                <span class="font-pixel text-xs text-accent">{{ $userProgress->total_points }}</span>
                <span class="text-[10px] text-caption">poin</span>
            </div>
        </div>

        {{-- Mobile-only trigger untuk buka drawer Daftar Isi Modul. --}}
        <div class="mt-10 lg:hidden">
            <button type="button" @click="tocOpen = true" class="inline-flex items-center gap-1.5 rounded-control border border-muted/40 px-3 py-1.5 text-xs font-medium text-ink hover:border-ink">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                    <path d="M4 6h16" /><path d="M4 12h16" /><path d="M4 18h7" />
                </svg>
                Daftar Isi Modul
            </button>
        </div>

        {{-- Backdrop dipakai bersama TOC & WEBI overlay (mobile saja, lg:hidden). --}}
        <div
            x-show="tocOpen || webiOpen"
            x-cloak
            x-transition:enter="transition-opacity duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="tocOpen = false; webiOpen = false"
            class="fixed inset-0 z-30 bg-ink/40 lg:hidden"
        ></div>

        <div class="mt-4 flex flex-col gap-6 lg:mt-6 lg:flex-row lg:items-start">
            {{--
                KOLOM KIRI: Daftar Isi Modul. Desktop: kolom inline, bisa
                menyusut jadi strip ikon (w-16) lewat tocOpen=false, TIDAK
                PERNAH hilang total (selalu ada wayfinding minimal). Mobile:
                drawer fixed dari kiri, tersembunyi total saat tertutup
                (max-lg:hidden) -- ruang terlalu sempit untuk strip persisten.
            --}}
            <div
                :class="tocOpen
                    ? 'w-64 max-lg:fixed max-lg:inset-y-0 max-lg:left-0 max-lg:z-40 max-lg:w-72 max-lg:shadow-warm-lg'
                    : 'w-16 max-lg:hidden'"
                class="shrink-0 overflow-y-auto rounded-2xl border border-muted/20 bg-white p-3 shadow-warm-xs transition-all duration-200 lg:sticky lg:top-24 lg:max-h-[calc(100vh-7rem)]"
            >
                <div class="flex items-center justify-between gap-2" :class="! tocOpen && 'lg:justify-center'">
                    <p x-show="tocOpen" class="truncate text-xs font-semibold uppercase tracking-wide text-caption">Modul {{ $unit->module->order_number }}</p>
                    <button type="button" @click="tocOpen = !tocOpen" :aria-expanded="tocOpen.toString()" aria-label="Minimize/expand daftar isi modul" class="shrink-0 rounded-control p-1 text-muted hover:bg-surface-alt hover:text-ink">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 transition-transform" :class="! tocOpen && 'rotate-180'">
                            <path d="m15 6-6 6 6 6" />
                        </svg>
                    </button>
                </div>

                <div class="mt-3 space-y-1">
                    @foreach ($moduleUnits as $entry)
                        @php
                            $entryUnit = $entry['unit'];
                            $iconClass = match (true) {
                                $entry['completed'] => 'bg-ink text-accent',
                                $entry['in_progress'] => 'bg-accent text-ink ring-2 ring-accent-soft',
                                $entry['locked'] => 'bg-white text-muted border-2 border-muted',
                                default => 'bg-white text-ink border-2 border-ink/30',
                            };
                        @endphp

                        @if ($entry['locked'])
                            <span class="flex items-center gap-2 rounded-lg px-2 py-2 text-sm text-muted opacity-60" :class="! tocOpen && 'lg:justify-center'" title="{{ $entryUnit->title }} (terkunci)">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full font-mono text-xs font-bold {{ $iconClass }}">&#128274;</span>
                                <span x-show="tocOpen" class="truncate">{{ $entryUnit->title }}</span>
                            </span>
                        @else
                            <a
                                href="{{ url('/eksplorasi/unit/'.$entryUnit->id) }}"
                                wire:navigate
                                class="flex items-center gap-2 rounded-lg px-2 py-2 text-sm transition-colors duration-150 hover:bg-surface-alt {{ $entry['is_current'] ? 'bg-accent-soft/40' : '' }}"
                                :class="! tocOpen && 'lg:justify-center'"
                                title="{{ $entryUnit->title }}"
                            >
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full font-mono text-xs font-bold {{ $iconClass }}">
                                    {{ $entry['completed'] ? '✓' : $entryUnit->order_number }}
                                </span>
                                <span x-show="tocOpen" class="truncate {{ $entry['is_current'] ? 'font-medium text-ink' : 'text-ink' }}">{{ $entryUnit->title }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>

                {{--
                    Putaran perbaikan Aye: tombol WEBI dipindah ke SINI
                    (bawah daftar isi, di dalam card TOC yang sama -- "stuck
                    mengikuti daftar isi", bukan strip terpisah di bawah
                    seluruh baris 3-kolom lagi). Default ikon saja; hover
                    memperlebar tombol menampilkan teks "Chat dengan WEBI"
                    lewat group-hover CSS murni (bukan state Alpine baru).
                    Teks HANYA muncul kalau tocOpen juga true (x-show) --
                    kalau TOC sedang diminimize jadi strip w-16, tidak cukup
                    ruang untuk teks meluas tanpa keluar batas kolom, jadi
                    sengaja disamakan aturannya dengan label unit lain di
                    atas (sama-sama x-show="tocOpen").
                --}}
                <div class="mt-3 border-t border-muted/15 pt-3">
                    <button
                        type="button"
                        @click="webiOpen = ! webiOpen"
                        class="group flex w-full items-center rounded-lg p-2 text-ink transition-colors duration-150 hover:bg-surface-alt"
                        :class="! tocOpen && 'lg:justify-center'"
                        aria-label="Chat dengan WEBI"
                    >
                        <x-brand.mascot variant="chat-icon" size="sm" class="shrink-0" />
                        <span
                            x-show="tocOpen"
                            class="max-w-0 truncate text-sm font-medium whitespace-nowrap opacity-0 transition-all duration-200 group-hover:ml-2 group-hover:max-w-40 group-hover:opacity-100"
                        >Chat dengan WEBI</span>
                    </button>
                </div>
            </div>

            {{-- KOLOM TENGAH: Materi. Fase 4 Batch 2a: unit yang sudah
                 diisi lewat Editor Blok Konten admin (contentBlocks tidak
                 kosong) dirender lewat <x-content-blocks>; unit yang belum
                 pernah disentuh editor (masih 0 blok) tetap fallback ke
                 kolom `content` teks polos lama -- migrasi 67 unit lama ke
                 blok satu-satu tetap di luar scope task ini, cuma jalur
                 BACA-nya yang sekarang siap sejak ada isi blok sungguhan. --}}
            <div class="relative min-w-0 flex-1 overflow-hidden rounded-2xl border border-muted/20 bg-white p-6 shadow-warm-md sm:p-8">
                <div class="card-pixel-accent-left" aria-hidden="true"></div>
                <div class="card-pixel-accent-right" aria-hidden="true"></div>

                <h1 class="font-display text-2xl font-bold text-heading">{{ $unit->title }}</h1>
                <p class="font-mono mt-1 text-xs text-caption">{{ $unit->estimated_minutes }} menit &middot; {{ $unit->point_value }} poin</p>

                <div class="mt-6 space-y-4 text-sm leading-relaxed text-body">
                    @if ($unit->contentBlocks->isNotEmpty())
                        <x-content-blocks :blocks="$unit->contentBlocks" />
                    @else
                        @foreach (explode("\n\n", $unit->content) as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    @endif
                </div>

                <div class="mt-8 border-t border-muted/20 pt-6">
                    <livewire:eksplorasi.unit-evaluation :unit="$unit" />
                </div>
            </div>

            {{--
                KOLOM KANAN: WEBI. Putaran perbaikan Aye (kedua): panel
                harus muat 1 layar penuh persis seperti side-panel
                Gemini/browser -- percobaan pertama (lg:h-[calc(100vh-7rem)]
                FIXED + overflow-hidden) ternyata masih terpotong di bawah.

                Root cause: sticky punya DUA posisi berbeda -- posisi
                NATURAL (sebelum sempat "nempel", ditentukan document flow
                biasa: tinggi navbar + padding <main> + margin baris ini,
                totalnya lebih dari lg:top-24/6rem) dan posisi STUCK
                (persis di lg:top-24 setelah discroll cukup jauh). Tinggi
                tetap (lg:h-...) dihitung seolah posisi awalnya SUDAH di
                6rem dari atas -- padahal di posisi NATURAL (halaman baru
                dibuka, belum discroll sama sekali -- persis situasi
                screenshot Aye) jaraknya lebih dari itu, jadi card jadi
                lebih tinggi dari sisa viewport yang benar-benar tersedia,
                bagian bawah (kotak ketik) kepotong di luar layar.

                Perbaikan: angka tinggi diperlonggar jauh (7rem -> 11rem,
                mencakup skenario terburuk posisi natural itu) supaya
                dijamin tidak overflow di KEDUA posisi (natural maupun
                stuck) -- tetap TINGGI TETAP (lg:h-..., bukan max-h) karena
                itu yang justru dibutuhkan supaya flex-1 di dalamnya
                (messages-box) benar-benar mengisi penuh sisa ruang section
                ini (memenuhi "efisien, muat 1 section" seperti contoh
                Gemini side-panel Aye) -- max-height saja cuma jadi
                plafon, tidak memaksa card mengisi ruang yang tersedia.
                overflow-y-auto tetap dipasang di card ini sendiri sebagai
                JARING PENGAMAN (bukan overflow-hidden lagi) -- kalau
                perhitungan rem ternyata masih meleset di kondisi nyata,
                card boleh scroll sendiri sebagai fallback aman, bukan
                diam-diam kepotong tanpa cara mengaksesnya. Selama flex-1
                di dalam bekerja normal, scrollbar ini tidak akan pernah
                muncul (tinggi card selalu pas terisi, tidak lebih).
            --}}
            <div
                x-show="webiOpen"
                x-cloak
                x-transition:enter="transition-opacity duration-150"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                :class="'max-lg:fixed max-lg:inset-0 max-lg:z-40 max-lg:p-4'"
                class="flex w-full shrink-0 flex-col overflow-y-auto rounded-2xl border border-muted/20 bg-white shadow-warm-md lg:sticky lg:top-24 lg:h-[calc(100vh-11rem)] lg:w-96"
            >
                <div class="flex items-center justify-end p-2 lg:hidden">
                    <button type="button" @click="webiOpen = false" aria-label="Tutup WEBI" class="rounded-control p-1 text-muted hover:bg-surface-alt hover:text-ink">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="M18 6 6 18" /><path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-hidden p-4 pt-0 lg:pt-4">
                    <livewire:eksplorasi.webi.chat :context-unit="$unit" :key="'webi-contextual-'.$unit->id" />
                </div>
            </div>
        </div>
    </div>
@endif

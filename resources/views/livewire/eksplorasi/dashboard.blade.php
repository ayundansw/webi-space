{{--
    Wrapper terluar POLOS (bukan card) -- dibutuhkan karena Livewire
    hanya mengizinkan SATU root element per komponen (dikonfirmasi lewat
    MultipleRootElementsDetectedException saat footer sempat ditulis
    sebagai sibling langsung dari card di bawah). Card `rounded-3xl
    bg-surface` dan footer sekarang jadi DUA child dari div ini,
    bukan dua root terpisah.
--}}
<div>
<div class="relative overflow-hidden rounded-3xl bg-surface p-6 sm:p-8">
    {{--
        Design token v2 (docs/v_2.0/archive/sumber-konsolidasi/design-tokens-v2.md §7 / brief §5): a thin
        circuit-style ornament, deliberately NOT the full curriculum node
        path (that stays exclusive to Peta Kurikulum per design-tokens.md
        §4) — just a light decorative echo of it, purely cosmetic.
    --}}
    <svg aria-hidden="true" viewBox="0 0 240 120" fill="none" stroke="currentColor" stroke-width="1.5" class="pointer-events-none absolute -top-6 -right-6 h-32 w-56 text-accent/20 sm:h-40 sm:w-72">
        <path d="M0 90 H70 L90 60 H150 L170 30 H240" />
        <circle cx="70" cy="90" r="4" fill="currentColor" stroke="none" />
        <circle cx="150" cy="60" r="4" fill="currentColor" stroke="none" />
        <circle cx="240" cy="30" r="4" fill="currentColor" stroke="none" />
    </svg>

    <div class="relative space-y-6">
        {{--
            Baris 1 (Rancangan Final §3.1): ikon WEBI (kiri, sempit) — sapaan
            (tengah, lebar) — Level + avatar Fox (kanan). <x-greeting> tetap
            dipakai apa adanya (shared 3 portal), cuma direposisi jadi kolom
            tengah baris ini alih-alih berdiri sendiri di atas.

            Flexbox + justify-between (bukan grid-cols-[auto_1fr_auto]
            arbitrary value yang dipakai sebelumnya) -- lebih predictable:
            dua ujung (ikon, kartu level) tetap natural-width, tengah
            (sapaan) otomatis mengisi sisa ruang lewat flex-1, tanpa perlu
            menghitung proporsi kolom manual.
        --}}
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="hidden shrink-0 sm:block">
                <x-brand.mascot variant="chat-icon" size="md" />
            </div>

            <div class="sm:flex-1 sm:px-2">
                <x-greeting />
            </div>

            <div class="flex shrink-0 items-center gap-3 rounded-xl border border-muted/20 bg-white px-4 py-3 shadow-warm-xs">
                <x-avatar.fox :tingkat="$foxTier" size="sm" />
                <div>
                    <p class="text-xs text-caption">Level {{ $userProgress->current_level }}</p>
                    <p class="font-display text-lg font-bold text-heading">{{ $userProgress->level_name }}</p>
                </div>
            </div>
        </div>

        {{--
            Putaran 3 — root cause lebar kolom tidak konsisten: Baris 2, 3,
            dan 4 sebelumnya masing-masing punya `<div class="grid ...">`
            SENDIRI dengan proporsi kolom berbeda (grid-cols-3 vs
            grid-cols-[2fr_1fr] vs grid-cols-2) -- tiga grid terpisah TIDAK
            ADA cara untuk dijamin align satu sama lain, karena browser
            menghitung lebar kolom tiap grid secara independen. 1/3 (baris
            2) secara matematis TIDAK SAMA dengan 1/2 (baris 4 lama),
            makanya tepi kolom meleset di screenshot Aye.

            Perbaikan: SATU grid besar (bukan tiga terpisah) membungkus
            Baris 2+3+4 sekaligus, `lg:grid-cols-3` didefinisikan SEKALI di
            sini. Tiap card jadi grid item langsung; card lebar (Modul
            Sedang Dikerjakan, Forum Terbaru) pakai `lg:col-span-2` supaya
            mengisi 2 dari 3 kolom (proporsi 2fr:1fr yang sama persis
            dengan baris Leaderboard/Daftar Praktik), card sempit
            (WEBI AI, Log Aktivitas) otomatis mengisi kolom ke-3 yang
            tersisa. Karena SEMUA baris berbagi definisi kolom yang sama,
            tepi kolom dijamin nyambung lurus secara matematis -- bukan
            cuma "kelihatan mirip".
        --}}
        <div class="grid grid-cols-1 items-stretch gap-6 lg:grid-cols-3">
            {{--
                Root cause perbaikan sebelumnya (Masalah 1): `h-full` di atas
                flex-col yang duduk di dalam grid cell items-stretch adalah
                pola RAPUH (percentage-height di dalam flex di dalam grid —
                area yang historisnya tidak konsisten antar browser). Diganti
                grid-rows-2 murni: div ini jadi grid item (otomatis stretch
                setinggi Leaderboard lewat items-stretch di atas, TIDAK perlu
                h-full lagi), lalu grid-rows-2 di dalamnya membagi tinggi itu
                jadi 2 track sama besar -- tiap card jadi grid item langsung
                yang ikut stretch mengisi track-nya masing-masing.
                Grid-di-dalam-grid jauh lebih well-tested daripada flex+h-full.

                Putaran 5: Total Poin & Progres Keseluruhan berhenti pakai
                <x-stat-card> generik -- keduanya sudah jadi desain bespoke
                (split kiri/kanan vs vertikal+visual besar) yang beda satu
                sama lain, dipertahankan sebagai markup langsung di sini
                (pola yang sama dipakai Leaderboard/Daftar Praktik/dst di
                bawah) supaya <x-stat-card> tetap murni generik untuk
                pemakai lain (KPI row Admin, dashboard Eksekusi).
            --}}
            <div class="grid grid-rows-2 gap-6">
                {{-- Total Poin: split 2 sisi -- label kiri, angka besar
                     font pixel warna accent kanan (permintaan Aye, lampiran
                     wireframe, Putaran 6: justify-center+gap terkontrol
                     bukan justify-between -- supaya label & angka jadi
                     satu kesatuan visual di tengah card, tidak mepet ke
                     tepi kiri/kanan; angka diperbesar text-3xl -> text-5xl
                     sesuai permintaan "perbesar lagi". --}}
                <div class="relative flex items-center justify-center gap-4 overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md">
                    <div class="card-pixel-accent-top" aria-hidden="true"></div>
                    <div class="card-pixel-accent-bottom" aria-hidden="true"></div>

                    <p class="text-sm font-medium text-muted">Total Poinmu :</p>
                    <p class="font-pixel text-5xl text-accent">{{ $userProgress->total_points }}</p>
                </div>

                {{-- Progres Keseluruhan: judul diberi jarak lebih lega
                     (mt-4, bukan mt-1 lama) sebelum konten, ditambah visual
                     angka persentase besar di atas progress bar yang
                     diperbesar+dihaluskan transisinya -- "visual tambahan"
                     yang diminta, bukan cuma bar tipis seperti sebelumnya.
                     Putaran 6: font angka persentase DIKEMBALIKAN ke
                     font-display biasa (bukan font-pixel) -- Aye cuma minta
                     pixel font untuk angka Total Poin, bukan di sini. --}}
                <div class="relative overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md">
                    <div class="card-pixel-accent-top" aria-hidden="true"></div>
                    <div class="card-pixel-accent-bottom" aria-hidden="true"></div>

                    <p class="text-sm font-medium text-muted">Progres Keseluruhan :</p>

                    <div class="mt-4">
                        <p class="font-display text-3xl font-bold text-accent">{{ $overallPercentage }}%</p>

                        <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-muted/15">
                            <div class="h-full rounded-full bg-accent transition-all duration-300" style="width: {{ $overallPercentage }}%"></div>
                        </div>

                        <p class="mt-2 text-right text-xs text-caption">Ayo lanjutkan progresmu!</p>
                    </div>
                </div>
            </div>

            @php
                $isInTop5 = collect($leaderboardTop5)->contains('is_self', true);

                $appreciationLines = [
                    'Kamu ada di jajaran atas minggu ini. Pertahankan ritmenya!',
                    'Keren, progresmu masuk yang terdepan. Lanjutkan!',
                    'Posisimu lagi bagus banget. Terus semangat belajarnya!',
                ];

                $motivationLines = [
                    'Sedikit lagi buat masuk Top 5. Yuk lanjutkan pelan-pelan!',
                    'Progresmu jalan terus, itu yang paling penting, bukan kecepatan.',
                    'Tiap unit yang kamu selesaikan bikin posisimu makin dekat ke atas.',
                ];

                $leaderboardMessage = $isInTop5
                    ? $appreciationLines[array_rand($appreciationLines)]
                    : $motivationLines[array_rand($motivationLines)];
            @endphp

            <div class="relative overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md">
                <div class="card-pixel-accent-top" aria-hidden="true"></div>
                <div class="card-pixel-accent-bottom" aria-hidden="true"></div>

                <div class="flex items-center justify-between">
                    <h2 class="font-display text-base font-semibold text-heading">Leaderboard</h2>
                    <span class="font-mono text-xs text-caption">Top 5</span>
                </div>

                <p class="mt-1 text-sm text-body">{{ $leaderboardMessage }}</p>

                <div class="mt-4 space-y-1.5">
                    @foreach ($leaderboardTop5 as $row)
                        <div class="flex items-center justify-between rounded-lg px-3 py-2 text-sm {{ $row['is_self'] ? 'border border-accent bg-accent-soft/40' : 'border border-transparent' }}">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full font-mono text-xs font-medium {{ $row['rank'] <= 3 ? 'bg-ink text-white' : 'bg-muted/15 text-muted' }}">
                                    {{ $row['rank'] }}
                                </span>
                                <span class="text-ink {{ $row['is_self'] ? 'font-medium' : '' }}">
                                    {{ $row['name'] }}{{ $row['is_self'] ? ' (Kamu)' : '' }}
                                </span>
                            </div>
                            <span class="font-mono text-xs text-ink">{{ $row['points'] }} poin</span>
                        </div>
                    @endforeach

                    @if ($leaderboardSelf)
                        <div class="mt-2 flex items-center justify-between rounded-lg border border-dashed border-muted/40 px-3 py-2 text-sm">
                            <span class="text-ink">Kamu peringkat {{ $leaderboardSelf['rank'] }}</span>
                            <span class="font-mono text-xs text-ink">{{ $leaderboardSelf['points'] }} poin</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md">
                <div class="card-pixel-accent-top" aria-hidden="true"></div>
                <div class="card-pixel-accent-bottom" aria-hidden="true"></div>

                <div class="flex items-center justify-between">
                    <h2 class="font-display text-base font-semibold text-heading">Daftar Praktik</h2>
                    <a href="{{ url('/eksplorasi/praktik') }}" wire:navigate aria-label="Lihat semua Praktik" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-control bg-ink text-white hover:bg-ink/90">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M5 12h14" /><path d="m13 6 6 6-6 6" />
                        </svg>
                    </a>
                </div>

                <div class="mt-3 space-y-2">
                    @forelse ($latestChallenges as $challenge)
                        <a href="{{ url('/eksplorasi/praktik/'.$challenge->id) }}" wire:navigate class="block rounded-lg border border-muted/20 bg-white px-4 py-3 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md">
                            <p class="text-sm font-medium text-ink">{{ $challenge->title }}</p>
                            <p class="mt-1 text-xs text-caption">{{ strtoupper($challenge->level) }} &middot; {{ $challenge->points_reward }} poin</p>
                        </a>
                    @empty
                        <div class="flex items-center gap-3 rounded-lg border border-dashed border-muted/25 bg-white px-4 py-4">
                            <p class="text-sm text-muted">Belum ada challenge Praktik yang tersedia.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{--
                Baris 3: Modul yang Sedang Dikerjakan (lebar, col-span-2) —
                Kartu WEBI AI (kolom ke-3 yang tersisa). Putaran 4: strip
                solid `bg-warm` di tepi kiri (Round 1) DIGANTI aksen
                pixel-art left+right. Putaran 7: warna aksen disamakan
                dengan card lain (cyan/--color-accent, bukan varian --warm
                lagi) sesuai permintaan Aye supaya seluruh card di
                keluarga left/right konsisten satu warna aksen.
            --}}
            <div class="relative overflow-hidden rounded-2xl border border-muted/20 bg-white p-6 shadow-warm-md transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-lg lg:col-span-2">
                <div class="card-pixel-accent-left" aria-hidden="true"></div>
                <div class="card-pixel-accent-right" aria-hidden="true"></div>

                <p class="text-xs font-medium text-warm">Modul yang Sedang Dikerjakan</p>
                @if ($nextUnit)
                    <p class="font-display mt-1 text-xl font-bold text-heading">{{ $nextUnit->title }}</p>
                    <a href="{{ url('/eksplorasi/unit/'.$nextUnit->id) }}" wire:navigate class="mt-3 inline-flex items-center gap-1.5 rounded-control bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">
                        Lanjut Belajar
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M5 12h14" /><path d="m13 6 6 6-6 6" />
                        </svg>
                    </a>
                @else
                    <p class="mt-1 text-sm text-body">Semua unit yang tersedia sudah kamu selesaikan. Keren banget!</p>
                    <a href="{{ url('/eksplorasi/kurikulum') }}" wire:navigate class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-ink underline hover:text-accent">
                        Lihat Peta Kurikulum
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                            <path d="M5 12h14" /><path d="m13 6 6 6-6 6" />
                        </svg>
                    </a>
                @endif
            </div>

            <div class="relative flex flex-col justify-center gap-3 overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md">
                <div class="card-pixel-accent-left" aria-hidden="true"></div>
                <div class="card-pixel-accent-right" aria-hidden="true"></div>

                <div class="flex items-center gap-3">
                    <x-brand.mascot variant="chat-icon" size="md" />
                    <div>
                        <h2 class="font-display text-base font-semibold text-heading">WEBI AI</h2>
                        <p class="mt-1 text-sm text-body">Bingung soal materi? WEBI siap bantu jelasin pelan-pelan, kapan saja.</p>
                    </div>
                </div>
                <a href="{{ route('eksplorasi.webi') }}" wire:navigate class="inline-flex w-full items-center justify-center gap-1.5 rounded-control bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M4 5h13v9H8l-4 4V5Z" />
                    </svg>
                    Mulai Chat
                </a>
            </div>

            {{--
                Baris 4: Preview Forum Thread terbaru (BARU) — Log
                Aktivitas (sudah ada). Putaran 4: dua-duanya SEBELUMNYA
                cuma <div> polos tanpa card wrapper apa pun -- aksen
                pixel left/right butuh parent `relative overflow-hidden`
                BERPADDING supaya tidak mepet konten, jadi sekarang ada
                card LUAR (dekoratif: border+shadow+aksen pixel,
                col-span-2 di Forum supaya tepinya align dengan Modul
                Sedang Dikerjakan) membungkus card DALAM (bg-surface,
                menampung heading+list) -- card-di-dalam-card, beda dari
                Modul/WEBI AI yang cukup 1 layer karena isinya bukan
                daftar card-di-dalam-card lagi.
            --}}
            <div class="relative overflow-hidden rounded-2xl border border-muted/20 bg-white p-5 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md lg:col-span-2">
                <div class="card-pixel-accent-left" aria-hidden="true"></div>
                <div class="card-pixel-accent-right" aria-hidden="true"></div>

                <div class="rounded-xl bg-surface/60 p-4">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display text-base font-semibold text-heading">Forum Terbaru</h2>
                        <a href="{{ url('/eksplorasi/forum') }}" wire:navigate aria-label="Lihat semua thread forum" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-control bg-ink text-white hover:bg-ink/90">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <path d="M5 12h14" /><path d="m13 6 6 6-6 6" />
                            </svg>
                        </a>
                    </div>
                    <div class="mt-3 space-y-2">
                        @forelse ($latestThreads as $thread)
                            <a href="{{ url('/eksplorasi/forum/'.$thread->id) }}" wire:navigate class="block rounded-lg border border-muted/20 bg-white px-4 py-3 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md">
                                <p class="text-sm font-medium text-ink">{{ $thread->title }}</p>
                                <p class="mt-1 text-xs text-caption">
                                    {{ $thread->creator->name }}
                                    @if ($thread->module)
                                        &middot; Modul {{ $thread->module->order_number }}
                                    @endif
                                    @if ($thread->unit)
                                        &middot; Unit: {{ $thread->unit->title }}
                                    @endif
                                </p>
                            </a>
                        @empty
                            <div class="flex items-center gap-3 rounded-lg border border-dashed border-muted/25 bg-white px-4 py-4">
                                <p class="text-sm text-muted">Belum ada thread. Yuk mulai diskusi pertama di Forum!</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-2xl border border-muted/20 bg-white p-5 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md">
                <div class="card-pixel-accent-left" aria-hidden="true"></div>
                <div class="card-pixel-accent-right" aria-hidden="true"></div>

                <div class="rounded-xl bg-surface/60 p-4">
                    <h2 class="font-display text-base font-semibold text-heading">Log Aktivitas</h2>
                    <div class="mt-3 space-y-2">
                        @forelse ($feed as $entry)
                            <div class="rounded-lg border border-muted/20 bg-white px-4 py-3 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md">
                                <p class="text-sm text-ink">{{ $entry['message'] }}</p>
                                <p class="font-mono mt-1 text-xs text-caption">{{ \Illuminate\Support\Carbon::parse($entry['timestamp'])->diffForHumans() }}</p>
                            </div>
                        @empty
                            <div class="flex items-center gap-3 rounded-lg border border-dashed border-muted/25 bg-white px-4 py-4">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6 shrink-0 text-warm">
                                    <path d="M12 2 12 8" />
                                    <path d="m8 6 4 2 4-2" />
                                    <path d="M6 11c0 5 2.5 9 6 11 3.5-2 6-6 6-11-2-1-4-3-6-6-2 3-4 5-6 6Z" />
                            </svg>
                            <p class="text-sm text-muted">Belum ada aktivitas. Yuk mulai dari Modul 1 di Peta Kurikulum!</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{--
    Footer sederhana penanda "ujung halaman" (BARU, Fase 3 Batch 1
    Putaran 4). Sengaja di LUAR card `rounded-3xl bg-surface` di atas
    (sibling di dalam wrapper polos yang sama, bukan child card itu)
    supaya terasa sebagai penutup halaman, bukan bagian dari isi card
    dashboard.
--}}
<div class="mt-8 flex items-center justify-center gap-3 py-4 text-center">
    <x-brand.mascot variant="chat-icon" size="sm" class="opacity-50" />
    <p class="text-xs text-caption">Kamu sudah mencapai batas halaman</p>
    <x-brand.mascot variant="chat-icon" size="sm" class="opacity-50" />
</div>
</div>

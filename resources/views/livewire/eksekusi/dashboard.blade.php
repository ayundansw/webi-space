@php
    // Ikon status proyek, gaya sama dengan eksekusi/projects/index.blade.php
    // (viewBox 24, stroke-width 1.75) tapi warnanya diselaraskan ke token
    // final (§4.0: "warna ikut Modul 1") -- warning/success/accent, bukan
    // Tailwind ad-hoc (amber-600/green-600) seperti versi lama.
    $statusIcons = [
        'planning' => '<path d="M9 4h6v3H9Z" /><path d="M7 5H6a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-1" />',
        'active' => '<path d="M8 5v14l11-7Z" />',
        'on_hold' => '<rect x="7" y="5" width="3.5" height="14" rx="1" /><rect x="13.5" y="5" width="3.5" height="14" rx="1" />',
        'completed' => '<path d="m5 13 4 4 10-10" />',
        'archived' => '<path d="M4 7h16v3H4Z" /><path d="M5 10v10h14V10" /><path d="M10 14h4" />',
    ];

    $statusColors = [
        'planning' => 'text-muted',
        'active' => 'text-accent',
        'on_hold' => 'text-warning',
        'completed' => 'text-success',
        'archived' => 'text-muted',
    ];
@endphp

{{--
    Fase 3 — Dashboard Eksekusi dibangun lengkap (RANCANGAN_FINAL_WEBI-SPACE_v2.md
    §4.1), disamakan bahasa visualnya dengan Dashboard Eksplorasi yang sudah
    final (3 putaran perbaikan): SATU grid besar per kelompok baris (bukan
    grid terpisah per baris -- pelajaran dari Putaran 3 Eksplorasi, supaya
    tepi kolom antar baris terjamin align secara matematis), `items-stretch`
    supaya card sebaris otomatis sejajar tinggi, aksen pixel-art checkerboard
    (top+bottom untuk card keluarga "stat", left+right untuk card keluarga
    "konten" -- sama seperti Eksplorasi), dan aksen sirkuit sudut dekoratif
    yang sama persis (§4.1: "aksen sirkuit sudut").

    Badge Status Mode (komponen §4.1 poin 1) TIDAK ditambahkan di sini --
    sudah terpasang GLOBAL di navbar (resources/views/components/shell/navbar.blade.php,
    <x-shell.mode-badge />), bukan per-halaman dashboard, jadi otomatis
    tampil di sini juga tanpa kerja tambahan begitu Fase 8 mengaktifkannya.
--}}
<div class="relative overflow-hidden rounded-3xl bg-surface p-6 sm:p-8">
    <svg aria-hidden="true" viewBox="0 0 240 120" fill="none" stroke="currentColor" stroke-width="1.5" class="pointer-events-none absolute -top-6 -right-6 h-32 w-56 text-accent/20 sm:h-40 sm:w-72">
        <path d="M0 90 H70 L90 60 H150 L170 30 H240" />
        <circle cx="70" cy="90" r="4" fill="currentColor" stroke="none" />
        <circle cx="150" cy="60" r="4" fill="currentColor" stroke="none" />
        <circle cx="240" cy="30" r="4" fill="currentColor" stroke="none" />
    </svg>

    <div class="relative space-y-6">
        {{-- Baris 1: Greeting (shared <x-greeting>, otomatis dapat nama
             beraksen + font pixel dari komponen yang sama dipakai Eksplorasi). --}}
        <div>
            <x-greeting />
            <p class="mt-1 text-sm text-body">Ringkasan proyek dan task yang kamu ikuti.</p>
        </div>

        {{--
            SATU grid besar (lg:grid-cols-3) membungkus Baris 2+3+4 sekaligus
            -- prinsip yang sama dengan Dashboard Eksplorasi Putaran 3, supaya
            tepi kolom "Proyek yang Kamu Ikuti"/"Peringatan" (col-span-2) dan
            "Ringkasan Kalender"/"Antrian Review Praktik" (col-span-1) selalu
            align lurus tanpa perlu grid terpisah per baris.
        --}}
        <div class="grid grid-cols-1 items-stretch gap-6 lg:grid-cols-3">
            {{-- Baris 2, kolom 1: Proyek Diikuti -- pola SAMA persis dengan
                 "Total Poin" Eksplorasi (label+angka besar font pixel warna
                 accent, satu kesatuan di tengah, bukan mepet ke tepi). --}}
            <div class="relative flex items-center justify-center gap-4 overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md">
                <div class="card-pixel-accent-top" aria-hidden="true"></div>
                <div class="card-pixel-accent-bottom" aria-hidden="true"></div>

                <p class="text-sm font-medium text-muted">Proyek Diikuti :</p>
                <p class="font-pixel text-5xl text-accent">{{ $projectCount }}</p>
            </div>

            {{-- Baris 2, kolom 2: Task Aktif -- angka besar (total Todo+In
                 Progress+In Review) plus breakdown kecil di bawahnya. --}}
            <div class="relative overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md">
                <div class="card-pixel-accent-top" aria-hidden="true"></div>
                <div class="card-pixel-accent-bottom" aria-hidden="true"></div>

                <p class="text-sm font-medium text-muted">Task Aktif :</p>
                <div class="mt-2 flex items-center justify-center">
                    <p class="font-pixel text-5xl text-accent">{{ $activeTaskCount }}</p>
                </div>
                <div class="mt-3 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-xs text-caption">
                    <span>Todo <span class="font-mono font-semibold text-ink">{{ $taskCounts['todo'] }}</span></span>
                    <span>In Progress <span class="font-mono font-semibold text-ink">{{ $taskCounts['in_progress'] }}</span></span>
                    <span>In Review <span class="font-mono font-semibold text-ink">{{ $taskCounts['in_review'] }}</span></span>
                </div>
            </div>

            {{-- Baris 2, kolom 3: Praktik Menunggu Direview -- BARU di §4.1,
                 baru aktif Fase 5 (§4.5, assigned_reviewer_id). Placeholder,
                 pola identik "Daftar Praktik" di Dashboard Eksplorasi. --}}
            <div class="relative flex flex-col items-center justify-center gap-2 overflow-hidden rounded-xl border border-dashed border-muted/40 bg-white/60 p-5 text-center opacity-70">
                <div class="card-pixel-accent-top" aria-hidden="true"></div>
                <div class="card-pixel-accent-bottom" aria-hidden="true"></div>

                <span class="rounded-full bg-muted/15 px-3 py-1 font-mono text-[10px] font-medium uppercase tracking-wide text-caption">Segera Hadir</span>
                <h2 class="font-display text-sm font-semibold text-heading">Praktik Menunggu Direview</h2>
                <p class="text-xs text-caption">Muncul kalau admin menugaskanmu sebagai reviewer submission Praktik.</p>
            </div>

            {{-- Baris 3, kolom 1-2 (span 2): Proyek yang Kamu Ikuti -- card
                 terpenting di dashboard ini (analog "Modul Sedang Dikerjakan"
                 Eksplorasi), redesign dari list polos sebelumnya jadi card
                 di dalam card dengan status badge + progress bar per proyek. --}}
            <div class="relative overflow-hidden rounded-2xl border border-muted/20 bg-white p-6 shadow-warm-md transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-lg lg:col-span-2">
                <div class="card-pixel-accent-left" aria-hidden="true"></div>
                <div class="card-pixel-accent-right" aria-hidden="true"></div>

                <div class="flex items-center justify-between">
                    <h2 class="font-display text-base font-semibold text-heading">Proyek yang Kamu Ikuti</h2>
                    <a href="{{ url('/eksekusi/projects') }}" wire:navigate aria-label="Lihat semua proyek" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-control bg-ink text-white hover:bg-ink/90">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M5 12h14" /><path d="m13 6 6 6-6 6" />
                        </svg>
                    </a>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($projects as $summary)
                        <a href="{{ url('/eksekusi/projects/'.$summary['project']->id) }}" wire:navigate class="block rounded-xl border border-muted/20 bg-white p-4 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-medium text-ink">{{ $summary['project']->title }}</p>
                                    <p class="mt-1 text-xs text-caption">
                                        {{ ucfirst($summary['project']->project_type) }} &middot; {{ $summary['task_count'] }} task
                                        @if ($summary['next_milestone'])
                                            &middot; milestone terdekat: {{ $summary['next_milestone']->title }} ({{ $summary['next_milestone']->target_date->format('d M Y') }})
                                        @endif
                                    </p>
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-muted/40 px-2 py-1 font-mono text-xs uppercase text-ink">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 shrink-0 {{ $statusColors[$summary['project']->status] ?? 'text-muted' }}">
                                        {!! $statusIcons[$summary['project']->status] ?? '' !!}
                                    </svg>
                                    {{ $summary['project']->status }}
                                </span>
                            </div>

                            <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-muted/15">
                                <div class="h-full rounded-full bg-accent" style="width: {{ $summary['progress'] }}%"></div>
                            </div>
                            <p class="mt-1 font-mono text-xs text-caption">{{ $summary['progress'] }}% task selesai</p>
                        </a>
                    @empty
                        <div class="rounded-lg border border-dashed border-muted/25 bg-white px-4 py-4">
                            <p class="text-sm text-muted">Kamu belum tergabung di proyek apa pun. Cek Project Ideas untuk mulai mengusulkan proyek baru.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Baris 3, kolom 3: Ringkasan Kalender -- BARU di §4.1, data
                 asli sejak Fase 7 Batch 2b (App\Services\Execution\CalendarService,
                 sama sumber dengan halaman Kalender Personal). --}}
            <div class="relative flex flex-col overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs">
                <div class="card-pixel-accent-left" aria-hidden="true"></div>
                <div class="card-pixel-accent-right" aria-hidden="true"></div>

                <div class="flex items-center justify-between">
                    <h2 class="font-display text-sm font-semibold text-heading">Ringkasan Kalender</h2>
                    <a href="{{ route('eksekusi.kalender') }}" wire:navigate aria-label="Lihat Kalender Personal" class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-control bg-ink text-white hover:bg-ink/90">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                            <path d="M5 12h14" /><path d="m13 6 6 6-6 6" />
                        </svg>
                    </a>
                </div>
                <div class="mt-2 space-y-1.5">
                    @forelse ($upcomingCalendarItems as $item)
                        <div class="flex items-center gap-2 text-xs">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $item['category'] === 'kegiatan' ? 'bg-ink' : 'bg-blue-500' }}"></span>
                            <span class="font-mono text-caption">{{ $item['date']->locale('id')->translatedFormat('d M') }}</span>
                            <span class="truncate text-ink">{{ $item['title'] }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-caption">Tidak ada kegiatan/acara mendatang.</p>
                    @endforelse
                </div>
            </div>

            {{-- Baris 4, kolom 1-2 (span 2): Alert Panel -- sudah ada sejak
                 2.4, di sini cuma re-skin warna ke token final. --}}
            <div class="relative overflow-hidden rounded-2xl border border-muted/20 bg-white p-6 shadow-warm-md transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-lg lg:col-span-2">
                <div class="card-pixel-accent-left" aria-hidden="true"></div>
                <div class="card-pixel-accent-right" aria-hidden="true"></div>

                <h2 class="font-display text-base font-semibold text-heading">Peringatan</h2>

                <div class="mt-4 space-y-2">
                    @forelse ($alerts as $alert)
                        @php
                            // severity 3 (OVERDUE/AT RISK/IDLE) -> danger;
                            // severity 2 (STALLED) & 1 (DUE SOON) berbagi
                            // keluarga warning (tidak ada token "orange"
                            // terpisah), dibedakan lewat opacity border/bg.
                            $alertClass = match ($alert['severity']) {
                                3 => 'border-danger/40 bg-danger-soft',
                                2 => 'border-warning/60 bg-warning-soft',
                                default => 'border-warning/25 bg-warning-soft/60',
                            };
                        @endphp
                        <div class="rounded-lg border p-3 text-sm {{ $alertClass }}">
                            <span class="font-mono text-xs font-medium uppercase text-ink">{{ $alert['label'] }}</span>
                            <p class="mt-1 text-ink">{{ $alert['text'] }}</p>
                            @if ($alert['task'])
                                <a href="{{ url('/eksekusi/tasks/'.$alert['task']->id) }}" wire:navigate class="mt-1 inline-block text-xs text-accent hover:underline">Lihat task &rarr;</a>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-muted/25 bg-white px-4 py-4">
                            <p class="text-sm text-muted">Tidak ada peringatan untuk proyekmu saat ini. Semua aman!</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Baris 4, kolom 3: Antrian Review Praktik -- Praktik 3 Bagian
                 C, sekarang data asli (submission yang ditugaskan admin ke
                 anggota ini, status pending). --}}
            <div class="relative flex flex-col overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs">
                <div class="card-pixel-accent-left" aria-hidden="true"></div>
                <div class="card-pixel-accent-right" aria-hidden="true"></div>

                <h2 class="font-display text-sm font-semibold text-heading">Antrian Review Praktik</h2>
                <div class="mt-2 space-y-2">
                    @forelse ($reviewQueue as $submission)
                        <a
                            href="{{ url('/eksekusi/praktik/submissions/'.$submission->id) }}"
                            wire:navigate
                            class="block rounded-lg border border-muted/20 bg-white px-3 py-2 text-xs shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md"
                        >
                            <p class="font-medium text-ink">{{ $submission->challenge->title }}</p>
                            <p class="mt-0.5 text-caption">{{ $submission->user->name }} &middot; Percobaan ke-{{ $submission->attempt_number }}</p>
                        </a>
                    @empty
                        <p class="text-xs text-caption">Tidak ada submission yang ditugaskan ke kamu saat ini.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{--
            Baris 5: CTA banner "Ajukan Project Idea" -- full width, di luar
            grid 3-kolom di atas (banner tunggal tidak butuh alignment kolom
            dengan apa pun). Warna accent dipakai menonjol (bukan cuma aksen
            kecil) SENGAJA di sini karena kartu ini MEMANG dimaksudkan jadi
            CTA yang mengundang perhatian (§4.1 poin 7: "styling menonjol/
            mengundang aksi") -- satu-satunya card di dashboard ini yang
            boleh melanggar prinsip "aksen kecil saja", karena perannya
            memang beda (ajakan bertindak, bukan tampilan info).
        --}}
        <div class="relative overflow-hidden rounded-2xl border border-accent/30 bg-accent-soft/40 p-6 shadow-warm-md transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-lg">
            <div class="card-pixel-accent-left" aria-hidden="true"></div>
            <div class="card-pixel-accent-right" aria-hidden="true"></div>

            <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-ink text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="M9 18h6" />
                            <path d="M10 22h4" />
                            <path d="M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.3 1 2.3h6c0-1 .4-1.8 1-2.3A7 7 0 0 0 12 2Z" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="font-display text-base font-semibold text-heading">Punya Ide Proyek?</h2>
                        <p class="mt-1 text-sm text-body">Ajukan Project Idea kamu -- admin akan meninjau, dan bisa langsung dieksekusi jadi proyek baru.</p>
                    </div>
                </div>
                <a href="{{ route('eksekusi.ideas.create') }}" wire:navigate class="inline-flex w-full shrink-0 items-center justify-center gap-1.5 rounded-control bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 sm:w-auto">
                    Ajukan Project Idea
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M5 12h14" /><path d="m13 6 6 6-6 6" />
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>

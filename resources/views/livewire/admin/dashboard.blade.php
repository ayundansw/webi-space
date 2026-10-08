<div>
    <x-greeting />
    <p class="mt-1 text-sm text-muted">Satu panel terpadu untuk Eksplorasi, Eksekusi, dan WEBI, tidak perlu berpindah halaman untuk gambaran keseluruhan.</p>

    <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <x-stat-card label="Anggota Eksplorasi" :value="$explorationLeaderboard->count()" />
        <x-stat-card label="Anggota Eksekusi" :value="$memberSummaries->count()" />
        <x-stat-card label="Proyek Aktif" :value="$projects->count()" />
        <x-stat-card label="Peringatan Aktif" :value="$alerts->count()" />
        <x-stat-card label="Pesan WEBI" :value="$webiSummary['total_messages']" />
    </div>

    {{--
        Fase 3 Batch B (polesan panel admin, 2026-07-17): tiap kategori besar
        sekarang satu card terpadu (rounded-card + shadow-warm-xs, token yang
        sama dipakai Kanban/Roadmap/Ideas) dengan panel-panel di dalamnya,
        BUKAN rentetan card lepas berbobot visual sama seperti sebelumnya.
        Jarak antar KATEGORI pakai mt-12 (besar, mata langsung menangkap batas
        tanpa baca judul dulu), jarak di DALAM satu kategori tetap mt-4/mt-6
        (rapat, terasa satu kesatuan) -- ini yang membedakan hierarki
        kategori-besar vs sub-informasi vs detail. Data yang ditampilkan
        (5 metrik, Eksplorasi, Eksekusi, Log WEBI, Status Kurikulum, Antrian
        Mode Ganda) semuanya dipertahankan utuh, cuma disusun ulang.
    --}}

    <section class="mt-12">
        <h2 class="font-display text-xl font-bold text-ink">Eksplorasi</h2>

        <div class="mt-4 rounded-card border border-muted/20 bg-white p-6 shadow-warm-xs">
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-5">
                <div class="lg:col-span-3">
                    <h3 class="font-display text-base font-bold text-ink">Progres Anggota</h3>
                    <div class="mt-3 overflow-x-auto rounded-control border border-muted/20">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-muted/20 bg-accent-soft/20 text-muted">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Anggota</th>
                                    <th class="px-4 py-3 font-medium">Sedang Dikerjakan</th>
                                    <th class="px-4 py-3 font-medium">Level</th>
                                    <th class="px-4 py-3 font-medium">Progres</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($explorationLeaderboard as $row)
                                    <tr class="border-b border-muted/15 last:border-0">
                                        <td class="px-4 py-3 text-ink">{{ $row['member']->name }}</td>
                                        <td class="px-4 py-3 text-muted">{{ $row['current_unit']?->title ?? 'Belum mulai' }}</td>
                                        <td class="px-4 py-3 font-mono text-xs text-ink">{{ $row['progress']->level_name }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <div class="h-1.5 w-16 rounded-full bg-muted/20">
                                                    <div class="h-1.5 rounded-full bg-accent" style="width: {{ $row['overall_percentage'] }}%"></div>
                                                </div>
                                                <span class="font-mono text-xs text-muted">{{ $row['overall_percentage'] }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-muted">Belum ada anggota eksplorasi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="lg:col-span-2 lg:border-l lg:border-muted/15 lg:pl-8">
                    <h3 class="font-display text-base font-bold text-ink">Leaderboard</h3>
                    <p class="mt-1 text-xs text-muted">Khusus admin: tidak pernah ditampilkan ke anggota eksplorasi (PRD 3.1.4).</p>
                    <div class="mt-3 space-y-1.5">
                        @foreach ($explorationLeaderboard as $index => $row)
                            <div class="flex items-center justify-between rounded-control border border-muted/20 px-3 py-2 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full font-mono text-xs font-medium {{ $index < 3 ? 'bg-ink text-white' : 'bg-muted/15 text-muted' }}">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="text-ink">{{ $row['member']->name }}</span>
                                </div>
                                <span class="font-mono text-xs text-ink">{{ $row['progress']->total_points }} poin</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-12">
        <h2 class="font-display text-xl font-bold text-ink">Sistem &amp; Lintas Modul</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-card border border-muted/20 bg-white p-4 shadow-warm-xs">
                <h3 class="font-display text-sm font-bold text-ink">Log Percakapan WEBI</h3>
                <p class="mt-1 text-xs text-muted font-mono">
                    {{ $webiSummary['total_messages'] }} pesan &middot; {{ $webiSummary['member_count'] }} anggota
                    &middot; <span class="{{ $webiSummary['total_flags'] > 0 ? 'text-danger' : '' }}">{{ $webiSummary['total_flags'] }} guardrail flag</span>
                    @if ($webiSummary['last_message_at'])
                        &middot; terakhir {{ $webiSummary['last_message_at']->diffForHumans() }}
                    @endif
                </p>
                <a href="{{ url('/admin/webi') }}" class="mt-3 inline-block rounded-control border border-muted/40 px-3 py-1.5 text-sm text-ink hover:border-accent hover:text-accent">Lihat semua log &rarr;</a>
            </div>

            <div class="rounded-card border border-muted/20 bg-white p-4 shadow-warm-xs">
                <h3 class="font-display text-sm font-bold text-ink">Status Kurikulum</h3>
                <p class="mt-1 text-xs text-muted font-mono">
                    {{ $curriculumSummary['module_count'] }} modul &middot; {{ $curriculumSummary['unit_count'] }} unit
                    &middot; {{ $curriculumSummary['units_with_content_blocks'] }}/{{ $curriculumSummary['unit_count'] }} sudah pakai Editor Blok Konten
                </p>
                <a href="{{ url('/admin/curriculum/modules') }}" class="mt-3 inline-block rounded-control border border-muted/40 px-3 py-1.5 text-sm text-ink hover:border-accent hover:text-accent">Kelola kurikulum &rarr;</a>
            </div>

            {{-- Fase 8 Batch 5 (§5.6 "Antrian Permintaan Mode Ganda"). --}}
            <div class="rounded-card border border-muted/20 bg-white p-4 shadow-warm-xs">
                <h3 class="font-display text-sm font-bold text-ink">Antrian Permintaan Mode Ganda</h3>
                <p class="mt-1 text-xs text-muted font-mono">
                    {{ $dualModePendingCount }} permintaan menunggu keputusan
                </p>
                <a href="{{ url('/admin/dual-mode') }}" class="mt-3 inline-block rounded-control border border-muted/40 px-3 py-1.5 text-sm text-ink hover:border-accent hover:text-accent">Lihat antrian &rarr;</a>
            </div>
        </div>
    </section>

    <section class="mt-12">
        <h2 class="font-display text-xl font-bold text-ink">Eksekusi</h2>

        <div class="mt-4 rounded-card border border-muted/20 bg-white p-6 shadow-warm-xs">
            <div>
                <h3 class="font-display text-base font-bold text-ink">Ringkasan Proyek Aktif</h3>
                <div class="mt-3 space-y-3">
                    @forelse ($projects as $summary)
                        <a href="{{ url('/eksekusi/projects/'.$summary['project']->id) }}" class="block rounded-control border border-muted/20 p-4 hover:bg-accent-soft/20">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-ink">{{ $summary['project']->title }}</p>
                                    <p class="mt-1 text-xs text-muted">{{ ucfirst($summary['project']->project_type) }} &middot; {{ $summary['active_members'] }} anggota aktif &middot; {{ $summary['days_left'] }} hari lagi</p>
                                </div>
                                <span class="rounded-control border border-muted/40 px-2 py-1 text-xs font-mono uppercase text-ink">{{ $summary['project']->status }}</span>
                            </div>

                            <div class="mt-2 h-1.5 rounded-full bg-muted/20">
                                <div class="h-1.5 rounded-full bg-accent" style="width: {{ $summary['progress'] }}%"></div>
                            </div>
                            <p class="mt-1 font-mono text-xs text-muted">{{ $summary['progress'] }}% task selesai</p>

                            @if ($summary['milestones']->isNotEmpty())
                                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    @foreach ($summary['milestones'] as $milestone)
                                        <div class="text-xs">
                                            <div class="flex justify-between text-muted">
                                                <span>{{ $milestone->title }}</span>
                                                <span class="font-mono">{{ $milestone->progressPercentage() }}%</span>
                                            </div>
                                            <div class="mt-0.5 h-1 rounded-full bg-muted/20">
                                                <div class="h-1 rounded-full bg-accent" style="width: {{ $milestone->progressPercentage() }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </a>
                    @empty
                        <p class="text-sm text-muted">Belum ada proyek aktif.</p>
                    @endforelse
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-8 border-t border-muted/15 pt-6 lg:grid-cols-3">
                <div>
                    <h3 class="font-display text-base font-bold text-ink">Alert Panel</h3>
                    <div class="mt-3 space-y-2">
                        @forelse ($alerts as $alert)
                            <div class="rounded-control border p-3 text-sm {{ $alert['severity'] === 3 ? 'border-danger/40 bg-danger-soft' : ($alert['severity'] === 2 ? 'border-warning/40 bg-warning-soft' : 'border-warning/30 bg-warning-soft/60') }}">
                                <span class="font-mono text-xs font-medium uppercase">{{ $alert['label'] }}</span>
                                <p class="mt-1 text-ink">{{ $alert['text'] }}</p>
                                @if ($alert['task'])
                                    <a href="{{ url('/eksekusi/tasks/'.$alert['task']->id) }}" class="mt-1 inline-block text-xs text-accent hover:underline">Lihat task</a>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-muted">Tidak ada peringatan saat ini.</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <h3 class="font-display text-base font-bold text-ink">Feed Progress Update Terbaru</h3>
                    <div class="mt-3 space-y-2">
                        @forelse ($feed as $update)
                            <a href="{{ url('/eksekusi/tasks/'.$update->task_id) }}" class="block rounded-control border border-muted/20 p-3 text-sm hover:bg-accent-soft/20">
                                <p class="font-medium text-ink">{{ $update->user->name }} &middot; {{ $update->task->title }}</p>
                                <p class="mt-1 text-muted">{{ \Illuminate\Support\Str::limit($update->content, 100) }}</p>
                                <p class="mt-1 text-xs text-muted font-mono">{{ $update->created_at->diffForHumans() }}</p>
                            </a>
                        @empty
                            <p class="text-sm text-muted">Belum ada progress update.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Task 8 (Tahap B, Isi Proyek revisi): widget kalender
                     terpadu, Kegiatan+Acara SEMUA proyek digabung (bukan
                     satu proyek seperti tab Kalender per-proyek). --}}
                <div>
                    <h3 class="font-display text-base font-bold text-ink">Ringkasan Kalender</h3>
                    <div class="mt-3 space-y-2">
                        @forelse ($upcomingCalendarItems as $item)
                            <div class="flex items-center gap-2 rounded-control border border-muted/20 p-2 text-sm">
                                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $item['category'] === 'kegiatan' ? 'bg-ink' : 'bg-blue-500' }}"></span>
                                <span class="font-mono text-xs text-muted">{{ $item['date']->locale('id')->translatedFormat('d M') }}</span>
                                <span class="truncate text-ink">{{ $item['title'] }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-muted">Tidak ada kegiatan/acara mendatang.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="mt-8 border-t border-muted/15 pt-6">
                <h3 class="font-display text-base font-bold text-ink">Ringkasan Aktivitas Anggota</h3>
                <div class="mt-3 overflow-x-auto rounded-control border border-muted/20">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-muted/20 bg-accent-soft/20 text-muted">
                            <tr>
                                <th class="p-3 font-medium">Anggota</th>
                                <th class="p-3 font-medium">Todo</th>
                                <th class="p-3 font-medium">In Progress</th>
                                <th class="p-3 font-medium">In Review</th>
                                <th class="p-3 font-medium">Done</th>
                                <th class="p-3 font-medium">Update Terakhir</th>
                                <th class="p-3 font-medium">Task Overdue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($memberSummaries as $summary)
                                <tr class="border-b border-muted/15 last:border-0">
                                    <td class="p-3 text-ink">{{ $summary['member']->name }}</td>
                                    <td class="p-3 font-mono text-ink">{{ $summary['todo'] }}</td>
                                    <td class="p-3 font-mono text-ink">{{ $summary['in_progress'] }}</td>
                                    <td class="p-3 font-mono text-ink">{{ $summary['in_review'] }}</td>
                                    <td class="p-3 font-mono text-ink">{{ $summary['done'] }}</td>
                                    <td class="p-3 font-mono text-xs text-muted">{{ $summary['last_update'] ? \Illuminate\Support\Carbon::parse($summary['last_update'])->diffForHumans() : '-' }}</td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 font-mono text-xs {{ $summary['overdue_count'] > 0 ? 'bg-danger-soft text-danger' : 'bg-muted/15 text-muted' }}">
                                            {{ $summary['overdue_count'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

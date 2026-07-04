<div>
    <h1 class="font-display text-2xl font-bold text-ink">Dashboard Admin</h1>
    <p class="mt-1 text-sm text-muted">Satu panel terpadu untuk Eksplorasi, Eksekusi, dan WEBI &mdash; tidak perlu berpindah halaman untuk gambaran keseluruhan.</p>

    <section class="mt-8">
        <h2 class="font-display text-xl font-bold text-ink">Eksplorasi</h2>

        <div class="mt-4 grid grid-cols-1 gap-8 lg:grid-cols-5">
            <div class="lg:col-span-3">
                <h3 class="font-display text-lg font-bold text-ink">Progres Anggota</h3>
                <div class="mt-3 overflow-x-auto rounded-xl border border-muted/25">
                    <table class="w-full text-sm">
                        <thead class="border-b border-muted/25 bg-muted/5 text-left text-xs uppercase text-muted">
                            <tr>
                                <th class="px-4 py-2">Anggota</th>
                                <th class="px-4 py-2">Sedang Dikerjakan</th>
                                <th class="px-4 py-2">Level</th>
                                <th class="px-4 py-2">Progres</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($explorationLeaderboard as $row)
                                <tr class="border-b border-muted/10 last:border-0">
                                    <td class="px-4 py-2 text-ink">{{ $row['member']->name }}</td>
                                    <td class="px-4 py-2 text-muted">{{ $row['current_unit']?->title ?? 'Belum mulai' }}</td>
                                    <td class="px-4 py-2 font-mono text-xs">{{ $row['progress']->level_name }}</td>
                                    <td class="px-4 py-2">
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

            <div class="lg:col-span-2">
                <h3 class="font-display text-lg font-bold text-ink">Leaderboard</h3>
                <p class="mt-1 text-xs text-muted">Khusus admin &mdash; tidak pernah ditampilkan ke anggota eksplorasi (PRD 3.1.4).</p>
                <div class="mt-3 space-y-1.5">
                    @foreach ($explorationLeaderboard as $index => $row)
                        <div class="flex items-center justify-between rounded-lg border border-muted/25 px-3 py-2 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs text-muted">#{{ $index + 1 }}</span>
                                <span class="text-ink">{{ $row['member']->name }}</span>
                            </div>
                            <span class="font-mono text-xs text-ink">{{ $row['progress']->total_points }} poin</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-between rounded-xl border border-muted/25 p-4">
            <div>
                <h3 class="font-display text-base font-bold text-ink">Log Percakapan WEBI</h3>
                <p class="mt-1 text-xs text-muted font-mono">
                    {{ $webiSummary['total_messages'] }} pesan &middot; {{ $webiSummary['member_count'] }} anggota
                    &middot; <span class="{{ $webiSummary['total_flags'] > 0 ? 'text-red-600' : '' }}">{{ $webiSummary['total_flags'] }} guardrail flag</span>
                    @if ($webiSummary['last_message_at'])
                        &middot; terakhir {{ $webiSummary['last_message_at']->diffForHumans() }}
                    @endif
                </p>
            </div>
            <a href="{{ url('/admin/webi') }}" class="rounded-lg border border-muted/40 px-3 py-1.5 text-sm text-ink hover:border-accent hover:text-accent">Lihat semua log &rarr;</a>
        </div>
    </section>

    <hr class="mt-10 border-muted/25">

    <div class="mt-8">
        <h2 class="font-display text-xl font-bold text-ink">Eksekusi</h2>
    </div>

    <div class="mt-4">
        <h2 class="font-display text-lg font-bold text-ink">Ringkasan Proyek Aktif</h2>
        <div class="mt-3 space-y-3">
            @forelse ($projects as $summary)
                <a href="{{ url('/eksekusi/projects/'.$summary['project']->id) }}" class="block rounded-xl border border-muted/25 p-4 hover:bg-accent-soft/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-ink">{{ $summary['project']->title }}</p>
                            <p class="mt-1 text-xs text-muted">{{ ucfirst($summary['project']->project_type) }} &middot; {{ $summary['active_members'] }} anggota aktif &middot; {{ $summary['days_left'] }} hari lagi</p>
                        </div>
                        <span class="rounded-lg border border-muted/40 px-2 py-1 text-xs font-mono uppercase text-ink">{{ $summary['project']->status }}</span>
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

    <div class="mt-8 grid grid-cols-1 gap-8 md:grid-cols-2">
        <div>
            <h2 class="font-display text-lg font-bold text-ink">Alert Panel</h2>
            <div class="mt-3 space-y-2">
                @forelse ($alerts as $alert)
                    <div class="rounded-lg border p-3 text-sm {{ $alert['severity'] === 3 ? 'border-red-300 bg-red-50' : ($alert['severity'] === 2 ? 'border-orange-300 bg-orange-50' : 'border-amber-200 bg-amber-50') }}">
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
            <h2 class="font-display text-lg font-bold text-ink">Feed Progress Update Terbaru</h2>
            <div class="mt-3 space-y-2">
                @forelse ($feed as $update)
                    <a href="{{ url('/eksekusi/tasks/'.$update->task_id) }}" class="block rounded-lg border border-muted/25 p-3 text-sm hover:bg-accent-soft/20">
                        <p class="font-medium text-ink">{{ $update->user->name }} &middot; {{ $update->task->title }}</p>
                        <p class="mt-1 text-muted">{{ \Illuminate\Support\Str::limit($update->content, 100) }}</p>
                        <p class="mt-1 text-xs text-muted font-mono">{{ $update->created_at->diffForHumans() }}</p>
                    </a>
                @empty
                    <p class="text-sm text-muted">Belum ada progress update.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="font-display text-lg font-bold text-ink">Ringkasan Aktivitas Anggota</h2>
        <div class="mt-3 overflow-x-auto rounded-xl border border-muted/25">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-muted/25 text-left text-xs font-mono uppercase text-muted">
                        <th class="p-3">Anggota</th>
                        <th class="p-3">Todo</th>
                        <th class="p-3">In Progress</th>
                        <th class="p-3">In Review</th>
                        <th class="p-3">Done</th>
                        <th class="p-3">Update Terakhir</th>
                        <th class="p-3">Task Overdue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($memberSummaries as $summary)
                        <tr class="border-b border-muted/10 last:border-0">
                            <td class="p-3 text-ink">{{ $summary['member']->name }}</td>
                            <td class="p-3 font-mono">{{ $summary['todo'] }}</td>
                            <td class="p-3 font-mono">{{ $summary['in_progress'] }}</td>
                            <td class="p-3 font-mono">{{ $summary['in_review'] }}</td>
                            <td class="p-3 font-mono">{{ $summary['done'] }}</td>
                            <td class="p-3 font-mono text-xs">{{ $summary['last_update'] ? \Illuminate\Support\Carbon::parse($summary['last_update'])->diffForHumans() : '-' }}</td>
                            <td class="p-3 font-mono {{ $summary['overdue_count'] > 0 ? 'text-red-600' : '' }}">{{ $summary['overdue_count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

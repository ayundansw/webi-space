<div x-data="{ openTaskId: null }">
    <livewire:eksekusi.projects.project-header :project="$project" />

    <x-eksekusi.project-tabs :project="$project" active="gantt" />

    @php
        $statusLabels = ['todo' => 'Todo', 'in_progress' => 'In Progress', 'in_review' => 'In Review', 'done' => 'Done'];
    @endphp

    <div class="mt-6 rounded-card border border-muted/20 bg-white p-4 shadow-warm-xs">
        @if (! $hasData)
            <p class="text-sm text-muted">Belum ada task besar atau milestone di proyek ini.</p>
        @else
            <div class="flex items-center justify-between">
                <h2 class="font-display text-sm font-semibold text-ink">Gantt Chart</h2>
                <p class="text-xs text-muted">{{ $rangeStart->locale('id')->translatedFormat('d M Y') }} &ndash; {{ $rangeEnd->locale('id')->translatedFormat('d M Y') }}</p>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-muted">
                <span class="flex items-center gap-1"><span class="h-2 w-3 rounded-sm bg-accent/80"></span> Task (klik untuk detail)</span>
                @if ($milestoneRowOffset > 0)
                    <span class="flex items-center gap-1"><span class="h-2 w-2 shrink-0 rotate-45 bg-success"></span> Milestone</span>
                @endif
                <span class="flex items-center gap-1"><span class="h-0.5 w-4 bg-ink"></span> Dependency ("bergantung pada")</span>
            </div>

            {{--
                Overhaul (Kalender+Gantt revisi), tetap Opsi A (CSS/SVG murni,
                nol dependency baru): dulu bar mengambang tanpa rujukan
                tanggal di kanvas kosong -- sekarang ada ruler tanggal
                (garis vertikal + label, interval adaptif dari Gantt.php)
                dan Milestone ikut diplot sebagai marker wajik di baris
                paling atas, satu linimasa dengan Task. Garis dependensi
                sekarang elbow (H-V-H) lewat <path>, bukan <line> diagonal
                yang dulu bisa memotong bar lain di antaranya.

                Struktur tinggi (semua dalam px, viewBox SVG pakai satuan
                yang SAMA supaya bar/marker/garis tidak pernah "meleset"):
                ruler ({{ $rulerHeight }}px) -> [baris Milestone, kalau ada,
                {{ $rowHeight }}px] -> baris Task ({{ $rowHeight }}px per
                task). $row['rowIndex'] dan posisi marker Milestone sudah
                dihitung server-side (Gantt.php) memperhitungkan offset ini.
            --}}
            <div class="mt-4 flex overflow-x-auto">
                <div class="w-36 shrink-0 border-r border-muted/20 pr-2">
                    <div style="height: {{ $rulerHeight }}px;"></div>

                    @if ($milestoneRowOffset > 0)
                        <div class="flex items-center gap-1.5 text-xs font-medium text-ink" style="height: {{ $rowHeight }}px;">
                            <span class="h-2 w-2 shrink-0 rotate-45 bg-success"></span> Milestone
                        </div>
                    @endif

                    @foreach ($rows as $row)
                        <button
                            type="button"
                            @click="openTaskId = openTaskId === '{{ $row['task']->id }}' ? null : '{{ $row['task']->id }}'"
                            class="flex w-full items-center truncate text-left text-xs font-medium text-ink hover:text-accent"
                            style="height: {{ $rowHeight }}px;"
                            title="{{ $row['task']->title }}"
                        >
                            {{ $row['task']->title }}
                        </button>
                    @endforeach
                </div>

                <div class="relative flex-1" style="min-width: 640px; height: {{ $totalHeight }}px;">
                    {{-- Ruler: label tanggal, posisi absolut % SAMA basisnya
                         dengan bar/marker (leftPct dari Gantt.php). --}}
                    <div class="absolute inset-x-0 top-0 border-b border-muted/15" style="height: {{ $rulerHeight }}px;">
                        @foreach ($gridLines as $line)
                            <span class="absolute top-1.5 -translate-x-1/2 whitespace-nowrap font-mono text-[9px] text-muted" style="left: {{ $line['leftPct'] }}%;">{{ $line['label'] }}</span>
                        @endforeach
                    </div>

                    <svg class="pointer-events-none absolute inset-0 h-full w-full text-ink" viewBox="0 0 1000 {{ $totalHeight }}" preserveAspectRatio="none">
                        <defs>
                            <marker id="gantt-dependency-arrow" markerWidth="8" markerHeight="8" refX="6" refY="3" orient="auto">
                                <path d="M0,0 L6,3 L0,6 Z" fill="currentColor" />
                            </marker>
                        </defs>

                        {{-- Grid garis waktu vertikal, tipis, di belakang bar/marker. --}}
                        @foreach ($gridLines as $line)
                            <line x1="{{ $line['leftPct'] * 10 }}" y1="0" x2="{{ $line['leftPct'] * 10 }}" y2="{{ $totalHeight }}" stroke="currentColor" stroke-width="1" stroke-opacity="0.08" vector-effect="non-scaling-stroke" />
                        @endforeach

                        {{-- Elbow connector: keluar horizontal dari ujung bar sumber,
                             naik/turun vertikal ke baris tujuan, masuk horizontal ke
                             sisi kiri bar tujuan -- bukan garis diagonal lurus. --}}
                        @foreach ($edges as $edge)
                            <path
                                d="M{{ $edge['fromX'] }},{{ $edge['fromY'] }} H{{ $edge['midX'] }} V{{ $edge['toY'] }} H{{ $edge['toX'] }}"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                vector-effect="non-scaling-stroke"
                                marker-end="url(#gantt-dependency-arrow)"
                            />
                        @endforeach
                    </svg>

                    @if ($milestoneRowOffset > 0)
                        <div class="absolute inset-x-0" style="top: {{ $rulerHeight }}px; height: {{ $rowHeight }}px;">
                            @foreach ($milestoneMarkers as $marker)
                                <div class="absolute top-1/2 -translate-x-1/2 -translate-y-1/2" style="left: {{ $marker['leftPct'] }}%;">
                                    <div class="h-3.5 w-3.5 rotate-45 rounded-xs bg-success shadow-warm-xs" title="{{ $marker['title'] }} ({{ $marker['dateLabel'] }})"></div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @foreach ($rows as $row)
                        <div class="absolute inset-x-0" style="top: {{ $rulerHeight + $row['rowIndex'] * $rowHeight }}px; height: {{ $rowHeight }}px;">
                            <button
                                type="button"
                                @click="openTaskId = openTaskId === '{{ $row['task']->id }}' ? null : '{{ $row['task']->id }}'"
                                class="absolute top-1/2 h-7 -translate-y-1/2 truncate rounded-control bg-accent px-2.5 text-[11px] font-semibold leading-7 text-ink shadow-warm-xs hover:brightness-95"
                                style="left: {{ $row['leftPct'] }}%; width: {{ $row['widthPct'] }}%;"
                                title="{{ $row['task']->title }} ({{ $row['startLabel'] }} &ndash; {{ $row['endLabel'] }})"
                            >
                                {{ $row['startLabel'] }} &ndash; {{ $row['endLabel'] }}
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>

            @foreach ($rows as $row)
                @php $task = $row['task']; @endphp
                <div
                    x-show="openTaskId === '{{ $task->id }}'"
                    x-cloak
                    x-transition:enter="transition-opacity duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition-opacity duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="openTaskId = null"
                    @keydown.escape.window="openTaskId = null"
                    class="fixed inset-0 z-40 flex items-center justify-center bg-ink/40 p-4"
                >
                    <div
                        @click.stop
                        x-show="openTaskId === '{{ $task->id }}'"
                        x-transition:enter="transition duration-200 ease-out"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition duration-150 ease-in"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="w-full max-w-md rounded-modal border border-muted/20 bg-white p-6 shadow-warm-lg"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <h3 class="font-display text-lg font-bold text-ink">{{ $task->title }}</h3>
                            <button type="button" @click="openTaskId = null" class="text-muted hover:text-ink" aria-label="Tutup">&times;</button>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-xs font-medium uppercase text-muted">Status</p>
                                <p class="mt-0.5 text-ink">{{ $statusLabels[$task->status] ?? $task->status }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase text-muted">Prioritas</p>
                                <p class="mt-0.5 text-ink">{{ ucfirst($task->priority) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase text-muted">Deadline</p>
                                <p class="mt-0.5 text-ink">{{ $task->deadline->locale('id')->translatedFormat('d M Y') }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase text-muted">Assignee</p>
                                <p class="mt-0.5 text-ink">
                                    @forelse ($task->assignments as $assignment)
                                        {{ $assignment->user->name }}@if (! $loop->last), @endif
                                    @empty
                                        <span class="text-muted">Belum ada</span>
                                    @endforelse
                                </p>
                            </div>
                        </div>

                        <div class="mt-3">
                            <p class="text-xs font-medium uppercase text-muted">Progres</p>
                            @if ($task->subtasks->isNotEmpty())
                                @php
                                    $doneSubtasks = $task->subtasks->where('status', 'done')->count();
                                    $totalSubtasks = $task->subtasks->count();
                                @endphp
                                <p class="mt-0.5 text-sm text-ink">{{ $doneSubtasks }}/{{ $totalSubtasks }} subtask selesai</p>
                                <div class="mt-1.5 h-1.5 rounded-full bg-muted/20">
                                    <div class="h-1.5 rounded-full bg-accent" style="width: {{ $totalSubtasks > 0 ? round($doneSubtasks / $totalSubtasks * 100) : 0 }}%"></div>
                                </div>
                            @else
                                <p class="mt-0.5 text-sm text-ink">{{ $statusLabels[$task->status] ?? $task->status }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>

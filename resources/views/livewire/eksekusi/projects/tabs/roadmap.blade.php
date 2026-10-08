<div x-data="{ openId: null }">
    <livewire:eksekusi.projects.project-header :project="$project" />

    <x-eksekusi.project-tabs :project="$project" active="roadmap" />

    {{--
        Fase 7 Batch 3b: node+garis penghubung, bahasa visual sama dengan
        Peta Kurikulum Eksplorasi (resources/views/livewire/eksplorasi/peta-kurikulum.blade.php)
        untuk konsistensi lintas-portal -- TAPI horizontal, bukan vertikal.
        Dipilih horizontal karena: (1) jumlah Milestone per proyek biasanya
        sedikit (beda dari jumlah Modul Eksplorasi yang bisa puluhan, jadi
        tidak butuh scroll panjang ala Peta Kurikulum), (2) Roadmap secara
        konsep adalah garis WAKTU proyek -- horizontal kiri-ke-kanan lebih
        cocok dengan cara orang membaca timeline dibanding vertikal
        top-to-bottom yang lebih cocok untuk "urutan langkah belajar" ala
        kurikulum. `overflow-x-auto` sebagai fallback kalau Milestone-nya
        banyak di layar sempit (pola sama dengan tabel lebar lain di app
        ini). Klik node membuka detail task di bawah baris node lewat
        Alpine murni (`openId`, satu state dibagi semua node) -- TIDAK ada
        Livewire round-trip, semua data task sudah dikirim di render awal.
    --}}
    <div class="mt-6 rounded-card border border-muted/20 bg-white p-4 shadow-warm-xs">
        @if ($entries->isEmpty())
            <p class="text-sm text-muted">Belum ada milestone di proyek ini.</p>
        @else
            <div class="flex items-start overflow-x-auto pb-2">
                @foreach ($entries as $index => $entry)
                    @php
                        $milestone = $entry['milestone'];
                        $status = $entry['status'];
                        $isLast = $index === count($entries) - 1;
                    @endphp

                    {{--
                        Task 5 (Tahap B, Isi Proyek revisi) bug fix: flex
                        items default to `min-width: auto`, which means their
                        computed min-width is their CONTENT's intrinsic width,
                        not 0 — so a long milestone title could force this
                        column wider than its intended 9rem no matter what
                        `width`/`shrink-0` say, and `truncate` on the <p>
                        below never actually got a bounded box to clip
                        against. `min-w-0` on both this column AND the title
                        button (plus `w-full` on the button so it actually
                        fills the 9rem instead of shrink-to-fit) is the fix —
                        `overflow-x-auto` on the parent row remains the
                        fallback for genuinely narrow viewports with many
                        milestones, unrelated to this bug.
                    --}}
                    <div class="flex min-w-0 shrink-0 flex-col items-center" style="width: 9rem;">
                        <div class="flex w-full items-center">
                            <button
                                type="button"
                                @click="openId = openId === '{{ $milestone->id }}' ? null : '{{ $milestone->id }}'"
                                @class([
                                    'mx-auto flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-sm font-display font-bold transition',
                                    'bg-ink text-accent' => $status === 'completed',
                                    'bg-accent text-ink ring-4 ring-accent-soft' => $status === 'active',
                                    'border-2 border-muted bg-white text-muted' => $status === 'not_started',
                                ])
                            >
                                @if ($status === 'completed')
                                    &#10003;
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </button>

                            @unless ($isLast)
                                <div @class([
                                    'h-0.5 flex-1',
                                    'bg-accent' => in_array($status, ['completed', 'active']),
                                    'bg-muted/40' => $status === 'not_started',
                                ])></div>
                            @endunless
                        </div>

                        <button type="button" @click="openId = openId === '{{ $milestone->id }}' ? null : '{{ $milestone->id }}'" class="mt-2 w-full min-w-0 text-center">
                            <p class="truncate text-xs font-semibold text-ink" title="{{ $milestone->title }}">{{ $milestone->title }}</p>
                            <p class="font-mono text-[10px] text-muted">{{ $milestone->target_date->format('d M Y') }}</p>
                        </button>

                        <div class="mt-1 h-1.5 w-20 overflow-hidden rounded-full bg-muted/15">
                            <div class="h-full bg-accent" style="width: {{ $entry['percentage'] }}%"></div>
                        </div>
                        <p class="font-mono text-[10px] text-muted">{{ $entry['percentage'] }}%</p>
                    </div>
                @endforeach
            </div>

            @foreach ($entries as $entry)
                @php $milestone = $entry['milestone']; @endphp
                <div x-show="openId === '{{ $milestone->id }}'" x-cloak x-transition class="mt-4 rounded-lg border border-muted/20 bg-surface/40 p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-display text-sm font-semibold text-ink">{{ $milestone->title }}</h3>
                        <span class="rounded-full px-2 py-0.5 font-mono text-[10px] uppercase {{ match ($entry['status']) { 'completed' => 'bg-success-soft text-success', 'active' => 'bg-accent-soft text-accent', default => 'bg-muted/15 text-muted' } }}">
                            {{ match ($entry['status']) { 'completed' => 'Selesai', 'active' => 'Sedang Berjalan', default => 'Belum Mulai' } }}
                        </span>
                    </div>
                    @if ($milestone->description)
                        <p class="mt-1 text-xs text-muted">{{ $milestone->description }}</p>
                    @endif

                    <div class="mt-3 space-y-2">
                        @forelse ($entry['tasks'] as $task)
                            <div class="flex items-center justify-between gap-2 rounded-lg border border-muted/20 bg-white p-2 text-xs">
                                <span class="text-ink">{{ $task->title }}</span>
                                <span class="font-mono uppercase text-muted">{{ $task->status }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-muted">Belum ada task di bawah milestone ini.</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>

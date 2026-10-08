<div>
    @if ($isReadOnlyExploration)
        <x-eksplorasi.read-only-banner />
    @endif

    <div class="mb-8">
        <h1 class="font-display text-2xl font-bold text-ink">Peta Kurikulum</h1>
        <p class="mt-1 text-sm text-muted">Jalan belajarmu, satu langkah pada satu waktu. Tidak perlu buru-buru.</p>
    </div>

    {{--
        Bagian A2: ringkasan Level+Poin, styling diambil dari card "Progres
        Keseluruhan" Dashboard Eksplorasi (border tegas, shadow-warm-xs,
        angka accent, progress bar) -- data lewat ProgressService yang
        SAMA dipakai dashboard (ensureProgress + overallProgressPercentage),
        tidak ada query baru. Struktur jalur vertikal di bawah TIDAK disentuh.
    --}}
    <div class="relative mx-auto mb-8 max-w-xl overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs">
        <div class="card-pixel-accent-top" aria-hidden="true"></div>
        <div class="card-pixel-accent-bottom" aria-hidden="true"></div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-muted">Level {{ $userProgress->current_level }} :</p>
                <p class="font-display text-xl font-bold text-heading">{{ $userProgress->level_name }}</p>
            </div>
            <div class="sm:text-right">
                <p class="text-sm font-medium text-muted">Total Poin :</p>
                <p class="font-pixel text-3xl text-accent">{{ $userProgress->total_points }}</p>
            </div>
        </div>

        <div class="mt-4">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-muted">Progres Keseluruhan Kurikulum</p>
                <p class="font-display text-sm font-bold text-accent">{{ $overallPercentage }}%</p>
            </div>
            <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-muted/15">
                <div class="h-full rounded-full bg-accent transition-all duration-300" style="width: {{ $overallPercentage }}%"></div>
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-xl">
        @foreach ($modules as $index => $entry)
            @php
                $module = $entry['module'];
                $status = $entry['status'];
                $isLast = $index === count($modules) - 1;
            @endphp

            <div x-data="{ open: {{ $status === 'active' ? 'true' : 'false' }} }">
                <div class="flex items-start gap-4">
                    <div class="flex flex-col items-center">
                        {{-- Node --}}
                        <button
                            type="button"
                            @click="open = !open"
                            @class([
                                'flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-sm font-display font-bold transition',
                                'bg-ink text-accent' => $status === 'completed',
                                'bg-accent text-ink ring-4 ring-accent-soft' => $status === 'active',
                                'bg-white text-muted border-2 border-muted' => $status === 'locked',
                            ])
                        >
                            @if ($status === 'completed')
                                &#10003;
                            @elseif ($status === 'locked')
                                &#128274;
                            @else
                                {{ $module->order_number }}
                            @endif
                        </button>

                        {{-- Connecting line --}}
                        @unless ($isLast)
                            <div @class([
                                'w-0.5 flex-1 min-h-10',
                                'bg-accent' => in_array($status, ['completed', 'active']),
                                'bg-muted/40' => $status === 'locked',
                            ])></div>
                        @endunless
                    </div>

                    <div class="flex-1 pb-10">
                        <button type="button" @click="open = !open" class="text-left">
                            <h2 class="font-display text-base font-semibold text-ink">{{ $module->title }}</h2>
                        </button>
                        <p class="mt-1 text-sm text-muted">{{ $module->description }}</p>

                        @if ($status !== 'locked')
                            <div class="mt-2 h-1.5 w-full max-w-xs overflow-hidden rounded-full bg-muted/15">
                                <div class="h-full bg-accent" style="width: {{ $entry['percentage'] }}%"></div>
                            </div>
                        @endif

                        <div x-show="open" x-transition class="mt-4">
                            @if ($status === 'locked')
                                <p class="text-sm text-muted">Selesaikan dulu modul sebelumnya untuk membuka modul ini ya.</p>
                            @else
                                {{-- Unit path (2.2.3 redesign): unit-as-node, mirroring the
                                     module-level circuit path above it. Every locked/completed/
                                     in_progress flag here is read straight off the array
                                     PetaKurikulum::render() already built — nothing recomputed. --}}
                                @foreach ($entry['units'] as $unitIndex => $unitEntry)
                                    @php
                                        $unit = $unitEntry['unit'];
                                        $isLastUnit = $unitIndex === count($entry['units']) - 1;
                                        $unitReached = $unitEntry['completed'] || $unitEntry['in_progress'];
                                    @endphp

                                    <div class="flex items-start gap-3">
                                        <div class="flex flex-col items-center">
                                            <div
                                                @class([
                                                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-full font-mono text-xs font-bold',
                                                    'bg-ink text-accent' => $unitEntry['completed'],
                                                    'bg-accent text-ink ring-4 ring-accent-soft' => $unitEntry['in_progress'],
                                                    'bg-white text-ink border-2 border-ink/30' => ! $unitEntry['completed'] && ! $unitEntry['in_progress'] && ! $unitEntry['locked'],
                                                    'bg-white text-muted border-2 border-muted' => $unitEntry['locked'],
                                                ])
                                            >
                                                @if ($unitEntry['completed'])
                                                    &#10003;
                                                @elseif ($unitEntry['locked'])
                                                    &#128274;
                                                @else
                                                    {{ $unit->order_number }}
                                                @endif
                                            </div>

                                            @unless ($isLastUnit)
                                                <div @class([
                                                    'min-h-8 w-0.5 flex-1',
                                                    'bg-accent' => $unitReached,
                                                    'bg-muted/40' => ! $unitReached,
                                                ])></div>
                                            @endunless
                                        </div>

                                        <div class="flex-1 pb-4">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-sm font-medium text-ink">{{ $unit->title }}</p>
                                                @if ($unitEntry['in_progress'])
                                                    <span class="rounded-full bg-accent-soft px-2 py-0.5 font-mono text-[10px] font-medium uppercase text-ink">Kamu di sini</span>
                                                @endif
                                            </div>
                                            <p class="font-mono text-xs text-muted">{{ $unit->estimated_minutes }} menit &middot; {{ $unit->point_value }} poin</p>

                                            <div class="mt-1.5">
                                                @if ($unitEntry['locked'])
                                                    <span class="text-xs text-muted">&#128274; Terkunci</span>
                                                @elseif ($unitEntry['completed'])
                                                    <a href="{{ url('/eksplorasi/unit/'.$unit->id) }}" wire:navigate class="text-xs text-ink underline hover:text-accent">Selesai &middot; Lihat lagi</a>
                                                @elseif ($unitEntry['in_progress'])
                                                    <a href="{{ url('/eksplorasi/unit/'.$unit->id) }}" wire:navigate class="rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90">Lanjutkan</a>
                                                @else
                                                    <a href="{{ url('/eksplorasi/unit/'.$unit->id) }}" wire:navigate class="rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90">Buka</a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @if ($module->checkpoint)
                                    <div class="mt-2 flex items-center justify-between rounded-lg border border-accent/40 bg-accent-soft/30 px-4 py-3">
                                        <p class="text-sm font-medium text-ink">Checkpoint Modul {{ $module->order_number }}</p>
                                        <a href="{{ url('/eksplorasi/checkpoint/'.$module->checkpoint->id) }}" wire:navigate class="text-xs text-ink underline hover:text-accent">
                                            {{ $status === 'completed' ? 'Lihat lagi' : 'Buka' }}
                                        </a>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

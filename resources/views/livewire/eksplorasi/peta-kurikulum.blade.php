<div>
    <div class="mb-8">
        <h1 class="font-display text-2xl font-bold text-ink">Peta Kurikulum</h1>
        <p class="mt-1 text-sm text-muted">Jalan belajarmu, satu langkah pada satu waktu. Tidak perlu buru-buru.</p>
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

                        <div x-show="open" x-transition class="mt-4 space-y-2">
                            @if ($status === 'locked')
                                <p class="text-sm text-muted">Selesaikan dulu modul sebelumnya untuk membuka modul ini ya.</p>
                            @else
                                @foreach ($entry['units'] as $unitEntry)
                                    @php $unit = $unitEntry['unit']; @endphp
                                    <div class="flex items-center justify-between rounded-lg border border-muted/25 px-4 py-3">
                                        <div>
                                            <p class="text-sm font-medium text-ink">{{ $unit->order_number }}. {{ $unit->title }}</p>
                                            <p class="font-mono text-xs text-muted">{{ $unit->estimated_minutes }} menit &middot; {{ $unit->point_value }} poin</p>
                                        </div>

                                        @if ($unitEntry['locked'])
                                            <span class="text-xs text-muted">&#128274; Terkunci</span>
                                        @elseif ($unitEntry['completed'])
                                            <a href="{{ url('/eksplorasi/unit/'.$unit->id) }}" class="text-xs text-ink underline hover:text-accent">Selesai &middot; Lihat lagi</a>
                                        @else
                                            <a href="{{ url('/eksplorasi/unit/'.$unit->id) }}" class="rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90">Buka</a>
                                        @endif
                                    </div>
                                @endforeach

                                @if ($module->checkpoint)
                                    <div class="flex items-center justify-between rounded-lg border border-accent/40 bg-accent-soft/30 px-4 py-3">
                                        <p class="text-sm font-medium text-ink">Checkpoint Modul {{ $module->order_number }}</p>
                                        <a href="{{ url('/eksplorasi/checkpoint/'.$module->checkpoint->id) }}" class="text-xs text-ink underline hover:text-accent">
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

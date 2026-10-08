<div>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Praktik</h1>
        <p class="mt-1 text-sm text-muted">Challenge project berjenjang, bebas dipilih kapan saja, tidak terikat urutan Materi.</p>
    </div>

    <div class="mb-6 flex flex-wrap items-center gap-2">
        <button
            type="button"
            wire:click="$set('levelFilter', '')"
            class="rounded-full px-3 py-1.5 text-xs font-medium {{ $levelFilter === '' ? 'bg-ink text-white' : 'border border-muted/40 text-ink hover:border-accent' }}"
        >
            Semua Level
        </button>
        @foreach (['low' => 'Low', 'mid' => 'Mid', 'high' => 'High'] as $value => $label)
            <button
                type="button"
                wire:click="$set('levelFilter', '{{ $value }}')"
                class="rounded-full px-3 py-1.5 text-xs font-medium {{ $levelFilter === $value ? 'bg-ink text-white' : 'border border-muted/40 text-ink hover:border-accent' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($challenges as $challenge)
            @php
                $levelBadge = match ($challenge->level) {
                    'low' => 'bg-green-100 text-green-700',
                    'mid' => 'bg-amber-100 text-amber-700',
                    'high' => 'bg-red-100 text-red-700',
                };
                $submission = $latestSubmissionByChallenge->get($challenge->id);
                $statusBadge = match ($submission?->status) {
                    'disetujui' => ['label' => 'Disetujui', 'class' => 'bg-green-100 text-green-700'],
                    'perlu_revisi' => ['label' => 'Perlu Revisi', 'class' => 'bg-red-100 text-red-700'],
                    'pending' => ['label' => 'Menunggu Review', 'class' => 'bg-amber-100 text-amber-700'],
                    default => ['label' => 'Belum Dicoba', 'class' => 'bg-muted/15 text-muted'],
                };
            @endphp

            <a
                href="{{ url('/eksplorasi/praktik/'.$challenge->id) }}"
                wire:navigate
                class="block rounded-lg border border-muted/20 bg-white p-4 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="rounded-full px-2 py-0.5 font-mono text-[10px] font-medium uppercase tracking-wide {{ $levelBadge }}">
                        {{ strtoupper($challenge->level) }}
                    </span>
                    <span class="font-mono text-xs text-caption">{{ $challenge->points_reward }} poin</span>
                </div>

                <p class="font-display mt-2 text-base font-semibold text-ink">{{ $challenge->title }}</p>
                <p class="mt-1 text-xs text-caption">{{ \Illuminate\Support\Str::limit($challenge->description, 100) }}</p>

                <div class="mt-3">
                    <span class="rounded-full px-2 py-0.5 font-mono text-[10px] font-medium uppercase tracking-wide {{ $statusBadge['class'] }}">
                        {{ $statusBadge['label'] }}
                    </span>
                </div>
            </a>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-muted/40 p-6 text-center text-sm text-muted">
                Belum ada challenge {{ $levelFilter !== '' ? 'di level ini' : 'yang tersedia' }} saat ini.
            </div>
        @endforelse
    </div>
</div>

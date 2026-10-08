<div class="max-w-xl">
    <a href="{{ url('/eksplorasi/forum') }}" class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
            <path d="m12 19-7-7 7-7" /><path d="M19 12H5" />
        </svg>
        Kembali ke Forum
    </a>

    {{--
        Bagian A (perbaikan lanjutan): card thread + reply diperkuat
        (border-muted/20, shadow-warm-xs/md, radius) mengikuti bahasa
        visual Dashboard Eksplorasi -- sebelumnya border-muted/25 polos
        tanpa shadow, terasa flat. Aksen pixel left/right di card utama
        (keluarga "konten", sama seperti Modul/Forum/Log Dashboard).
    --}}
    <div class="relative mt-4 overflow-hidden rounded-2xl border border-muted/20 bg-white p-6 shadow-warm-md">
        <div class="card-pixel-accent-left" aria-hidden="true"></div>
        <div class="card-pixel-accent-right" aria-hidden="true"></div>

        <div class="flex items-start justify-between gap-3">
            <h1 class="font-display text-xl font-bold text-heading">{{ $thread->title }}</h1>
            <span class="shrink-0 rounded-full px-2 py-0.5 text-xs {{ $thread->target === 'pic' ? 'bg-accent-soft/60 text-ink' : 'bg-muted/15 text-muted' }}">
                {{ $thread->target === 'pic' ? 'Ke PIC' : 'Ke Sesama Anggota' }}
            </span>
        </div>
        <p class="mt-1 text-xs text-caption">
            oleh {{ $thread->creator->name }}
            @if ($thread->module) &middot; Modul {{ $thread->module->order_number }}: {{ $thread->module->title }} @endif
            @if ($thread->unit) &middot; Unit: {{ $thread->unit->title }} @endif
            @if (! $thread->module && ! $thread->unit) &middot; General @endif
        </p>
        <p class="mt-3 text-sm text-body">{{ $thread->content }}</p>
    </div>

    <div class="mt-6 space-y-3">
        <h2 class="font-display text-sm font-semibold text-heading">Balasan</h2>

        @forelse ($replies as $reply)
            <div class="rounded-xl border border-muted/20 bg-white px-4 py-3 shadow-warm-xs">
                <p class="text-sm text-ink">{{ $reply->content }}</p>
                <p class="mt-1 text-xs text-caption">{{ $reply->user->name }} &middot; {{ $reply->created_at->diffForHumans() }}</p>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-muted/25 bg-white px-4 py-4">
                <p class="text-sm text-muted">Belum ada balasan. Jadi yang pertama membalas, yuk!</p>
            </div>
        @endforelse
    </div>

    @if ($isReadOnlyExploration)
        {{-- Fase 8 Batch 3: mode baca Eksplorasi -- membalas adalah interaksi sosial, tidak tersedia. --}}
        <div class="mt-4 rounded-lg border border-muted/25 bg-surface/60 p-4 text-sm text-muted">
            Membalas thread tidak tersedia dalam mode baca-saja Eksplorasi.
        </div>
    @else
        <div class="mt-4 rounded-xl border border-muted/20 bg-white p-4 shadow-warm-xs">
            <form wire:submit="reply" class="space-y-2">
                <textarea
                    wire:model="replyContent"
                    rows="3"
                    placeholder="Tulis balasanmu di sini..."
                    class="w-full rounded-control border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                ></textarea>
                @error('replyContent')
                    <p class="text-sm text-danger">{{ $message }}</p>
                @enderror
                <button type="submit" wire:loading.attr="disabled" class="rounded-control bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60">
                    Balas
                </button>
            </form>
        </div>
    @endif
</div>

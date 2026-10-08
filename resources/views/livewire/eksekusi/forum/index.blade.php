@push('breadcrumb-actions')
    <a href="{{ url('/eksekusi/forum/create') }}" wire:navigate class="inline-flex shrink-0 items-center gap-1.5 rounded-control bg-ink px-4 py-2 text-sm font-medium text-white shadow-warm-xs hover:bg-ink/90">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M12 5v14" /><path d="M5 12h14" />
        </svg>
        Buat Thread
    </a>
@endpush

<div>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Forum General Eksekusi</h1>
        <p class="mt-1 text-sm text-muted">Diskusi lintas-proyek, bukan spesifik satu proyek (itu ada di tab Forum tiap proyek).</p>
    </div>

    <div class="space-y-3">
        @forelse ($threads as $thread)
            <a href="{{ url('/eksekusi/forum/'.$thread->id) }}" wire:navigate class="block rounded-xl border border-muted/20 bg-white p-4 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md">
                <p class="text-sm font-medium text-ink">{{ $thread->title }}</p>
                <p class="mt-1 flex flex-wrap items-center gap-1 text-xs text-muted">
                    <span>oleh {{ $thread->creator->name }}</span>
                    <span class="inline-flex items-center gap-1">
                        &middot;
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 shrink-0">
                            <path d="M4 5h13v9H8l-4 4V5Z" />
                        </svg>
                        {{ $thread->replies->count() }} balasan
                    </span>
                </p>
            </a>
        @empty
            <p class="text-sm text-muted">Belum ada thread. Jadi yang pertama membuka diskusi, yuk!</p>
        @endforelse
    </div>
</div>

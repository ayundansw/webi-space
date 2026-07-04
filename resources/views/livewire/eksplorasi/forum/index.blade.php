<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold text-ink">Forum Diskusi Eksplorasi</h1>
            <p class="mt-1 text-sm text-muted">Bertanya ke sesama anggota atau langsung ke PIC, tidak perlu ragu.</p>
        </div>
        <a href="{{ url('/eksplorasi/forum/create') }}" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">
            Buat Thread
        </a>
    </div>

    <div class="space-y-3">
        @forelse ($threads as $thread)
            <a href="{{ url('/eksplorasi/forum/'.$thread->id) }}" class="block rounded-xl border border-muted/25 p-4 hover:border-ink">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-ink">{{ $thread->title }}</p>
                    <span class="rounded-full px-2 py-0.5 text-xs {{ $thread->target === 'pic' ? 'bg-accent-soft/60 text-ink' : 'bg-muted/15 text-muted' }}">
                        {{ $thread->target === 'pic' ? 'Ke PIC' : 'Ke Sesama Anggota' }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-muted">
                    oleh {{ $thread->creator->name }}
                    @if ($thread->module) &middot; Modul {{ $thread->module->order_number }}: {{ $thread->module->title }} @endif
                    @if ($thread->unit) &middot; Unit: {{ $thread->unit->title }} @endif
                    &middot; {{ $thread->replies->count() }} balasan
                </p>
            </a>
        @empty
            <p class="text-sm text-muted">Belum ada thread. Jadi yang pertama bertanya, yuk!</p>
        @endforelse
    </div>
</div>

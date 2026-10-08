<div class="max-w-xl">
    <a href="{{ url('/eksekusi/forum') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-muted hover:text-ink">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
            <path d="m12 19-7-7 7-7" /><path d="M19 12H5" />
        </svg>
        Kembali ke Forum General
    </a>

    <div class="relative mt-4 overflow-hidden rounded-2xl border border-muted/20 bg-white p-6 shadow-warm-md">
        <div class="card-pixel-accent-left" aria-hidden="true"></div>
        <div class="card-pixel-accent-right" aria-hidden="true"></div>

        <h1 class="font-display text-xl font-bold text-heading">{{ $thread->title }}</h1>
        <p class="mt-1 text-xs text-caption">oleh {{ $thread->creator->name }}</p>
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
</div>

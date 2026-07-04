<div class="max-w-xl">
    <a href="{{ url('/eksplorasi/forum') }}" class="text-sm text-muted hover:text-ink">&larr; Kembali ke Forum</a>

    <div class="mt-4 rounded-xl border border-muted/25 p-5">
        <div class="flex items-center justify-between">
            <h1 class="font-display text-xl font-bold text-ink">{{ $thread->title }}</h1>
            <span class="rounded-full px-2 py-0.5 text-xs {{ $thread->target === 'pic' ? 'bg-accent-soft/60 text-ink' : 'bg-muted/15 text-muted' }}">
                {{ $thread->target === 'pic' ? 'Ke PIC' : 'Ke Sesama Anggota' }}
            </span>
        </div>
        <p class="mt-1 text-xs text-muted">
            oleh {{ $thread->creator->name }}
            @if ($thread->module) &middot; Modul {{ $thread->module->order_number }}: {{ $thread->module->title }} @endif
            @if ($thread->unit) &middot; Unit: {{ $thread->unit->title }} @endif
        </p>
        <p class="mt-3 text-sm text-ink">{{ $thread->content }}</p>
    </div>

    <div class="mt-6 space-y-3">
        <h2 class="font-display text-sm font-semibold text-ink">Balasan</h2>

        @forelse ($replies as $reply)
            <div class="rounded-lg border border-muted/25 px-4 py-3">
                <p class="text-sm text-ink">{{ $reply->content }}</p>
                <p class="mt-1 text-xs text-muted">{{ $reply->user->name }} &middot; {{ $reply->created_at->diffForHumans() }}</p>
            </div>
        @empty
            <p class="text-sm text-muted">Belum ada balasan. Jadi yang pertama membalas, yuk!</p>
        @endforelse
    </div>

    <form wire:submit="reply" class="mt-4 space-y-2">
        <textarea
            wire:model="replyContent"
            rows="3"
            placeholder="Tulis balasanmu di sini..."
            class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
        ></textarea>
        @error('replyContent')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
        <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60">
            Balas
        </button>
    </form>
</div>

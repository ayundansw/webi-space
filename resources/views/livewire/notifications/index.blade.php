<div>
    <div class="flex items-center justify-between">
        <h1 class="font-display text-2xl font-bold text-ink">Notifikasi</h1>

        @if ($notifications->contains(fn ($n) => ! $n->is_read))
            <button
                type="button"
                wire:click="markAllAsRead"
                class="rounded-lg border border-muted/40 px-3 py-1.5 text-sm text-ink hover:border-accent hover:text-accent"
            >
                Tandai semua sudah dibaca
            </button>
        @endif
    </div>

    <div class="mt-6 space-y-2">
        @forelse ($notifications as $notification)
            <a
                href="{{ $notification->linkUrl() ?? '#' }}"
                wire:click="markAsRead('{{ $notification->id }}')"
                class="block rounded-xl border border-muted/25 p-4 text-sm hover:bg-accent-soft/20 {{ $notification->is_read ? '' : 'bg-accent-soft/10' }}"
            >
                <div class="flex items-start gap-3">
                    @unless ($notification->is_read)
                        <span class="mt-1.5 h-2 w-2 flex-shrink-0 rounded-full bg-accent"></span>
                    @endunless
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-ink">{{ $notification->title }}</p>
                        <p class="mt-1 text-muted">{{ $notification->message }}</p>
                        <p class="mt-1 font-mono text-xs text-muted">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            </a>
        @empty
            <p class="text-sm text-muted">Belum ada notifikasi.</p>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</div>

<div class="relative" x-data="{ open: false }" @click.outside="open = false" wire:poll.30s="refresh">
    <button
        type="button"
        @click="open = !open"
        class="relative rounded-lg p-1.5 text-ink hover:bg-accent-soft/20"
        aria-label="Notifikasi"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
            <path d="M6 8a6 6 0 1 1 12 0c0 4.5 1.5 6 2 7H4c.5-1 2-2.5 2-7Z" />
            <path d="M10 19a2 2 0 0 0 4 0" />
        </svg>

        @if ($unreadCount > 0)
            <span class="absolute -right-1 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-accent px-1 font-mono text-[10px] font-medium text-ink">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute right-0 z-20 mt-2 w-80 rounded-xl border border-muted/25 bg-white p-2 shadow-lg"
    >
        <p class="px-2 py-1 font-display text-sm font-bold text-ink">Notifikasi</p>

        <div class="mt-1 max-h-96 space-y-1 overflow-y-auto">
            @forelse ($recent as $notification)
                <a
                    href="{{ $notification->linkUrl() ?? '#' }}"
                    wire:click="markAsRead('{{ $notification->id }}')"
                    class="block rounded-lg px-2 py-2 text-sm hover:bg-accent-soft/20 {{ $notification->is_read ? '' : 'bg-accent-soft/10' }}"
                >
                    <div class="flex items-start gap-2">
                        @unless ($notification->is_read)
                            <span class="mt-1.5 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-accent"></span>
                        @endunless
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-ink">{{ $notification->title }}</p>
                            <p class="mt-0.5 truncate text-xs text-muted">{{ $notification->message }}</p>
                            <p class="mt-0.5 font-mono text-[10px] text-muted">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <p class="px-2 py-4 text-center text-sm text-muted">Belum ada notifikasi.</p>
            @endforelse
        </div>

        <a href="{{ url('/notifications') }}" class="mt-1 block rounded-lg px-2 py-2 text-center text-xs text-accent hover:underline">
            Lihat semua notifikasi &rarr;
        </a>
    </div>
</div>

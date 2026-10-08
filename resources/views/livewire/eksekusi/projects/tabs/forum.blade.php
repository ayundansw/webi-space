<div>
    <livewire:eksekusi.projects.project-header :project="$project" />

    <x-eksekusi.project-tabs :project="$project" active="forum" />

    <div class="mt-6 flex items-center justify-between">
        <h2 class="font-display text-sm font-semibold text-ink">Diskusi Proyek</h2>
        <button type="button" wire:click="$set('showCreateForm', true)" class="rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90">+ Buat Thread</button>
    </div>

    @if ($showCreateForm)
        <form wire:submit="createThread" class="mt-3 space-y-2 rounded-card border border-muted/20 bg-white p-4 shadow-warm-xs">
            <input type="text" wire:model="newThreadTitle" placeholder="Judul thread" class="w-full rounded-lg border border-muted/40 px-3 py-1.5 text-sm focus:border-accent focus:outline-none">
            @error('newThreadTitle') <p class="text-xs text-danger">{{ $message }}</p> @enderror
            <textarea wire:model="newThreadContent" rows="3" placeholder="Isi thread..." class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"></textarea>
            @error('newThreadContent') <p class="text-xs text-danger">{{ $message }}</p> @enderror
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90">Kirim</button>
                <button type="button" wire:click="$set('showCreateForm', false)" class="rounded-lg border border-muted/40 px-3 py-1.5 text-xs text-ink hover:border-ink">Batal</button>
            </div>
        </form>
    @endif

    <div class="mt-4 space-y-2">
        @forelse ($threads as $thread)
            <button type="button" wire:click="openThread('{{ $thread->id }}')" class="block w-full rounded-card border border-muted/20 bg-white p-4 text-left shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md">
                <p class="text-sm font-medium text-ink">{{ $thread->title }}</p>
                <p class="mt-1 text-xs text-muted">oleh {{ $thread->creator->name }} &middot; {{ $thread->replies_count }} balasan</p>
            </button>
        @empty
            <p class="text-sm text-muted">Belum ada thread di proyek ini. Jadi yang pertama, yuk!</p>
        @endforelse
    </div>

    @if ($openThread)
        <div class="fixed inset-0 z-40 bg-ink/40" wire:click="closeThread"></div>

        <div class="fixed inset-y-0 right-0 z-50 flex w-full max-w-xl flex-col overflow-y-auto border-l border-muted/20 bg-white shadow-warm-lg">
            <div class="flex items-center justify-between border-b border-muted/20 p-4">
                <h2 class="font-display text-lg font-bold text-ink">Detail Thread</h2>
                <button type="button" wire:click="closeThread" aria-label="Tutup panel" class="rounded-control p-1 text-muted hover:bg-surface-alt hover:text-ink">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M18 6 6 18" /><path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 p-4">
                <h3 class="font-display text-lg font-bold text-ink">{{ $openThread->title }}</h3>
                <p class="mt-1 text-xs text-muted">oleh {{ $openThread->creator->name }} &middot; {{ $openThread->created_at->format('d M Y H:i') }}</p>
                <p class="mt-3 text-sm text-ink">{{ $openThread->content }}</p>

                <div class="mt-6 space-y-2">
                    <h4 class="font-display text-sm font-semibold text-ink">Balasan</h4>
                    @forelse ($openThread->replies as $reply)
                        <div class="rounded-lg border border-muted/20 p-3">
                            <p class="text-sm text-ink">{{ $reply->content }}</p>
                            <p class="mt-1 text-xs text-muted">{{ $reply->user->name }} &middot; {{ $reply->created_at->diffForHumans() }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-muted">Belum ada balasan.</p>
                    @endforelse
                </div>

                <form wire:submit="reply" class="mt-4 space-y-2">
                    <textarea wire:model="replyContent" rows="3" placeholder="Tulis balasanmu di sini..." class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"></textarea>
                    @error('replyContent') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    <button type="submit" class="rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white hover:bg-ink/90">Balas</button>
                </form>
            </div>
        </div>
    @endif
</div>

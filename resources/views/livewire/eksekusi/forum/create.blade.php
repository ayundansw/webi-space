<div class="max-w-xl">
    <a href="{{ url('/eksekusi/forum') }}" wire:navigate class="text-sm text-muted hover:text-ink">&larr; Kembali ke Forum General</a>

    <h1 class="font-display mt-4 text-2xl font-bold text-ink">Buat Thread Baru</h1>
    <p class="mt-1 text-sm text-muted">Diskusi lintas-proyek, terbuka untuk seluruh anggota Eksekusi.</p>

    <form wire:submit="save" class="mt-6 space-y-4 rounded-xl border border-muted/25 p-6">
        <div>
            <label for="title" class="mb-1 block text-sm text-ink">Judul</label>
            <input
                type="text"
                id="title"
                wire:model="title"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
            @error('title')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="content" class="mb-1 block text-sm text-ink">Isi</label>
            <textarea
                id="content"
                wire:model="content"
                rows="4"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            ></textarea>
            @error('content')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" wire:loading.attr="disabled" class="w-full rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60">
            Posting Thread
        </button>
    </form>
</div>

@push('breadcrumb-actions')
    <a href="{{ url('/admin/curriculum/challenges') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-control border border-muted/40 px-4 py-2 text-sm font-medium text-ink shadow-warm-xs hover:border-ink hover:bg-surface-alt">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
        </svg>
        Kembali
    </a>
@endpush

<div class="max-w-lg">
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Buat Challenge Baru</h1>
    </div>

    <form wire:submit="save" class="space-y-4 rounded-xl border border-muted/25 p-6">
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
            <label for="description" class="mb-1 block text-sm text-ink">Deskripsi</label>
            <textarea
                id="description"
                wire:model="description"
                rows="4"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            ></textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="level" class="mb-1 block text-sm text-ink">Level</label>
            <select
                id="level"
                wire:model="level"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
                <option value="low">Low</option>
                <option value="mid">Mid</option>
                <option value="high">High</option>
            </select>
            @error('level')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="points_reward" class="mb-1 block text-sm text-ink">Poin (kalau submission disetujui)</label>
            <input
                type="number"
                id="points_reward"
                wire:model="points_reward"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
            @error('points_reward')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="status" class="mb-1 block text-sm text-ink">Status</label>
            <select
                id="status"
                wire:model="status"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
                <option value="draft">Draft (belum terlihat anggota)</option>
                <option value="published">Published (tampil di halaman Praktik)</option>
            </select>
            @error('status')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            class="w-full rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60"
        >
            Buat Challenge
        </button>
    </form>
</div>

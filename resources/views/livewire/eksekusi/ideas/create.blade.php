<div class="max-w-xl">
    <h1 class="font-display text-2xl font-bold text-ink">Usulkan Ide Proyek</h1>
    <p class="mt-1 text-sm text-muted">Tidak ada validasi berat di sini — usulkan kapan saja, termasuk ide kompetisi yang mendadak.</p>

    <form wire:submit="save" class="mt-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-ink">Judul ide</label>
            <input type="text" wire:model="title" class="mt-1 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink">Deskripsi singkat</label>
            <textarea wire:model="description" rows="3" class="mt-1 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"></textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink">Tujuan / relevansi</label>
            <textarea wire:model="purpose" rows="3" placeholder="Kenapa ide ini layak dikerjakan?" class="mt-1 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"></textarea>
            @error('purpose') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">
            Kirim Ide
        </button>
    </form>
</div>

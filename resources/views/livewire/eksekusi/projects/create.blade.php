<div class="max-w-xl">
    <h1 class="font-display text-2xl font-bold text-ink">Buat Proyek Langsung</h1>
    <p class="mt-1 text-sm text-muted">Untuk proyek dari luar Project Ideas: permintaan organisasi, peluang kompetisi, atau proyek lanjutan.</p>

    <form wire:submit="save" class="mt-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-ink">Judul proyek</label>
            <input type="text" wire:model="title" class="mt-1 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink">Deskripsi detail</label>
            <textarea wire:model="description" rows="3" class="mt-1 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"></textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink">Tujuan terukur</label>
            <textarea wire:model="objective" rows="3" class="mt-1 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"></textarea>
            @error('objective') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink">Tipe proyek</label>
            <select wire:model="projectType" class="mt-1 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                <option value="internal">Internal</option>
                <option value="competition">Competition</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-ink">Tanggal mulai</label>
            <input type="date" wire:model="startDate" class="mt-1 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
            @error('startDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink">Target tanggal selesai</label>
            <input type="date" wire:model="targetEndDate" class="mt-1 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
            @error('targetEndDate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">
            Buat Proyek
        </button>
    </form>
</div>

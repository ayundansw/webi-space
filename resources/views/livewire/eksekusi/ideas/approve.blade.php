<div class="max-w-xl">
    <h1 class="font-display text-2xl font-bold text-ink">Approve Ide: {{ $idea->title }}</h1>
    <p class="mt-1 text-sm text-muted">Diusulkan oleh {{ $idea->proposer->name }}. Deskripsi dan tujuan bisa dilengkapi lagi nanti di halaman setup proyek.</p>

    <div class="mt-4 rounded-xl border border-muted/25 p-4 text-sm text-ink">
        <p>{{ $idea->description }}</p>
        <p class="mt-2 text-muted">Tujuan: {{ $idea->purpose }}</p>
    </div>

    <form wire:submit="save" class="mt-6 space-y-4">
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
            Approve dan Buat Proyek
        </button>
    </form>
</div>

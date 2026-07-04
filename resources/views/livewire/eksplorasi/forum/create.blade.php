<div class="max-w-xl">
    <a href="{{ url('/eksplorasi/forum') }}" class="text-sm text-muted hover:text-ink">&larr; Kembali ke Forum</a>

    <h1 class="font-display mt-4 text-2xl font-bold text-ink">Buat Thread Baru</h1>
    <p class="mt-1 text-sm text-muted">Tidak ada pertanyaan yang terlalu kecil untuk ditanyakan.</p>

    <form wire:submit="save" class="mt-6 space-y-4 rounded-xl border border-muted/25 p-6">
        <div>
            <label class="mb-1 block text-sm text-ink">Bahas modul atau unit mana?</label>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <select wire:model="moduleId" class="rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                    <option value="">-- Pilih Modul --</option>
                    @foreach ($modules as $module)
                        <option value="{{ $module->id }}">Modul {{ $module->order_number }}: {{ $module->title }}</option>
                    @endforeach
                </select>

                <select wire:model="unitId" class="rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                    <option value="">-- Pilih Unit (opsional) --</option>
                    @foreach ($modules as $module)
                        @foreach ($module->units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->title }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>
            @error('moduleId')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm text-ink">Mau bertanya ke siapa?</label>
            <div class="flex gap-4 text-sm text-ink">
                <label class="flex items-center gap-2">
                    <input type="radio" wire:model="target" value="peer"> Sesama Anggota
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" wire:model="target" value="pic"> Langsung ke PIC
                </label>
            </div>
        </div>

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
            <label for="content" class="mb-1 block text-sm text-ink">Isi Pertanyaan</label>
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

@push('breadcrumb-actions')
    <a href="{{ url('/admin/curriculum/units') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-control border border-muted/40 px-4 py-2 text-sm font-medium text-ink shadow-warm-xs hover:border-ink hover:bg-surface-alt">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
        </svg>
        Kembali
    </a>
@endpush

<div class="max-w-lg">
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Buat Unit Baru</h1>
    </div>

    <p class="mb-4 text-xs text-muted">
        Konten materi unit (heading, teks, gambar, dst) diisi belakangan lewat Editor Blok Konten, bukan di form ini: form ini cuma metadata dasar.
    </p>

    <form wire:submit="save" class="space-y-4 rounded-xl border border-muted/25 p-6">
        <div>
            <label for="module_id" class="mb-1 block text-sm text-ink">Modul</label>
            <select
                id="module_id"
                wire:model="module_id"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
                <option value="">Pilih modul</option>
                @foreach ($modules as $module)
                    <option value="{{ $module->id }}">{{ $module->order_number }}. {{ $module->title }}</option>
                @endforeach
            </select>
            @error('module_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="order_number" class="mb-1 block text-sm text-ink">Urutan Unit (dalam modul)</label>
            <input
                type="number"
                id="order_number"
                wire:model="order_number"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
            @error('order_number')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
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
            <label for="estimated_minutes" class="mb-1 block text-sm text-ink">Estimasi Waktu (menit)</label>
            <input
                type="number"
                id="estimated_minutes"
                wire:model="estimated_minutes"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
            @error('estimated_minutes')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="unit_type" class="mb-1 block text-sm text-ink">Tipe Unit</label>
            <select
                id="unit_type"
                wire:model="unit_type"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
                <option value="concept">Konsep</option>
                <option value="practice">Praktik</option>
            </select>
            @error('unit_type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="point_value" class="mb-1 block text-sm text-ink">Poin</label>
            <input
                type="number"
                id="point_value"
                wire:model="point_value"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
            @error('point_value')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="evaluation_type" class="mb-1 block text-sm text-ink">Tipe Evaluasi</label>
            <select
                id="evaluation_type"
                wire:model="evaluation_type"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
                <option value="none">Tidak ada</option>
                <option value="quiz_multiple_choice">Kuis Pilihan Ganda</option>
                <option value="quiz_matching">Kuis Mencocokkan</option>
                <option value="quiz_ordering">Kuis Mengurutkan</option>
                <option value="essay">Esai</option>
                <option value="practice">Praktik</option>
            </select>
            @error('evaluation_type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="prerequisite_unit_id" class="mb-1 block text-sm text-ink">Prasyarat (opsional)</label>
            <select
                id="prerequisite_unit_id"
                wire:model="prerequisite_unit_id"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
                <option value="">Tidak ada prasyarat</option>
                @foreach ($prerequisiteOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->module->title }}: {{ $option->title }}</option>
                @endforeach
            </select>
            @error('prerequisite_unit_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            class="w-full rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60"
        >
            Buat Unit
        </button>
    </form>
</div>

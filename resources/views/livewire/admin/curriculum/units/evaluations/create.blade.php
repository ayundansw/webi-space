@push('breadcrumb-actions')
    <a href="{{ url('/admin/curriculum/units/'.$unit->id.'/evaluations') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-control border border-muted/40 px-4 py-2 text-sm font-medium text-ink shadow-warm-xs hover:border-ink hover:bg-surface-alt">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
        </svg>
        Kembali
    </a>
@endpush

<div class="max-w-lg">
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Tambah Soal</h1>
    </div>

    <p class="mb-4 text-xs text-muted">{{ $unit->title }}</p>

    <form wire:submit="save" class="space-y-4 rounded-xl border border-muted/25 p-6">
        <div>
            <label for="sort_order" class="mb-1 block text-sm text-ink">Urutan Soal</label>
            <input
                type="number"
                id="sort_order"
                wire:model="sort_order"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
            @error('sort_order') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        @include('livewire.admin.curriculum.units.evaluations._question-fields')

        <button
            type="submit"
            wire:loading.attr="disabled"
            class="w-full rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60"
        >
            Buat Soal
        </button>
    </form>
</div>

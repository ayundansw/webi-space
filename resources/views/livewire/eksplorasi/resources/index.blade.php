{{--
    wire:click TIDAK dipakai di tombol ini dengan sengaja -- @push
    memindahkan tombol ini ke dalam <x-shell.breadcrumb> di app.blade.php,
    di LUAR div wire:id komponen ini (sibling di dalam <main>, bukan
    descendant), jadi $wire/wire:click di sini tidak bisa resolve ke
    instance komponen ini. Dispatch event window murni (Alpine $dispatch),
    ditangkap oleh listener .window di root div bawah yang MASIH di dalam
    boundary komponen ini, baru dari situ panggil $wire.openSubmitForm().
--}}
{{-- Fase 8 Batch 3: tombol submit disembunyikan total untuk mode baca
     Eksplorasi -- submit() sendiri sudah di-guard, ini cuma mencegah UX
     jalan buntu (isi form lalu ditolak di akhir). --}}
@unless ($isReadOnlyExploration)
    @push('breadcrumb-actions')
        <button type="button" @click="$dispatch('open-resource-form')" class="inline-flex shrink-0 items-center gap-1.5 rounded-control bg-ink px-4 py-2 text-sm font-medium text-white shadow-warm-xs hover:bg-ink/90">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                <path d="M12 5v14" /><path d="M5 12h14" />
            </svg>
            Ajukan Referensi
        </button>
    @endpush
@endunless

<div x-on:open-resource-form.window="$wire.openSubmitForm()">
    {{--
        Posisi judul disamakan dengan Peta Kurikulum/WEBI (tanpa margin-top
        tambahan) sesuai permintaan Aye -- mt-10 yang sempat ditambahkan di
        titik sebelumnya dicabut lagi, konsistensi posisi antar halaman
        lebih diutamakan daripada jarak ekstra dari card breadcrumb+tombol.
    --}}
    <h1 class="font-display text-2xl font-bold text-ink">Referensi Belajar</h1>
    <p class="mt-1 text-sm text-muted">Kumpulan sumber tambahan per modul. Bisa kamu akses kapan saja, tidak harus menunggu sampai di modulnya.</p>

    @if (session('resource_submitted'))
        <div class="mt-4 rounded-lg border border-success/40 bg-success-soft px-4 py-3 text-sm text-ink">
            {{ session('resource_submitted') }}
        </div>
    @endif

    {{--
        Bagian A3: card per modul + card "General" (referensi tanpa modul
        spesifik, module_id null) + card per referensi, bahasa visual sama
        dengan Dashboard Eksplorasi (border-muted/20, shadow-warm-xs, radius,
        aksen pixel left/right). Badge "Dari Anggota"/"Dari Admin" dibaca
        langsung dari created_by null atau tidak.
    --}}
    <div class="mt-6 space-y-6">
        @foreach ($modules as $module)
            <div class="relative overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs">
                <div class="card-pixel-accent-left" aria-hidden="true"></div>
                <div class="card-pixel-accent-right" aria-hidden="true"></div>

                <h2 class="font-display text-base font-semibold text-heading">Modul {{ $module->order_number }}: {{ $module->title }}</h2>

                @if ($module->learningResources->isEmpty())
                    <p class="mt-3 text-sm text-muted">Belum ada referensi tambahan untuk modul ini.</p>
                @else
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($module->learningResources as $resource)
                            @include('livewire.eksplorasi.resources._resource-card', ['resource' => $resource])
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        {{-- Card "General": referensi yang tidak terkait modul manapun. --}}
        <div class="relative overflow-hidden rounded-xl border border-dashed border-muted/40 bg-white/60 p-5">
            <div class="card-pixel-accent-left" aria-hidden="true"></div>
            <div class="card-pixel-accent-right" aria-hidden="true"></div>

            <h2 class="font-display text-base font-semibold text-heading">General</h2>
            <p class="text-xs text-caption">Referensi umum, tidak terikat modul tertentu.</p>

            @if ($generalResources->isEmpty())
                <p class="mt-3 text-sm text-muted">Belum ada referensi umum.</p>
            @else
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($generalResources as $resource)
                        @include('livewire.eksplorasi.resources._resource-card', ['resource' => $resource])
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{--
        Modal submit referensi. Alpine murni untuk overlay/transisi,
        Livewire untuk data (wire:model + wire:submit) -- pola yang sama
        dipakai komponen lain di aplikasi ini (Alpine untuk state visual,
        Livewire untuk state data).
    --}}
    <div
        x-show="$wire.showSubmitForm"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <div
            x-show="$wire.showSubmitForm"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="$wire.closeSubmitForm()"
            class="absolute inset-0 bg-ink/40"
        ></div>

        <div
            x-show="$wire.showSubmitForm"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-md rounded-modal border border-muted/20 bg-white p-6 shadow-warm-lg"
        >
            <div class="flex items-center justify-between">
                <h2 class="font-display text-lg font-bold text-heading">Ajukan Referensi</h2>
                <button type="button" wire:click="closeSubmitForm" aria-label="Tutup" class="rounded-control p-1 text-muted hover:bg-surface-alt hover:text-ink">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M18 6 6 18" /><path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>
            <p class="mt-1 text-sm text-muted">Punya artikel/video/tools yang membantu kamu belajar? Bagikan ke anggota lain.</p>

            <form wire:submit="submit" class="mt-4 space-y-4">
                <div>
                    <label for="resource-title" class="text-sm font-medium text-ink">Judul</label>
                    <input id="resource-title" type="text" wire:model="title" class="mt-1 w-full rounded-control border border-muted/40 px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none" placeholder="Contoh: Panduan Flexbox Lengkap">
                    @error('title') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="resource-url" class="text-sm font-medium text-ink">URL</label>
                    <input id="resource-url" type="text" wire:model="url" class="mt-1 w-full rounded-control border border-muted/40 px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none" placeholder="https://...">
                    @error('url') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="resource-description" class="text-sm font-medium text-ink">Deskripsi <span class="font-normal text-muted">(opsional)</span></label>
                    <textarea id="resource-description" wire:model="description" rows="2" class="mt-1 w-full rounded-control border border-muted/40 px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none" placeholder="Kenapa referensi ini membantu?"></textarea>
                    @error('description') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="resource-module" class="text-sm font-medium text-ink">Terkait Modul <span class="font-normal text-muted">(opsional)</span></label>
                    <select id="resource-module" wire:model="moduleId" class="mt-1 w-full rounded-control border border-muted/40 px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none">
                        <option value="">General (tidak terkait modul manapun)</option>
                        @foreach ($modules as $module)
                            <option value="{{ $module->id }}">Modul {{ $module->order_number }}: {{ $module->title }}</option>
                        @endforeach
                    </select>
                    @error('moduleId') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeSubmitForm" class="rounded-control border border-muted/40 px-4 py-2 text-sm text-ink hover:border-ink">Batal</button>
                    <button type="submit" class="rounded-control bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">Kirim</button>
                </div>
            </form>
        </div>
    </div>
</div>

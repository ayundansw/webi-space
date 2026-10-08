@push('breadcrumb-actions')
    <a href="{{ url('/admin/curriculum/challenges') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-control border border-muted/40 px-4 py-2 text-sm font-medium text-ink shadow-warm-xs hover:border-ink hover:bg-surface-alt">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
        </svg>
        Kembali
    </a>
@endpush

<div>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Kelola Challenge</h1>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-muted/25 bg-green-50 p-4 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    {{--
        Task 2 (revisi halaman Kelola Praktik): 2 kolom di desktop (form di
        kiri, DUA card sidebar di kanan) supaya sisi kanan layar tidak
        kosong seperti versi max-w-lg lama -- fallback 1 kolom stack di
        bawah `lg`. Urutan DOM SENGAJA form dulu baru sidebar (bukan
        Track Map dulu seperti tata letak lama) supaya urutan tab/fokus
        keyboard tetap logis meski posisi visual sidebar ada di kanan --
        grid auto-placement menaruh child DOM pertama di kolom kiri tanpa
        perlu utak-atik `order-*`.

        Perbaikan lanjutan: wrapper `max-w-5xl` (di root, lihat atas) DIHAPUS
        -- itu membatasi lebar total di bawah `max-w-7xl` yang sudah
        disediakan <main> di layout, sebelum grid ini sempat memakai ruang
        yang tersedia (persis bug yang sama dengan halaman Profil). Sidebar
        dilebarkan 20rem -> 22rem supaya rasio kiri:kanan tetap terasa
        seimbang di lebar penuh, bukan cuma form yang melebar sendirian.
    --}}
    <div class="lg:grid lg:grid-cols-[1fr_22rem] lg:items-start lg:gap-6">
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
            Simpan Perubahan
        </button>
    </form>

    {{-- Sidebar (kolom kanan di desktop): Track Map di atas, Lampiran di
         bawahnya, stack vertikal dalam satu kolom. --}}
    <div class="mt-6 space-y-6 lg:mt-0">
        <div class="rounded-xl border border-muted/25 p-4">
            <h2 class="font-display text-sm font-bold text-ink">Track Map</h2>
            <p class="mt-1 text-xs text-muted">{{ $stepCount }} step tersimpan untuk challenge ini.</p>
            <a href="{{ url('/admin/curriculum/challenges/'.$challenge->id.'/steps') }}" class="mt-3 inline-block rounded-lg border border-muted/40 px-3 py-1.5 text-sm text-ink hover:border-accent hover:text-accent">Kelola Track Map</a>
        </div>

        {{-- Praktik 3 Bagian B: lampiran referensi (brief, starter asset, dst) —
             terlihat oleh SEMUA anggota begitu challenge published, bukan
             privat seperti file submission member. --}}
        <div class="rounded-xl border border-muted/25 p-6">
            <h2 class="font-display text-sm font-bold text-ink">Lampiran Challenge</h2>
            <p class="mt-1 text-xs text-muted">Terlihat oleh semua anggota begitu challenge ini published.</p>

            <div class="mt-4 space-y-2">
                @forelse ($attachments as $attachment)
                    <div class="flex items-center justify-between gap-2 rounded-lg border border-muted/20 px-3 py-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm text-ink">{{ $attachment->file_name }}</p>
                            <p class="text-xs text-caption">{{ number_format($attachment->file_size / 1024, 0) }} KB</p>
                        </div>
                        <button
                            type="button"
                            wire:click="deleteAttachment('{{ $attachment->id }}')"
                            wire:confirm="Yakin hapus lampiran ini?"
                            class="shrink-0 text-sm text-red-600 hover:text-red-700"
                        >
                            Hapus
                        </button>
                    </div>
                @empty
                    <p class="text-sm text-muted">Belum ada lampiran.</p>
                @endforelse
            </div>

            <form wire:submit="uploadAttachments" class="mt-4 space-y-2">
                <input type="file" wire:model="newAttachments" multiple class="w-full text-sm">
                <p class="text-xs text-muted">Maks. 10MB per file. Tipe: gambar (jpg/png/gif/webp), PDF, video (mp4/mov/webm), atau zip.</p>
                @error('newAttachments') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @error('newAttachments.*') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <div wire:loading wire:target="newAttachments" class="text-xs text-muted">Mengunggah...</div>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="rounded-lg border border-muted/40 px-3 py-1.5 text-sm text-ink hover:border-accent"
                >
                    Unggah Lampiran
                </button>
            </form>
        </div>
    </div>
    </div>
</div>

@php
    // Icons matching the outline stroke style already used in the sidebar
    // (viewBox 24, stroke-width 1.75, currentColor) — no icon package.
    $targetIcon = fn (string $target) => $target === 'pic'
        ? '<path d="M12 3 4 6v5c0 4.5 3 7.5 8 10 5-2.5 8-5.5 8-10V6Z" />'
        : '<circle cx="9" cy="9" r="3" /><path d="M3.5 20a5.5 5.5 0 0 1 11 0" /><circle cx="17" cy="9" r="2.25" /><path d="M15.5 14.5A4.5 4.5 0 0 1 20 19" />';
@endphp

{{--
    Bagian A (perbaikan lanjutan): tombol "Buat Thread" dipindah sejajar
    card breadcrumb lewat @push('breadcrumb-actions') (sama seperti
    Referensi). wire:click TIDAK dipakai di sini -- lihat komentar sama di
    resources/index.blade.php, tombol ini dirender DI LUAR div wire:id
    komponen ini, jadi dispatch event window murni, ditangkap listener
    .window di root div bawah yang masih di dalam boundary komponen ini.
--}}
{{-- Fase 8 Batch 3: tombol buat thread disembunyikan untuk mode baca
     Eksplorasi -- Forum\Create::save() sudah di-guard, ini cuma mencegah
     modal kebuka untuk sesuatu yang pasti ditolak. --}}
@unless ($isReadOnlyExploration)
    @push('breadcrumb-actions')
        <button type="button" @click="$dispatch('open-thread-form')" class="inline-flex shrink-0 items-center gap-1.5 rounded-control bg-ink px-4 py-2 text-sm font-medium text-white shadow-warm-xs hover:bg-ink/90">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                <path d="M12 5v14" /><path d="M5 12h14" />
            </svg>
            Buat Thread
        </button>
    @endpush
@endunless

<div x-data="{ showCreateForm: false }" x-on:open-thread-form.window="showCreateForm = true">
    {{--
        Posisi judul disamakan dengan Peta Kurikulum/WEBI (tanpa margin-top
        tambahan) sesuai permintaan Aye -- mt-10 yang sempat ditambahkan di
        titik sebelumnya dicabut lagi, konsistensi posisi antar halaman
        lebih diutamakan daripada jarak ekstra dari card breadcrumb+tombol.
    --}}
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Forum Diskusi Eksplorasi</h1>
        <p class="mt-1 text-sm text-muted">Bertanya ke sesama anggota atau langsung ke PIC, tidak perlu ragu.</p>
    </div>

    <div class="space-y-3">
        @forelse ($threads as $thread)
            <a href="{{ url('/eksplorasi/forum/'.$thread->id) }}" class="block rounded-xl border border-muted/20 bg-white p-4 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-ink">{{ $thread->title }}</p>
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs {{ $thread->target === 'pic' ? 'bg-accent-soft/60 text-ink' : 'bg-muted/15 text-muted' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 shrink-0">
                            {!! $targetIcon($thread->target) !!}
                        </svg>
                        {{ $thread->target === 'pic' ? 'Ke PIC' : 'Ke Sesama Anggota' }}
                    </span>
                </div>
                <p class="mt-1 flex flex-wrap items-center gap-1 text-xs text-muted">
                    <span>oleh {{ $thread->creator->name }}</span>
                    @if ($thread->module) <span>&middot; Modul {{ $thread->module->order_number }}: {{ $thread->module->title }}</span> @endif
                    @if ($thread->unit) <span>&middot; Unit: {{ $thread->unit->title }}</span> @endif
                    @if (! $thread->module && ! $thread->unit) <span>&middot; General</span> @endif
                    <span class="inline-flex items-center gap-1">
                        &middot;
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 shrink-0">
                            <path d="M4 5h13v9H8l-4 4V5Z" />
                        </svg>
                        {{ $thread->replies->count() }} balasan
                    </span>
                </p>
            </a>
        @empty
            <p class="text-sm text-muted">Belum ada thread. Jadi yang pertama bertanya, yuk!</p>
        @endforelse
    </div>

    {{--
        Modal "Buat Thread" -- embed Forum\Create APA ADANYA (bukan
        duplikasi field/validasi). Komponen itu sendiri tetap berfungsi
        sebagai halaman penuh di /eksplorasi/forum/create (tidak dihapus),
        cuma jalur utama UX sekarang lewat modal ini, konsisten pola
        Referensi. save() di Forum\Create redirect ke halaman thread baru
        kalau sukses -- redirect itu otomatis "menutup" modal juga (pindah
        halaman), jadi tidak perlu wiring tambahan untuk itu.
    --}}
    <div
        x-show="showCreateForm"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <div
            x-show="showCreateForm"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="showCreateForm = false"
            class="absolute inset-0 bg-ink/40"
        ></div>

        <div
            x-show="showCreateForm"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-modal border border-muted/20 bg-white p-6 shadow-warm-lg"
        >
            <div class="flex items-center justify-between">
                <h2 class="font-display text-lg font-bold text-heading">Buat Thread Baru</h2>
                <button type="button" @click="showCreateForm = false" aria-label="Tutup" class="rounded-control p-1 text-muted hover:bg-surface-alt hover:text-ink">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M18 6 6 18" /><path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-4">
                <livewire:eksplorasi.forum.create :key="'forum-create-modal'" />
            </div>
        </div>
    </div>
</div>

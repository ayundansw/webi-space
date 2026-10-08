@php
    // Icons matching the outline stroke style already used elsewhere in
    // Eksekusi (viewBox 24, stroke-width 1.75, currentColor) — no icon package.
    $statusIcons = [
        'draft' => '<path d="m16.5 4.5 3 3L8 19l-4 1 1-4Z" />',
        'approved' => '<path d="m5 13 4 4 10-10" />',
        'rejected' => '<path d="m6 6 12 12" /><path d="m18 6-12 12" />',
    ];

    $statusColors = [
        'draft' => 'text-muted bg-muted/15',
        'approved' => 'text-success bg-success-soft',
        'rejected' => 'text-danger bg-danger-soft',
    ];

    $statusLabels = [
        'draft' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];

    // Status bisa diubah bebas ke arah manapun (Bagian 1 poin 6) — cuma
    // tawarkan status yang BEDA dari status idea saat ini, tidak ada
    // gunanya tombol "ubah ke status yang sama".
    $otherStatuses = collect(['draft' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'])
        ->except($idea->status);
@endphp

<div class="rounded-card border border-muted/20 bg-white p-4 shadow-warm-xs" x-data="{ detailOpen: false }">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="font-medium text-ink">{{ $idea->title }}</p>
        <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-xs {{ $statusColors[$idea->status] ?? 'text-muted bg-muted/15' }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 shrink-0">
                {!! $statusIcons[$idea->status] ?? '' !!}
            </svg>
            {{ $statusLabels[$idea->status] ?? ucfirst($idea->status) }}
        </span>
    </div>

    <p class="mt-1 text-sm text-muted">Diusulkan oleh {{ $idea->proposer->name }}</p>

    @if ($idea->description)
        <p class="mt-2 line-clamp-2 text-sm text-ink">{{ $idea->description }}</p>
    @endif

    @if ($idea->status === 'rejected' && $idea->rejection_reason)
        <p class="mt-2 line-clamp-2 text-sm text-danger">Alasan penolakan: {{ $idea->rejection_reason }}</p>
    @endif

    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
        <button type="button" @click="detailOpen = true" class="rounded-lg border border-ink/20 px-3 py-1.5 text-sm font-medium text-ink hover:border-ink hover:bg-accent-soft">
            Lihat Detail
        </button>

        @if (auth()->user()->role === 'admin')
            <div class="relative" x-data="{ statusMenuOpen: false }">
                <button type="button" @click="statusMenuOpen = !statusMenuOpen" class="rounded-lg border border-muted/40 px-3 py-1.5 text-sm text-ink hover:border-ink">
                    Ubah Status
                </button>

                <div
                    x-show="statusMenuOpen"
                    x-cloak
                    @click.outside="statusMenuOpen = false"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute right-0 z-10 mt-1 w-52 rounded-lg border border-muted/20 bg-white p-1.5 shadow-warm-md"
                >
                    @foreach ($otherStatuses as $value => $label)
                        @if ($value === 'rejected')
                            <div x-data="{ rejecting: false }">
                                <button type="button" @click="rejecting = !rejecting" class="block w-full rounded-md px-2 py-1.5 text-left text-sm text-ink hover:bg-surface-alt">
                                    {{ $label }}
                                </button>
                                <div x-show="rejecting" class="px-2 pb-1.5">
                                    <textarea wire:model="rejectReasons.{{ $idea->id }}" rows="2" placeholder="Alasan penolakan (wajib)" class="w-full rounded-lg border border-muted/40 px-2 py-1.5 text-xs focus:border-accent focus:outline-none"></textarea>
                                    @error('rejectReasons.'.$idea->id) <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                                    <button wire:click="changeStatus('{{ $idea->id }}', 'rejected')" type="button" class="mt-1 rounded-lg bg-ink px-3 py-1 text-xs font-medium text-white hover:bg-ink/90">
                                        Kirim Penolakan
                                    </button>
                                </div>
                            </div>
                        @else
                            <button wire:click="changeStatus('{{ $idea->id }}', '{{ $value }}')" type="button" @click="statusMenuOpen = false" class="block w-full rounded-md px-2 py-1.5 text-left text-sm text-ink hover:bg-surface-alt">
                                {{ $label }}
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Detail popup (2.2.2a Langkah 3): reuses data already loaded for the
         card above, no extra Livewire round-trip. Pattern mirrors the
         x-data/x-show/x-transition/x-cloak already used in bell.blade.php.
         Satu-satunya tempat description DAN purpose ditampilkan LENGKAP
         (tidak dipotong) — kartu list di atas cuma menampilkan ringkasan
         description terpotong (line-clamp-2), purpose tidak diulang di
         sana sama sekali, supaya tidak terasa membludak. --}}
    <div
        x-show="detailOpen"
        x-cloak
        x-transition:enter="transition-opacity duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="detailOpen = false"
        @keydown.escape.window="detailOpen = false"
        class="fixed inset-0 z-40 flex items-center justify-center bg-ink/40 p-4"
    >
        <div
            @click.stop
            x-show="detailOpen"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="w-full max-w-lg rounded-modal border border-muted/20 bg-white p-6 shadow-warm-lg"
        >
            <div class="flex items-start justify-between gap-4">
                <h2 class="font-display text-lg font-bold text-ink">{{ $idea->title }}</h2>
                <button type="button" @click="detailOpen = false" class="text-muted hover:text-ink" aria-label="Tutup">&times;</button>
            </div>

            <p class="mt-1 text-xs font-mono uppercase text-muted">
                {{ $statusLabels[$idea->status] ?? ucfirst($idea->status) }} &bull; diusulkan {{ $idea->created_at->diffForHumans() }}
            </p>
            <p class="mt-3 text-sm text-muted">Diusulkan oleh <span class="text-ink">{{ $idea->proposer->name }}</span></p>

            <div class="mt-4">
                <p class="text-xs font-medium uppercase text-muted">Deskripsi</p>
                <p class="mt-1 text-sm text-ink">{{ $idea->description ?: '(tidak diisi)' }}</p>
            </div>

            <div class="mt-3">
                <p class="text-xs font-medium uppercase text-muted">Tujuan</p>
                <p class="mt-1 text-sm text-ink">{{ $idea->purpose ?: '(tidak diisi)' }}</p>
            </div>

            @if ($idea->status === 'rejected' && $idea->rejection_reason)
                <div class="mt-3">
                    <p class="text-xs font-medium uppercase text-muted">Alasan Penolakan</p>
                    <p class="mt-1 text-sm text-danger">{{ $idea->rejection_reason }}</p>
                </div>
            @endif

            @if ($idea->promotedToProject)
                <div class="mt-3">
                    <p class="text-xs font-medium uppercase text-muted">Sudah Jadi Proyek</p>
                    <a href="{{ url('/eksekusi/projects/'.$idea->promotedToProject->id) }}" wire:navigate class="mt-1 inline-block text-sm text-accent hover:underline">
                        {{ $idea->promotedToProject->title }} &rarr;
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

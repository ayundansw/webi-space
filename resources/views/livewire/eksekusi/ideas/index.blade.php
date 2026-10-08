@push('breadcrumb-actions')
    <a href="{{ url('/eksekusi/ideas/create') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-control bg-ink px-4 py-2 text-sm font-medium text-white shadow-warm-xs hover:bg-ink/90">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M12 5v14" /><path d="M5 12h14" />
        </svg>
        Usulkan Ide
    </a>
@endpush

<div>
    <h1 class="font-display text-2xl font-bold text-ink">Project Ideas</h1>

    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Cari judul ide..."
            class="w-full rounded-control border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none sm:flex-1"
        >
        <select wire:model.live="statusFilter" class="w-full rounded-control border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none sm:w-48">
            <option value="">Semua Status</option>
            <option value="draft">Menunggu</option>
            <option value="approved">Disetujui</option>
            <option value="rejected">Ditolak</option>
        </select>
    </div>

    @if ($filteredIdeas !== null)
        {{-- Status tertentu dipilih di filter -- satu daftar gabungan, bukan
             dipisah dua section yang isinya kemungkinan besar cuma echo
             satu sama lain untuk hasil filter yang sudah sempit. --}}
        <section class="mt-6">
            <h2 class="font-display text-lg font-bold text-ink">Hasil Filter</h2>

            <div class="mt-3 space-y-3">
                @forelse ($filteredIdeas as $idea)
                    @include('livewire.eksekusi.ideas._idea-card', ['idea' => $idea])
                @empty
                    <p class="rounded-card border border-muted/20 bg-white p-4 text-sm text-muted shadow-warm-xs">Tidak ada ide yang cocok dengan pencarian/filter ini.</p>
                @endforelse
            </div>
        </section>
    @else
        <section class="mt-6">
            <h2 class="font-display text-lg font-bold text-ink">Menunggu Keputusan</h2>
            <p class="mt-1 text-sm text-muted">Ide yang belum disetujui atau ditolak admin.</p>

            <div class="mt-3 space-y-3">
                @forelse ($pendingIdeas as $idea)
                    @include('livewire.eksekusi.ideas._idea-card', ['idea' => $idea])
                @empty
                    <p class="rounded-card border border-muted/20 bg-white p-4 text-sm text-muted shadow-warm-xs">Tidak ada ide yang sedang menunggu keputusan.</p>
                @endforelse
            </div>
        </section>

        <section class="mt-10">
            <h2 class="font-display text-lg font-bold text-ink">Riwayat</h2>
            <p class="mt-1 text-sm text-muted">Ide yang sudah diputuskan: disetujui atau ditolak.</p>

            <div class="mt-3 space-y-3">
                @forelse ($historyIdeas as $idea)
                    @include('livewire.eksekusi.ideas._idea-card', ['idea' => $idea])
                @empty
                    <p class="rounded-card border border-muted/20 bg-white p-4 text-sm text-muted shadow-warm-xs">Belum ada ide yang diputuskan.</p>
                @endforelse
            </div>
        </section>
    @endif
</div>

<div class="mt-10 sm:mt-12">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl font-bold text-ink">{{ $project->title }}</h1>
            <p class="mt-1 text-sm text-muted">{{ ucfirst($project->project_type) }} &middot; {{ $project->start_date->format('d M Y') }} &ndash; {{ $project->target_end_date->format('d M Y') }}</p>
        </div>

        <div class="flex flex-col items-end gap-2">
            <span class="rounded-lg border border-muted/40 px-3 py-1.5 text-sm font-mono uppercase text-ink">{{ $project->status }}</span>

            {{-- Task 1 (Tahap B, Isi Proyek): dipindah sejajar badge status
                 (dulu di bawah card deskripsi) + solid background per aksi
                 supaya jelas beda dari tombol navigasi biasa yang di app ini
                 konsisten pakai border flat. Warna ikut asosiasi status yang
                 sudah dipakai di tempat lain (index proyek/dashboard):
                 on_hold = warning, completed = success, active = accent,
                 archived = ink (tidak ada token khusus "archived"). --}}
            @if (auth()->user()->role === 'admin')
                <div class="flex flex-wrap justify-end gap-2">
                    @if ($project->status === 'active')
                        <button wire:click="changeStatus('on_hold')" class="rounded-lg bg-warning px-3 py-1.5 text-xs font-medium text-white shadow-warm-xs hover:bg-warning/90">Pause (On Hold)</button>
                        <button wire:click="changeStatus('completed')" class="rounded-lg bg-success px-3 py-1.5 text-xs font-medium text-white shadow-warm-xs hover:bg-success/90">Tandai Completed</button>
                    @elseif ($project->status === 'on_hold')
                        <button wire:click="changeStatus('active')" class="rounded-lg bg-accent px-3 py-1.5 text-xs font-medium text-ink shadow-warm-xs hover:bg-accent/90">Resume (Active)</button>
                    @elseif ($project->status === 'completed')
                        <button wire:click="changeStatus('archived')" class="rounded-lg bg-ink px-3 py-1.5 text-xs font-medium text-white shadow-warm-xs hover:bg-ink/90">Archive</button>
                    @endif
                </div>
            @endif
        </div>
    </div>
    @error('status') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror

    <div class="mt-4 rounded-xl border border-muted/25 p-4 text-sm text-ink">
        <p>{{ $project->description }}</p>
        <p class="mt-2 text-muted">Tujuan: {{ $project->objective }}</p>
        <p class="mt-2 font-mono">{{ $project->progressPercentage() }}% task selesai</p>
    </div>

    {{--
        Task 3 (Tahap B, Isi Proyek): grid 2 kolom (bukan list 1 kolom),
        `items-start` supaya card di kolom kiri/kanan tidak dipaksa stretch
        sama tinggi (auto-height CSS grid akan menyamakan tinggi baris kalau
        tidak di-override). Nomor urut eksplisit di pojok tiap card (angka
        dari $loop->iteration, bukan cuma posisi visual). Form "+ Tambah
        Milestone" pindah dari inline-selalu-tampil jadi modal popup, pola
        sama dengan modal detail _idea-card.blade.php.
    --}}
    <div class="mt-6" x-data="{ milestoneModalOpen: false }" x-on:milestone-added.window="milestoneModalOpen = false">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-display text-base font-bold text-ink">Milestone</h2>

            @if (auth()->user()->role === 'admin' && $project->status !== 'archived')
                <button type="button" @click="milestoneModalOpen = true" class="inline-flex shrink-0 items-center gap-1.5 rounded-control bg-ink px-3 py-1.5 text-xs font-medium text-white shadow-warm-xs hover:bg-ink/90">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                        <path d="M12 5v14" /><path d="M5 12h14" />
                    </svg>
                    Tambah Milestone
                </button>
            @endif
        </div>

        <div class="mt-3 grid grid-cols-1 items-start gap-3 sm:grid-cols-2">
            @forelse ($milestones as $milestone)
                <div class="relative rounded-lg border border-muted/25 bg-white p-3 pt-4">
                    <span class="absolute -top-2 -left-2 flex h-6 w-6 items-center justify-center rounded-full bg-ink font-mono text-xs font-semibold text-white shadow-warm-xs">
                        {{ $loop->iteration }}
                    </span>
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-medium text-ink">{{ $milestone->title }}</p>
                        <span class="shrink-0 font-mono text-xs text-muted">{{ $milestone->progressPercentage() }}%</span>
                    </div>
                    @if ($milestone->description)
                        <p class="mt-1 text-sm text-muted">{{ $milestone->description }}</p>
                    @endif
                    <p class="mt-1 text-xs text-muted">Target: {{ $milestone->target_date->format('d M Y') }}</p>
                    <div class="mt-2 h-1.5 rounded-full bg-muted/20">
                        <div class="h-1.5 rounded-full bg-accent" style="width: {{ $milestone->progressPercentage() }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-muted sm:col-span-2">Belum ada milestone.</p>
            @endforelse
        </div>

        @if (auth()->user()->role === 'admin' && $project->status !== 'archived')
            <div
                x-show="milestoneModalOpen"
                x-cloak
                x-transition:enter="transition-opacity duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="milestoneModalOpen = false"
                @keydown.escape.window="milestoneModalOpen = false"
                class="fixed inset-0 z-40 flex items-center justify-center bg-ink/40 p-4"
            >
                <div
                    @click.stop
                    x-show="milestoneModalOpen"
                    x-transition:enter="transition duration-200 ease-out"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition duration-150 ease-in"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="w-full max-w-md rounded-modal border border-muted/20 bg-white p-6 shadow-warm-lg"
                >
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="font-display text-lg font-bold text-ink">Tambah Milestone</h3>
                        <button type="button" @click="milestoneModalOpen = false" class="text-muted hover:text-ink" aria-label="Tutup">&times;</button>
                    </div>

                    <form wire:submit="addMilestone" class="mt-4 space-y-3">
                        <div>
                            <input type="text" wire:model="milestoneTitle" placeholder="Judul milestone" class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                            @error('milestoneTitle') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <textarea wire:model="milestoneDescription" placeholder="Deskripsi (opsional)" rows="2" class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"></textarea>
                        </div>
                        <div>
                            <input type="date" wire:model="milestoneTargetDate" class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none">
                            @error('milestoneTargetDate') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="w-full rounded-lg bg-ink px-3 py-2 text-sm font-medium text-white hover:bg-ink/90">+ Tambah Milestone</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>

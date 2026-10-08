<div x-data="{ panelOpen: @entangle('openTaskId').live }">
    {{--
        Fase 7 Batch 1a: header + tab bar ditambahkan DI ATAS konten Kanban
        yang sudah ada — SATU-SATUNYA perubahan di file ini pada batch itu.
        Semua yang di bawah baris ini (termasuk h1 "Kanban Board" yang jadi
        sedikit redundan dengan judul di ProjectHeader — dibiarkan apa
        adanya, prioritas zero-risk di atas kerapian kosmetik) TIDAK
        DISENTUH sama sekali dari sebelum batch itu.

        Fase 7 Batch 1b: `x-data` di atas (root div) menambahkan
        `panelOpen`, di-entangle dua arah ke properti Livewire
        `$openTaskId` — string task_id kalau panel terbuka, null kalau
        tertutup. `x-show="panelOpen"` di bawah otomatis truthy/falsy
        mengikuti nilai itu, animasi transisi tetap ditangani Alpine
        (instan, tidak menunggu round-trip server) sementara `.live`
        menyinkronkan balik ke server (mis. saat backdrop diklik untuk
        menutup) supaya komponen nested di dalamnya selalu tahu task mana
        yang sedang tampil.
    --}}
    <livewire:eksekusi.projects.project-header :project="$project" />

    <x-eksekusi.project-tabs :project="$project" active="kanban" />

    <div class="mt-6 flex items-center justify-between">
        <h1 class="font-display text-2xl font-bold text-ink">Kanban Board: {{ $project->title }}</h1>
        <a href="{{ url('/eksekusi/projects/'.$project->id.'/tasks/create') }}" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">
            + Buat Task
        </a>
    </div>

    <p class="mt-2 text-xs text-muted">Geser kartu antar kolom, atau pakai tombol di kartu, keduanya dicek aturan yang sama.</p>

    @error('status') <p class="mt-2 text-sm text-danger">{{ $message }}</p> @enderror

    {{--
        Native HTML5 drag-and-drop (dragstart/dragover/drop) + Alpine for the
        visual state (dragging opacity, drop-target highlight) — no library.
        On drop, calls the SAME Board::changeStatus($taskId, $newStatus) the
        buttons below already call, which delegates to
        TaskService::changeStatus() for all validation/RBAC/side effects.
        Livewire always re-renders $columns from the DB after the call, so a
        rejected drop naturally "snaps back" — the card was never actually
        moved, no separate revert logic needed.

        Fase 3 Batch 6 (re-skin warna SAJA): every x-data/x-on:*/draggable/
        dataTransfer key/wire:click below is UNTOUCHED from before this
        batch — only the class="..."/:class="..." VALUES changed (design
        tokens instead of raw Tailwind grays/reds), never the mechanism
        that sets them.
    --}}
    @php
        $columnMeta = [
            'todo' => ['label' => 'Todo', 'dot' => 'bg-muted'],
            'in_progress' => ['label' => 'In Progress', 'dot' => 'bg-accent'],
            'in_review' => ['label' => 'In Review', 'dot' => 'bg-warning'],
            'done' => ['label' => 'Done', 'dot' => 'bg-success'],
        ];
        $priorityBadge = [
            'low' => 'bg-success-soft text-success',
            'medium' => 'bg-warning-soft text-warning',
            'high' => 'bg-danger-soft text-danger',
        ];
    @endphp

    <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        @foreach ($columnMeta as $status => $meta)
            <div
                x-data="{ dragOver: false }"
                x-on:dragover.prevent="dragOver = true"
                x-on:dragleave="dragOver = false"
                x-on:drop.prevent="
                    dragOver = false;
                    const taskId = $event.dataTransfer.getData('text/task-id');
                    const fromStatus = $event.dataTransfer.getData('text/from-status');
                    if (taskId && fromStatus !== '{{ $status }}') {
                        $wire.changeStatus(taskId, '{{ $status }}');
                    }
                "
                class="rounded-card bg-surface/60 p-3 shadow-warm-xs transition-all duration-150"
                :class="dragOver ? 'border-2 border-accent bg-accent-soft/40 shadow-warm-md' : 'border border-muted/20'"
            >
                <h2 class="font-mono flex items-center gap-1.5 text-xs font-medium uppercase text-muted">
                    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $meta['dot'] }}"></span>
                    {{ $meta['label'] }} ({{ $columns[$status]->count() }})
                </h2>

                <div class="mt-3 space-y-3">
                    @foreach ($columns[$status] as $task)
                        <div
                            draggable="true"
                            x-data="{ dragging: false }"
                            x-on:dragstart="
                                dragging = true;
                                $event.dataTransfer.effectAllowed = 'move';
                                $event.dataTransfer.setData('text/task-id', '{{ $task->id }}');
                                $event.dataTransfer.setData('text/from-status', '{{ $status }}');
                            "
                            x-on:dragend="dragging = false"
                            class="cursor-grab rounded-card border bg-white p-3 shadow-warm-xs transition-all duration-150 active:cursor-grabbing hover:shadow-warm-md {{ $task->deadline->isPast() && $task->status !== 'done' ? 'border-danger/40' : 'border-muted/20' }}"
                            :class="dragging ? 'opacity-40' : 'opacity-100'"
                        >
                            {{-- Fase 7 Batch 1b: dulu <a href> navigasi ke halaman penuh,
                                 sekarang buka panel slide-over di tempat. Ini SATU-SATUNYA
                                 baris yang diubah di dalam kartu draggable — atribut
                                 draggable/x-data/x-on:drag* di <div> pembungkusnya
                                 (baris di atas) tidak disentuh, drag tetap dimulai dari
                                 elemen itu terlepas dari apa yang terjadi saat elemen
                                 judul ini di-klik (bukan di-drag). --}}
                            <button type="button" wire:click="openTask('{{ $task->id }}')" class="text-left font-medium text-ink hover:text-accent">{{ $task->title }}</button>
                            <p class="mt-1 text-xs text-muted">{{ $task->milestone->title }}</p>
                            <div class="mt-1 flex items-center gap-2 text-xs">
                                <span class="rounded-full px-2 py-0.5 font-mono uppercase {{ $priorityBadge[$task->priority] ?? 'bg-muted/15 text-muted' }}">{{ $task->priority }}</span>
                                <span class="font-mono text-muted">{{ $task->deadline->format('d M') }}</span>
                                @if ($task->deadline->isPast() && $task->status !== 'done')
                                    <span class="rounded-full bg-danger-soft px-2 py-0.5 font-mono text-danger">OVERDUE</span>
                                @endif
                            </div>
                            @if ($task->assignments->isNotEmpty())
                                <p class="mt-1 text-xs text-muted">{{ $task->assignments->pluck('user.name')->join(', ') }}</p>
                            @endif

                            <div class="mt-2 flex flex-wrap gap-1">
                                @if ($status === 'todo')
                                    <button wire:click="changeStatus('{{ $task->id }}', 'in_progress')" class="rounded-control border border-muted/40 px-2 py-1 text-xs text-ink hover:border-accent hover:text-accent">Mulai Kerjakan</button>
                                @elseif ($status === 'in_progress')
                                    <button wire:click="changeStatus('{{ $task->id }}', 'in_review')" class="rounded-control border border-muted/40 px-2 py-1 text-xs text-ink hover:border-accent hover:text-accent">Submit Review</button>
                                @elseif ($status === 'in_review' && auth()->user()->role === 'admin')
                                    <button wire:click="changeStatus('{{ $task->id }}', 'done')" class="rounded-control border border-muted/40 px-2 py-1 text-xs text-ink hover:border-accent hover:text-accent">Lolos</button>
                                    <button wire:click="changeStatus('{{ $task->id }}', 'in_progress')" class="rounded-control border border-muted/40 px-2 py-1 text-xs text-ink hover:border-accent hover:text-accent">Perlu Revisi</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    {{--
        Fase 7 Batch 1b: panel slide-over Detail Task. Backdrop + panel
        SELALU ada di DOM (Alpine x-show/x-transition mengendalikan
        tampil/sembunyi, transisi translate-x dari kanan) — pola sama
        dengan drawer mobile sidebar riwayat WEBI Chat
        (resources/views/livewire/eksplorasi/webi/chat.blade.php,
        max-lg:translate-x-0 vs max-lg:-translate-x-full), cuma di sini
        aktif di SEMUA breakpoint (bukan cuma mobile) karena task memang
        dibuka-tutup berulang kali dalam satu sesi kerja Kanban, bukan
        kejadian sekali per halaman seperti drawer TOC/riwayat itu.
        Konten di dalamnya (`<livewire:eksekusi.tasks.show>`) adalah
        KOMPONEN YANG SAMA PERSIS dengan Tasks\Show yang dulu jadi halaman
        penuh — logic changeStatus/komentar/attachment/dst TIDAK dibangun
        ulang, cuma dipindah wadahnya jadi nested. `wire:key` diikat ke
        task_id supaya Livewire benar-benar me-remount komponen (bukan
        cuma re-render dengan prop lama) tiap kali task BERBEDA dibuka.
    --}}
    <div
        x-show="panelOpen"
        x-cloak
        x-transition:enter="transition-opacity duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="panelOpen = null"
        class="fixed inset-0 z-40 bg-ink/40"
    ></div>

    <div
        x-show="panelOpen"
        x-cloak
        x-transition:enter="transition-transform duration-200 ease-out"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition-transform duration-150 ease-in"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-xl flex-col overflow-y-auto border-l border-muted/20 bg-white shadow-warm-lg"
    >
        <div class="flex items-center justify-between border-b border-muted/20 p-4">
            <h2 class="font-display text-lg font-bold text-ink">Detail Task</h2>
            <button type="button" @click="panelOpen = null" aria-label="Tutup panel" class="rounded-control p-1 text-muted hover:bg-surface-alt hover:text-ink">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="M18 6 6 18" /><path d="m6 6 12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 p-4">
            @if ($openTask)
                <livewire:eksekusi.tasks.show :task="$openTask" :key="'task-panel-'.$openTask->id" />
            @endif
        </div>
    </div>
</div>

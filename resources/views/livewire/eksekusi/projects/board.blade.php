<div>
    <div class="flex items-center justify-between">
        <h1 class="font-display text-2xl font-bold text-ink">Kanban Board &mdash; {{ $project->title }}</h1>
        <a href="{{ url('/eksekusi/projects/'.$project->id.'/tasks/create') }}" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">
            + Buat Task
        </a>
    </div>

    @error('status') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

    <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        @foreach (['todo' => 'Todo', 'in_progress' => 'In Progress', 'in_review' => 'In Review', 'done' => 'Done'] as $status => $label)
            <div class="rounded-xl border border-muted/25 p-3">
                <h2 class="font-mono text-xs font-medium uppercase text-muted">{{ $label }} ({{ $columns[$status]->count() }})</h2>

                <div class="mt-3 space-y-3">
                    @foreach ($columns[$status] as $task)
                        <div class="rounded-lg border border-muted/25 bg-white p-3 {{ $task->deadline->isPast() && $task->status !== 'done' ? 'border-red-300' : '' }}">
                            <a href="{{ url('/eksekusi/tasks/'.$task->id) }}" class="font-medium text-ink hover:text-accent">{{ $task->title }}</a>
                            <p class="mt-1 text-xs text-muted">{{ $task->milestone->title }}</p>
                            <div class="mt-1 flex items-center gap-2 text-xs">
                                <span class="rounded-full border border-muted/40 px-2 py-0.5 font-mono uppercase text-muted">{{ $task->priority }}</span>
                                <span class="font-mono text-muted">{{ $task->deadline->format('d M') }}</span>
                                @if ($task->deadline->isPast() && $task->status !== 'done')
                                    <span class="rounded-full bg-red-100 px-2 py-0.5 font-mono text-red-700">OVERDUE</span>
                                @endif
                            </div>
                            @if ($task->assignments->isNotEmpty())
                                <p class="mt-1 text-xs text-muted">{{ $task->assignments->pluck('user.name')->join(', ') }}</p>
                            @endif

                            <div class="mt-2 flex flex-wrap gap-1">
                                @if ($status === 'todo')
                                    <button wire:click="changeStatus('{{ $task->id }}', 'in_progress')" class="rounded-lg border border-muted/40 px-2 py-1 text-xs text-ink hover:border-ink">Mulai Kerjakan</button>
                                @elseif ($status === 'in_progress')
                                    <button wire:click="changeStatus('{{ $task->id }}', 'in_review')" class="rounded-lg border border-muted/40 px-2 py-1 text-xs text-ink hover:border-ink">Submit Review</button>
                                @elseif ($status === 'in_review' && auth()->user()->role === 'admin')
                                    <button wire:click="changeStatus('{{ $task->id }}', 'done')" class="rounded-lg border border-muted/40 px-2 py-1 text-xs text-ink hover:border-ink">Lolos</button>
                                    <button wire:click="changeStatus('{{ $task->id }}', 'in_progress')" class="rounded-lg border border-muted/40 px-2 py-1 text-xs text-ink hover:border-ink">Perlu Revisi</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

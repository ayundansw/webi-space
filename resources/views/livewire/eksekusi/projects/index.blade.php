<div>
    <div class="flex items-center justify-between">
        <h1 class="font-display text-2xl font-bold text-ink">Proyek</h1>
        @if (auth()->user()->role === 'admin')
            <a href="{{ url('/eksekusi/projects/create') }}" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">
                + Buat Proyek Langsung
            </a>
        @endif
    </div>

    <div class="mt-6 divide-y divide-muted/25 border border-muted/25 rounded-xl">
        @forelse ($projects as $project)
            <a href="{{ url('/eksekusi/projects/'.$project->id) }}" class="block p-4 hover:bg-accent-soft/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-ink">{{ $project->title }}</p>
                        <p class="mt-1 text-sm text-muted">{{ ucfirst($project->project_type) }} &middot; {{ $project->progressPercentage() }}% task selesai</p>
                    </div>
                    <span class="rounded-lg border border-muted/40 px-2 py-1 text-xs font-mono uppercase text-ink">{{ $project->status }}</span>
                </div>
            </a>
        @empty
            <p class="p-4 text-sm text-muted">Belum ada proyek.</p>
        @endforelse
    </div>
</div>

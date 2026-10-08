@php
    // Icons matching the outline stroke style already used in the sidebar
    // (viewBox 24, stroke-width 1.75, currentColor) — no icon package.
    $statusIcons = [
        'planning' => '<path d="M9 4h6v3H9Z" /><path d="M7 5H6a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-1" />',
        'active' => '<path d="M8 5v14l11-7Z" />',
        'on_hold' => '<rect x="7" y="5" width="3.5" height="14" rx="1" /><rect x="13.5" y="5" width="3.5" height="14" rx="1" />',
        'completed' => '<path d="m5 13 4 4 10-10" />',
        'archived' => '<path d="M4 7h16v3H4Z" /><path d="M5 10v10h14V10" /><path d="M10 14h4" />',
    ];

    $statusColors = [
        'planning' => 'text-muted',
        'active' => 'text-accent',
        'on_hold' => 'text-amber-600',
        'completed' => 'text-green-600',
        'archived' => 'text-muted',
    ];
@endphp

@if (auth()->user()->role === 'admin')
    @push('breadcrumb-actions')
        <a href="{{ url('/eksekusi/projects/create') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-control bg-ink px-4 py-2 text-sm font-medium text-white shadow-warm-xs hover:bg-ink/90">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                <path d="M12 5v14" /><path d="M5 12h14" />
            </svg>
            Buat Proyek Langsung
        </a>
    @endpush
@endif

<div>
    <h1 class="font-display text-2xl font-bold text-ink">Proyek</h1>

    {{-- Task 2 (Tahap B, Isi Proyek): ringkasan sebelum daftar -- token
         card yang sama dipakai Dashboard (<x-stat-card>). --}}
    <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
        <x-stat-card label="Total Proyek" :value="$summary['total']" />
        <x-stat-card label="Planning" :value="$summary['by_status']['planning']" />
        <x-stat-card label="Active" :value="$summary['by_status']['active']" />
        <x-stat-card label="On Hold" :value="$summary['by_status']['on_hold']" />
        <x-stat-card label="Completed" :value="$summary['by_status']['completed']" />
        <x-stat-card label="Archived" :value="$summary['by_status']['archived']" />
        <x-stat-card label="Rata-rata Progres" :value="$summary['average_progress']" suffix="%" />
    </div>

    <div class="mt-6 divide-y divide-muted/25 border border-muted/25 rounded-xl">
        @forelse ($projects as $project)
            <a href="{{ url('/eksekusi/projects/'.$project->id) }}" class="block p-4 hover:bg-accent-soft/20">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-ink">{{ $project->title }}</p>
                        <p class="mt-1 text-sm text-muted">{{ ucfirst($project->project_type) }} &middot; {{ $project->progressPercentage() }}% task selesai</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-muted/40 px-2 py-1 text-xs font-mono uppercase text-ink">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3 shrink-0 {{ $statusColors[$project->status] ?? 'text-muted' }}">
                            {!! $statusIcons[$project->status] ?? '' !!}
                        </svg>
                        {{ $project->status }}
                    </span>
                </div>
            </a>
        @empty
            <p class="p-4 text-sm text-muted">Belum ada proyek.</p>
        @endforelse
    </div>
</div>

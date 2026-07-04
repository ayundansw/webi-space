<x-layouts.app title="Dashboard Eksekusi">
    <h1 class="font-display text-2xl font-bold text-ink">Dashboard Eksekusi</h1>
    <p class="mt-2 text-sm text-muted">Mulai dari salah satu menu di bawah.</p>

    <div class="mt-6 flex gap-3">
        <a href="{{ url('/eksekusi/ideas') }}" class="rounded-lg border border-muted/40 px-4 py-2 text-sm text-ink hover:border-ink">Project Ideas</a>
        <a href="{{ url('/eksekusi/projects') }}" class="rounded-lg border border-muted/40 px-4 py-2 text-sm text-ink hover:border-ink">Proyek Saya</a>
    </div>
</x-layouts.app>

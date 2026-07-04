<div class="max-w-2xl">
    <a href="{{ url('/eksplorasi/kurikulum') }}" class="text-sm text-muted hover:text-ink">&larr; Kembali ke Peta Kurikulum</a>

    @if ($locked)
        <div class="mt-6 rounded-xl border border-muted/25 p-8 text-center">
            <p class="text-lg font-medium text-ink">Unit ini belum bisa dibuka</p>
            <p class="mt-2 text-sm text-muted">Selesaikan dulu unit sebelumnya ya, baru unit ini kebuka. Santai, tidak perlu buru-buru.</p>
        </div>
    @else
        <div class="mt-4">
            <h1 class="font-display text-2xl font-bold text-ink">{{ $unit->title }}</h1>
            <p class="font-mono mt-1 text-xs text-muted">{{ $unit->estimated_minutes }} menit &middot; {{ $unit->point_value }} poin</p>
        </div>

        <div class="mt-6 space-y-4 text-sm leading-relaxed text-ink">
            @foreach (explode("\n\n", $unit->content) as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>

        <div class="mt-8 border-t border-muted/25 pt-6">
            <livewire:eksplorasi.unit-evaluation :unit="$unit" />
        </div>
    @endif
</div>

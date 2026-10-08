{{--
    Extracted so both member Show (eksplorasi/praktik/show.blade.php) and
    the reviewer's review page (eksekusi/praktik/review.blade.php) render
    the challenge's track map identically -- reuses <x-content-blocks>,
    same as member-facing Unit materi. Expects `$steps` in scope (inherited
    automatically via @include).
--}}
<div class="mt-6">
    <h2 class="font-display text-lg font-bold text-ink">Track Map</h2>
    <div class="mt-3 space-y-4">
        @forelse ($steps as $step)
            <div class="relative overflow-hidden rounded-xl border border-muted/20 bg-white p-5 shadow-warm-xs">
                <div class="card-pixel-accent-top" aria-hidden="true"></div>
                <div class="card-pixel-accent-bottom" aria-hidden="true"></div>

                <p class="font-mono text-xs text-caption">Langkah {{ $step->order_number }}</p>
                <h3 class="font-display mt-1 text-base font-semibold text-heading">{{ $step->title }}</h3>

                <div class="mt-3 space-y-3 text-sm leading-relaxed text-body">
                    @if ($step->contentBlocks->isNotEmpty())
                        <x-content-blocks :blocks="$step->contentBlocks" />
                    @else
                        <p class="text-muted">Belum ada instruksi untuk langkah ini.</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-muted/40 p-6 text-center text-sm text-muted">
                Belum ada track map untuk challenge ini.
            </div>
        @endforelse
    </div>
</div>

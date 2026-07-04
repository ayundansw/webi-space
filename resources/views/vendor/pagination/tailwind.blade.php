{{-- Overrides Laravel's default Tailwind pagination view (docs/design-tokens.md
     border/radius/warna tokens) — the framework default uses generic
     gray/blue-300 focus-ring styling that doesn't match this app's palette. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-4">
        <div>
            @if ($paginator->onFirstPage())
                <span class="rounded-lg border border-muted/25 px-3 py-1.5 text-sm text-muted">&larr; Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-lg border border-muted/40 px-3 py-1.5 text-sm text-ink hover:border-accent hover:text-accent">&larr; Sebelumnya</a>
            @endif
        </div>

        <div class="hidden items-center gap-1 sm:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 font-mono text-xs text-muted">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="rounded-lg bg-accent-soft px-3 py-1.5 font-mono text-xs font-medium text-ink">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="rounded-lg px-3 py-1.5 font-mono text-xs text-muted hover:bg-accent-soft/20 hover:text-ink">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        <div>
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-lg border border-muted/40 px-3 py-1.5 text-sm text-ink hover:border-accent hover:text-accent">Berikutnya &rarr;</a>
            @else
                <span class="rounded-lg border border-muted/25 px-3 py-1.5 text-sm text-muted">Berikutnya &rarr;</span>
            @endif
        </div>
    </nav>
@endif

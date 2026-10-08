@push('breadcrumb-actions')
    <a href="{{ $backUrl }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-control border border-muted/40 px-4 py-2 text-sm font-medium text-ink shadow-warm-xs hover:border-ink hover:bg-surface-alt">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
        </svg>
        Kembali ke Editor
    </a>
@endpush

<div>
    <div class="mb-4">
        <h1 class="font-display text-xl font-bold text-ink">Preview: {{ $title }}</h1>
        <p class="mt-1 text-sm text-muted">{{ $subtitle }}</p>
    </div>

    <div class="relative overflow-hidden rounded-2xl border border-muted/20 bg-white p-6 shadow-warm-md sm:p-8">
        <h2 class="font-display text-2xl font-bold text-heading">{{ $title }}</h2>
        @if ($metaLine)
            <p class="font-mono mt-1 text-xs text-caption">{{ $metaLine }}</p>
        @endif

        <div class="mt-6 space-y-4 text-sm leading-relaxed text-body">
            @if ($blocks->isNotEmpty())
                <x-content-blocks :blocks="$blocks" />
            @else
                <p class="text-muted">Belum ada blok konten di sini.</p>
            @endif
        </div>
    </div>
</div>

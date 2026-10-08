{{--
    Diekstrak dari index.blade.php supaya markup kartu referensi tidak
    diduplikasi antara loop per-modul dan card "General" (module_id null).
--}}
<a href="{{ $resource->url }}" target="_blank" rel="noopener" class="block rounded-lg border border-muted/20 bg-white p-4 shadow-warm-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-warm-md">
    <div class="flex items-start justify-between gap-2">
        <p class="text-sm font-medium text-ink">{{ $resource->title }}</p>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-accent">
            <path d="M14 4h6v6" /><path d="M20 4 10 14" /><path d="M18 13v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h6" />
        </svg>
    </div>

    @if ($resource->description)
        <p class="mt-1 text-xs text-caption">{{ Str::limit($resource->description, 90) }}</p>
    @endif

    <div class="mt-2 flex items-center justify-between gap-2">
        <span class="font-mono text-xs text-caption">{{ $resource->source_name }}</span>
        <span class="rounded-full px-2 py-0.5 font-mono text-[10px] font-medium uppercase tracking-wide {{ $resource->created_by ? 'bg-accent-soft text-ink' : 'bg-muted/15 text-muted' }}">
            {{ $resource->created_by ? 'Dari Anggota' : 'Dari Admin' }}
        </span>
    </div>
</a>

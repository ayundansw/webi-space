@php
    $url = $data['url'] ?? '';
    $embedUrl = null;

    // Provider is detected from the URL and the embed src is built by us
    // (not the raw url) — see docs/v_2.0/archive/sumber-konsolidasi/content-blocks-spec.md, "Video:
    // deteksi provider dari url" — so an unrecognized/arbitrary url can
    // never become an iframe src.
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]{6,})/', $url, $m)) {
        $embedUrl = 'https://www.youtube.com/embed/'.$m[1];
    } elseif (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
        $embedUrl = 'https://player.vimeo.com/video/'.$m[1];
    }
@endphp

@if ($embedUrl)
    <div class="aspect-video overflow-hidden rounded-xl border border-muted/25">
        <iframe src="{{ $embedUrl }}" class="h-full w-full" loading="lazy" allowfullscreen title="{{ $data['caption'] ?? 'Video' }}"></iframe>
    </div>
@elseif ($url)
    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="inline-block rounded-lg border border-muted/40 px-4 py-2 text-sm text-ink hover:border-accent">
        Tonton video &rarr;
    </a>
@endif

@if (! empty($data['caption']))
    <p class="mt-2 text-xs text-muted">{{ $data['caption'] }}</p>
@endif

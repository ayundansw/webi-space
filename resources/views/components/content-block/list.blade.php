@php
    $ordered = ($data['style'] ?? 'unordered') === 'ordered';
    $tag = $ordered ? 'ol' : 'ul';
    $listClass = $ordered ? 'list-decimal' : 'list-disc';
@endphp

<{{ $tag }} class="{{ $listClass }} space-y-1 pl-5 text-sm text-ink">
    @foreach ($data['items'] ?? [] as $item)
        <li class="[&_p]:inline [&_p]:m-0">{!! \App\Services\Content\SafeMarkdown::toHtml($item) !!}</li>
    @endforeach
</{{ $tag }}>

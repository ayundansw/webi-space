@props(['blocks'])

{{--
    Generic renderer (docs/v_2.0/archive/sumber-konsolidasi/content-blocks-spec.md) — takes any ordered
    collection of ContentBlock models, regardless of which `blockable` they
    belong to (Unit now, ChallengeStep later per the Praktik track map
    rancangan), and renders each by `type`. No unit-specific logic here.
--}}
<div class="space-y-6">
    @foreach ($blocks as $block)
        @php $data = $block->content ?? []; @endphp

        @switch($block->type)
            @case('heading')
                @include('components.content-block.heading', ['data' => $data])
                @break
            @case('text')
                @include('components.content-block.text', ['data' => $data])
                @break
            @case('image')
                @include('components.content-block.image', ['data' => $data])
                @break
            @case('callout')
                @include('components.content-block.callout', ['data' => $data])
                @break
            @case('code')
                @include('components.content-block.code', ['data' => $data])
                @break
            @case('video')
                @include('components.content-block.video', ['data' => $data])
                @break
            @case('list')
                @include('components.content-block.list', ['data' => $data])
                @break
            @case('table')
                @include('components.content-block.table', ['data' => $data])
                @break
            @case('custom_html')
                @include('components.content-block.custom-html', ['data' => $data])
                @break
        @endswitch
    @endforeach
</div>

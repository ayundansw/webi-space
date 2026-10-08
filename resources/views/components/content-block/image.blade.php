<figure>
    <img src="{{ $data['url'] ?? '' }}" alt="{{ $data['alt'] ?? '' }}" loading="lazy" class="w-full rounded-xl border border-muted/25">
    @if (! empty($data['caption']))
        <figcaption class="mt-2 text-center text-xs text-muted">{{ $data['caption'] }}</figcaption>
    @endif
</figure>

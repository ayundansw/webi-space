<div class="text-sm leading-relaxed text-ink">
    {!! \App\Services\Content\SafeMarkdown::toHtml($data['markdown'] ?? '') !!}
</div>

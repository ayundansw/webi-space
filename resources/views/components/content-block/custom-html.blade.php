{{--
    custom_html is sanitized on every render (docs/v_2.0/archive/sumber-konsolidasi/content-blocks-spec.md
    §"Custom HTML") — this deliberately tightens
    Rancangan_Arsitektur_Konten_Dinamis_v2.md §5, which originally allowed
    raw/unsanitized HTML for this escape hatch.
--}}
<div>
    {!! \App\Services\Content\HtmlSanitizer::sanitize($data['html'] ?? '') !!}
</div>

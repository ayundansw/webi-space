<?php

namespace App\Services\Content;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allowlist-based sanitizer for the `custom_html` content block (the escape
 * hatch in docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Arsitektur_Konten_Dinamis_v2.md §5). See
 * docs/v_2.0/archive/sumber-konsolidasi/content-blocks-spec.md for the full rationale, including why
 * this is a small custom sanitizer instead of a third-party library.
 *
 * Removes: dangerous tags entirely (script, iframe, forms, etc.), event
 * handler attributes (on*), and javascript:/vbscript:/non-image data: URLs
 * in href/src/action/formaction. Everything else (div, span, class, table
 * markup, presentational attributes) passes through untouched.
 */
class HtmlSanitizer
{
    private const DISALLOWED_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'form',
        'input', 'button', 'textarea', 'select', 'link', 'meta', 'base',
    ];

    private const URL_ATTRIBUTES = ['href', 'src', 'action', 'formaction'];

    public static function sanitize(string $html): string
    {
        return (new self)->clean($html);
    }

    private function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        // The <?xml encoding> prolog is the standard trick to make
        // DOMDocument::loadHTML respect UTF-8 instead of mangling multibyte
        // characters (it otherwise assumes ISO-8859-1).
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="__sanitizer_root__">'.$html.'</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $root = $dom->getElementById('__sanitizer_root__');

        if (! $root) {
            return '';
        }

        $this->cleanChildren($root);

        $innerHtml = '';
        foreach ($root->childNodes as $child) {
            $innerHtml .= $dom->saveHTML($child);
        }

        return trim($innerHtml);
    }

    private function cleanChildren(DOMNode $node): void
    {
        $toRemove = [];

        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            if (in_array(strtolower($child->tagName), self::DISALLOWED_TAGS, true)) {
                $toRemove[] = $child;

                continue;
            }

            $this->cleanAttributes($child);
            $this->cleanChildren($child);
        }

        foreach ($toRemove as $child) {
            $node->removeChild($child);
        }
    }

    private function cleanAttributes(DOMElement $element): void
    {
        $toRemove = [];

        foreach (iterator_to_array($element->attributes) as $attr) {
            $name = strtolower($attr->name);

            $isEventHandler = str_starts_with($name, 'on');
            $isDangerousUrl = in_array($name, self::URL_ATTRIBUTES, true) && $this->isDangerousUrl($attr->value);

            if ($isEventHandler || $isDangerousUrl) {
                $toRemove[] = $attr->name;
            }
        }

        foreach ($toRemove as $name) {
            $element->removeAttribute($name);
        }
    }

    private function isDangerousUrl(string $value): bool
    {
        $value = trim($value);

        if (preg_match('/^\s*(javascript|vbscript)\s*:/i', $value)) {
            return true;
        }

        if (preg_match('/^\s*data\s*:/i', $value) && ! preg_match('/^\s*data:image\//i', $value)) {
            return true;
        }

        return false;
    }
}

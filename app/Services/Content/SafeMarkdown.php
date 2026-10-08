<?php

namespace App\Services\Content;

use Illuminate\Support\Str;

/**
 * Same safe CommonMark configuration as App\Services\Webi\MessageRenderer::toSafeHtml()
 * — one consistent rule for "markdown-sourced text gets rendered safely",
 * regardless of whether it came from a WEBI reply or an admin-authored
 * content block. See docs/v_2.0/archive/sumber-konsolidasi/content-blocks-spec.md.
 */
class SafeMarkdown
{
    public static function toHtml(string $markdown): string
    {
        return Str::markdown($markdown, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
    }
}

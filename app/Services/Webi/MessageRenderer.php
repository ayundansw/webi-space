<?php

namespace App\Services\Webi;

use Illuminate\Support\Str;

/**
 * Renders a WEBI message's stored text for display (chat bubbles, admin log
 * viewer) and for text-to-speech.
 *
 * Fixed 2026-07-04: markdown symbols (**bold**, `code`, etc.) used to be shown
 * completely raw in the chat bubble AND read aloud symbol-by-symbol by TTS.
 * Visual rendering now goes through CommonMark; TTS gets a fully stripped
 * plain-text version instead (client-side counterpart in chat.blade.php's
 * webiVoice() also strips markdown before calling speechSynthesis, as a
 * second layer — the same defense-in-depth pattern as the guardrail).
 */
class MessageRenderer
{
    /**
     * `html_input: escape` neutralizes any literal HTML in the source text
     * (turns "<script>" into inert entities) before parsing markdown — the
     * reply text originates from an AI model, not a fully trusted source, so
     * this must never be left at CommonMark's default ('allow'), which would
     * let raw HTML in the model's output pass straight through and get
     * rendered unescaped in the browser.
     */
    public function toSafeHtml(string $content): string
    {
        return Str::markdown($content, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * Strips markdown syntax down to plain text, for TTS and for anywhere
     * else raw spoken/plain text is needed. Backend counterpart to the
     * client-side stripping in chat.blade.php's webiVoice().speak().
     */
    public function toPlainText(string $content): string
    {
        $text = $content;

        // fenced code blocks and inline code — drop the backticks, keep the text
        $text = preg_replace('/```[a-zA-Z0-9]*\n?([\s\S]*?)```/', '$1', $text);
        $text = preg_replace('/`([^`]*)`/', '$1', $text);

        // bold/italic (**text**, __text__, *text*, _text_)
        $text = preg_replace('/(\*\*|__)(.*?)\1/', '$2', $text);
        $text = preg_replace('/(\*|_)(.*?)\1/', '$2', $text);

        // headings, blockquote markers, list bullets at line start
        $text = preg_replace('/^#{1,6}\s+/m', '', $text);
        $text = preg_replace('/^>\s?/m', '', $text);
        $text = preg_replace('/^[\-\*\+]\s+/m', '', $text);
        $text = preg_replace('/^\d+\.\s+/m', '', $text);

        // links: [label](url) -> label
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text);

        return trim($text);
    }
}

<?php

namespace Tests\Feature\Webi;

use App\Services\Webi\MessageRenderer;
use Tests\TestCase;

class MessageRendererTest extends TestCase
{
    private function renderer(): MessageRenderer
    {
        return new MessageRenderer;
    }

    public function test_bold_and_italic_and_code_render_as_real_html(): void
    {
        $html = $this->renderer()->toSafeHtml('Ini **bold**, *italic*, dan `kode`.');

        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<em>italic</em>', $html);
        $this->assertStringContainsString('<code>kode</code>', $html);
    }

    public function test_raw_html_in_model_output_is_escaped_not_rendered(): void
    {
        $html = $this->renderer()->toSafeHtml('Contoh tag: <script>alert(1)</script> dan <img src=x onerror=alert(1)>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_plain_text_strips_all_markdown_symbols(): void
    {
        $plain = $this->renderer()->toPlainText(
            "# Judul\n\nIni **bold** dan *italic* serta `kode`.\n\n- Item satu\n- Item dua\n\n[link](https://x.com)"
        );

        $this->assertStringNotContainsString('*', $plain);
        $this->assertStringNotContainsString('`', $plain);
        $this->assertStringNotContainsString('#', $plain);
        $this->assertStringNotContainsString('- ', $plain);
        $this->assertStringNotContainsString('[', $plain);
        $this->assertStringContainsString('Judul', $plain);
        $this->assertStringContainsString('bold', $plain);
        $this->assertStringContainsString('Item satu', $plain);
        $this->assertStringContainsString('link', $plain);
    }
}

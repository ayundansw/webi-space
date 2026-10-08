<?php

namespace Tests\Feature\Content;

use App\Models\ContentBlock;
use App\Models\Module;
use App\Models\Unit;
use App\Services\Content\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 2.2.4a (Konten Dinamis, batch fondasi): schema + model + generic renderer,
 * verified with dummy data only — no production unit is touched or migrated
 * in this batch (docs/v_2.0/archive/sumber-konsolidasi/content-blocks-spec.md).
 */
class ContentBlockTest extends TestCase
{
    use RefreshDatabase;

    private function unit(): Unit
    {
        $module = Module::create([
            'order_number' => 1, 'title' => 'Modul Uji', 'description' => 'x', 'level_number' => 1,
        ]);

        return Unit::create([
            'module_id' => $module->id, 'order_number' => 1, 'title' => 'Unit Uji',
            'content' => 'Konten lama tidak disentuh.', 'estimated_minutes' => 10,
            'unit_type' => 'concept', 'point_value' => 5, 'evaluation_type' => 'none',
        ]);
    }

    public function test_content_blocks_are_returned_in_order_for_their_blockable(): void
    {
        $unit = $this->unit();

        // Deliberately inserted out of `order` sequence.
        $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Ketiga'], 'order' => 3]);
        $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Pertama'], 'order' => 1]);
        $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Kedua'], 'order' => 2]);

        $ordered = $unit->fresh()->contentBlocks->pluck('content.markdown')->all();

        $this->assertSame(['Pertama', 'Kedua', 'Ketiga'], $ordered);
    }

    public function test_untouched_by_this_batch_units_content_column_still_holds_its_own_plain_text(): void
    {
        $unit = $this->unit();
        $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Blok baru'], 'order' => 1]);

        // The old plain-text column is completely independent of the new
        // relation — additive, per the task's explicit constraint.
        $this->assertSame('Konten lama tidak disentuh.', $unit->fresh()->content);
    }

    public function test_content_blocks_are_polymorphic_and_use_the_registered_morph_map(): void
    {
        $unit = $this->unit();
        $block = $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'x'], 'order' => 1]);

        // 'unit' is already registered in AppServiceProvider's morph map for
        // Notification's context — content_blocks reuses that same map
        // rather than storing the fully-qualified class name.
        $this->assertSame('unit', $block->fresh()->blockable_type);
        $this->assertTrue($block->blockable->is($unit));
    }

    public function test_renderer_displays_heading_block_with_clamped_level(): void
    {
        $html = $this->renderBlocks([
            ['type' => 'heading', 'content' => ['level' => 1, 'text' => 'Judul Besar']],
            ['type' => 'heading', 'content' => ['level' => 99, 'text' => 'Level Tidak Wajar']],
        ]);

        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('Judul Besar', $html);
        // level 99 clamped down to the max allowed (3), never left as <h99>
        $this->assertStringContainsString('<h3', $html);
        $this->assertStringNotContainsString('<h99', $html);
    }

    public function test_renderer_displays_text_block_with_safe_markdown(): void
    {
        $html = $this->renderBlocks([
            ['type' => 'text', 'content' => ['markdown' => 'Ini **tebal** dan ini <script>alert(1)</script> percobaan.']],
        ]);

        $this->assertStringContainsString('<strong>tebal</strong>', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_renderer_displays_image_block(): void
    {
        $html = $this->renderBlocks([
            ['type' => 'image', 'content' => ['url' => 'https://example.com/gambar.png', 'alt' => 'Deskripsi gambar', 'caption' => 'Contoh caption']],
        ]);

        $this->assertStringContainsString('src="https://example.com/gambar.png"', $html);
        $this->assertStringContainsString('alt="Deskripsi gambar"', $html);
        $this->assertStringContainsString('Contoh caption', $html);
    }

    public function test_renderer_displays_callout_block_per_variant(): void
    {
        $html = $this->renderBlocks([
            ['type' => 'callout', 'content' => ['variant' => 'warning', 'title' => 'Perhatian', 'body' => 'Jangan lupa.']],
        ]);

        $this->assertStringContainsString('Perhatian', $html);
        $this->assertStringContainsString('Jangan lupa.', $html);
        $this->assertStringContainsString('bg-amber-50', $html);
    }

    public function test_renderer_displays_code_block_without_markdown_interpretation(): void
    {
        $html = $this->renderBlocks([
            ['type' => 'code', 'content' => ['language' => 'bash', 'code' => "mkdir **latihan**\ncd latihan"]],
        ]);

        $this->assertStringContainsString('bash', $html);
        // code content is escaped/literal, never parsed as markdown
        $this->assertStringContainsString('mkdir **latihan**', $html);
        $this->assertStringNotContainsString('<strong>latihan</strong>', $html);
    }

    public function test_renderer_embeds_recognized_video_provider_and_falls_back_for_unknown_url(): void
    {
        $html = $this->renderBlocks([
            ['type' => 'video', 'content' => ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'caption' => 'Video YouTube']],
            ['type' => 'video', 'content' => ['url' => 'https://not-a-real-provider.example/video', 'caption' => 'Video tidak dikenal']],
        ]);

        $this->assertStringContainsString('src="https://www.youtube.com/embed/dQw4w9WgXcQ"', $html);
        $this->assertStringContainsString('Tonton video', $html);
        $this->assertStringContainsString('href="https://not-a-real-provider.example/video"', $html);
    }

    public function test_renderer_displays_list_block_with_correct_tag_per_style(): void
    {
        $html = $this->renderBlocks([
            ['type' => 'list', 'content' => ['style' => 'ordered', 'items' => ['Langkah **satu**', 'Langkah dua']]],
        ]);

        $this->assertStringContainsString('<ol', $html);
        $this->assertStringContainsString('<strong>satu</strong>', $html);
    }

    public function test_renderer_displays_table_block(): void
    {
        $html = $this->renderBlocks([
            ['type' => 'table', 'content' => ['headers' => ['Perintah', 'Fungsi'], 'rows' => [['pwd', 'Lokasi folder']]]],
        ]);

        $this->assertStringContainsString('Perintah', $html);
        $this->assertStringContainsString('pwd', $html);
        $this->assertStringContainsString('Lokasi folder', $html);
    }

    public function test_renderer_sanitizes_custom_html_block_removing_script_and_event_handlers(): void
    {
        $html = $this->renderBlocks([
            ['type' => 'custom_html', 'content' => ['html' => '<div class="aman">Halo<script>alert(1)</script><img src=x onerror="alert(2)"></div>']],
        ]);

        $this->assertStringContainsString('class="aman"', $html);
        $this->assertStringContainsString('Halo', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    public function test_html_sanitizer_strips_dangerous_tags_and_attributes_directly(): void
    {
        $dirty = '<div>Teks aman'
            .'<script>alert("xss")</script>'
            .'<img src="x" onerror="alert(1)">'
            .'<a href="javascript:alert(2)">klik</a>'
            .'<iframe src="https://evil.example"></iframe>'
            .'</div>';

        $clean = HtmlSanitizer::sanitize($dirty);

        $this->assertStringContainsString('Teks aman', $clean);
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringNotContainsString('<iframe', $clean);
    }

    public function test_html_sanitizer_allows_safe_image_data_uri_but_blocks_other_data_schemes(): void
    {
        $safe = HtmlSanitizer::sanitize('<img src="data:image/png;base64,AAAA">');
        $unsafe = HtmlSanitizer::sanitize('<a href="data:text/html;base64,AAAA">link</a>');

        $this->assertStringContainsString('data:image/png', $safe);
        $this->assertStringNotContainsString('data:text/html', $unsafe);
    }

    private function renderBlocks(array $definitions): string
    {
        $blocks = collect($definitions)->map(fn (array $d) => new ContentBlock($d));

        return view('components.content-blocks', ['blocks' => $blocks])->render();
    }
}

<?php

namespace Tests\Feature\Admin\Curriculum;

use App\Livewire\Admin\Curriculum\Units\ContentEditor;
use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContentEditorTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function member(string $role): User
    {
        return User::create([
            'name' => 'Member', 'email' => $role.'@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => $role, 'membership_status' => 'active',
        ]);
    }

    private function unit(): Unit
    {
        $module = Module::create(['order_number' => 1, 'title' => 'Modul Uji', 'description' => 'D', 'level_number' => 1]);

        return Unit::create([
            'module_id' => $module->id, 'order_number' => 1, 'title' => 'Unit Uji',
            'content' => 'Konten lama.', 'estimated_minutes' => 15, 'unit_type' => 'concept',
            'point_value' => 10, 'evaluation_type' => 'none',
        ]);
    }

    public function test_non_admin_cannot_access_content_editor(): void
    {
        $unit = $this->unit();
        $exploration = $this->member('exploration_member');
        $execution = $this->member('execution_member');

        foreach ([$exploration, $execution] as $user) {
            $this->actingAs($user)->get("/admin/curriculum/units/{$unit->id}/content")->assertForbidden();
        }
    }

    public function test_admin_can_create_a_heading_block(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'heading')
            ->set('level', '2')
            ->set('text', 'Judul Bagian')
            ->call('saveBlock')
            ->assertHasNoErrors();

        $block = $unit->contentBlocks()->first();
        $this->assertSame('heading', $block->type);
        // assertEquals, not assertSame: MySQL's JSON column type re-serializes
        // object keys by (length, then lexicographic) on round-trip, not
        // insertion order — the DATA is correct, key order is just a MySQL
        // storage detail that doesn't affect access-by-key anywhere it's read.
        $this->assertEquals(['level' => 2, 'text' => 'Judul Bagian'], $block->content);
        $this->assertSame(1, $block->order);
    }

    public function test_heading_level_must_be_between_1_and_3(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'heading')
            ->set('level', '5')
            ->set('text', 'Judul')
            ->call('saveBlock')
            ->assertHasErrors(['level']);

        $this->assertSame(0, $unit->contentBlocks()->count());
    }

    public function test_admin_can_create_a_text_block(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'text')
            ->set('markdown', 'Ini **teks** biasa.')
            ->call('saveBlock')
            ->assertHasNoErrors();

        $block = $unit->contentBlocks()->first();
        $this->assertSame('text', $block->type);
        $this->assertEquals(['markdown' => 'Ini **teks** biasa.'], $block->content);
    }

    public function test_text_block_requires_markdown(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'text')
            ->set('markdown', '')
            ->call('saveBlock')
            ->assertHasErrors(['markdown']);
    }

    public function test_admin_can_create_a_callout_block(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'callout')
            ->set('variant', 'tip')
            ->set('title', 'Tips')
            ->set('body', 'Ini isi callout.')
            ->call('saveBlock')
            ->assertHasNoErrors();

        $block = $unit->contentBlocks()->first();
        $this->assertSame('callout', $block->type);
        $this->assertEquals(['variant' => 'tip', 'title' => 'Tips', 'body' => 'Ini isi callout.'], $block->content);
    }

    public function test_callout_variant_must_be_one_of_the_three_fixed_options(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'callout')
            ->set('variant', 'danger')
            ->set('body', 'Isi')
            ->call('saveBlock')
            ->assertHasErrors(['variant']);
    }

    public function test_admin_can_create_a_code_block(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'code')
            ->set('language', 'bash')
            ->set('code', 'echo hello')
            ->call('saveBlock')
            ->assertHasNoErrors();

        $block = $unit->contentBlocks()->first();
        $this->assertSame('code', $block->type);
        $this->assertEquals(['language' => 'bash', 'code' => 'echo hello'], $block->content);
    }

    public function test_code_block_requires_code_but_language_is_optional(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'code')
            ->set('code', 'pwd')
            ->call('saveBlock')
            ->assertHasNoErrors();

        $this->assertEquals(['language' => null, 'code' => 'pwd'], $unit->contentBlocks()->first()->content);
    }

    public function test_admin_can_create_an_image_block(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'image')
            ->set('url', 'https://example.test/gambar.png')
            ->set('alt', 'Contoh gambar')
            ->set('caption', 'Caption gambar')
            ->call('saveBlock')
            ->assertHasNoErrors();

        $block = $unit->contentBlocks()->first();
        $this->assertSame('image', $block->type);
        $this->assertEquals([
            'url' => 'https://example.test/gambar.png',
            'alt' => 'Contoh gambar',
            'caption' => 'Caption gambar',
        ], $block->content);
    }

    public function test_image_block_requires_a_valid_url(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'image')
            ->set('url', 'bukan-url')
            ->call('saveBlock')
            ->assertHasErrors(['url']);
    }

    public function test_selecting_an_unknown_type_does_not_open_a_form(): void
    {
        // All 9 real block types are functional since 2b — this only guards
        // against an invalid wire:click payload, not a "segera hadir" type.
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'bukan_tipe_valid')
            ->assertSet('formType', null);

        $this->assertSame(0, $unit->contentBlocks()->count());
    }

    public function test_new_blocks_get_auto_incrementing_order(): void
    {
        $unit = $this->unit();
        $component = Livewire::actingAs($this->admin())->test(ContentEditor::class, ['unit' => $unit]);

        $component->call('selectType', 'heading')->set('level', '1')->set('text', 'Pertama')->call('saveBlock');
        $component->call('selectType', 'heading')->set('level', '1')->set('text', 'Kedua')->call('saveBlock');

        $orders = $unit->contentBlocks()->pluck('order', 'order')->keys()->sort()->values()->all();
        $this->assertSame([1, 2], $orders);
    }

    public function test_admin_can_edit_each_of_the_five_block_types(): void
    {
        $admin = $this->admin();
        $unit = $this->unit();

        $heading = $unit->contentBlocks()->create(['type' => 'heading', 'content' => ['level' => 1, 'text' => 'Lama'], 'order' => 1]);
        $text = $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Lama'], 'order' => 2]);
        $callout = $unit->contentBlocks()->create(['type' => 'callout', 'content' => ['variant' => 'info', 'title' => null, 'body' => 'Lama'], 'order' => 3]);
        $code = $unit->contentBlocks()->create(['type' => 'code', 'content' => ['language' => null, 'code' => 'lama'], 'order' => 4]);
        $image = $unit->contentBlocks()->create(['type' => 'image', 'content' => ['url' => 'https://example.test/lama.png', 'alt' => null, 'caption' => null], 'order' => 5]);

        Livewire::actingAs($admin)
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('editBlock', $heading->id)->set('text', 'Baru')->call('saveBlock')
            ->assertHasNoErrors();
        $this->assertSame('Baru', $heading->fresh()->content['text']);

        Livewire::actingAs($admin)
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('editBlock', $text->id)->set('markdown', 'Baru')->call('saveBlock')
            ->assertHasNoErrors();
        $this->assertSame('Baru', $text->fresh()->content['markdown']);

        Livewire::actingAs($admin)
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('editBlock', $callout->id)->set('variant', 'warning')->call('saveBlock')
            ->assertHasNoErrors();
        $this->assertSame('warning', $callout->fresh()->content['variant']);

        Livewire::actingAs($admin)
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('editBlock', $code->id)->set('code', 'baru')->call('saveBlock')
            ->assertHasNoErrors();
        $this->assertSame('baru', $code->fresh()->content['code']);

        Livewire::actingAs($admin)
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('editBlock', $image->id)->set('url', 'https://example.test/baru.png')->call('saveBlock')
            ->assertHasNoErrors();
        $this->assertSame('https://example.test/baru.png', $image->fresh()->content['url']);

        // editing never changes `type` or `order`.
        $this->assertSame('heading', $heading->fresh()->type);
        $this->assertSame(1, $heading->fresh()->order);
    }

    public function test_admin_can_delete_a_block(): void
    {
        $unit = $this->unit();
        $block = $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'x'], 'order' => 1]);

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('deleteBlock', $block->id);

        $this->assertSame(0, $unit->contentBlocks()->count());
    }

    // --- Batch 2b: video, list, table, custom_html ---

    public function test_admin_can_create_a_video_block(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'video')
            ->set('url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
            ->set('caption', 'Contoh video')
            ->call('saveBlock')
            ->assertHasNoErrors();

        $block = $unit->contentBlocks()->first();
        $this->assertSame('video', $block->type);
        $this->assertEquals(['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'caption' => 'Contoh video'], $block->content);
    }

    public function test_video_block_requires_a_valid_url(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'video')
            ->set('url', 'bukan-url')
            ->call('saveBlock')
            ->assertHasErrors(['url']);
    }

    public function test_admin_can_create_a_list_block_with_multiple_items(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'list')
            ->set('style', 'ordered')
            ->set('items.0', 'Langkah pertama')
            ->call('addListItem')
            ->set('items.1', 'Langkah kedua')
            ->call('addListItem')
            ->set('items.2', 'Langkah ketiga')
            ->call('saveBlock')
            ->assertHasNoErrors();

        $block = $unit->contentBlocks()->first();
        $this->assertSame('list', $block->type);
        $this->assertEquals(['style' => 'ordered', 'items' => ['Langkah pertama', 'Langkah kedua', 'Langkah ketiga']], $block->content);
    }

    public function test_removing_a_list_item_re_indexes_the_rest(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'list')
            ->set('items.0', 'Pertama')
            ->call('addListItem')
            ->set('items.1', 'Kedua')
            ->call('addListItem')
            ->set('items.2', 'Ketiga')
            ->call('removeListItem', 1)
            ->assertSet('items', ['Pertama', 'Ketiga'])
            ->call('saveBlock')
            ->assertHasNoErrors();

        $this->assertEquals(['style' => 'unordered', 'items' => ['Pertama', 'Ketiga']], $unit->contentBlocks()->first()->content);
    }

    public function test_list_block_cannot_remove_its_last_remaining_item(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'list')
            ->set('items.0', 'Satu-satunya')
            ->call('removeListItem', 0)
            ->assertSet('items', ['Satu-satunya']);
    }

    public function test_list_block_requires_at_least_one_non_empty_item(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'list')
            ->set('items.0', '')
            ->call('saveBlock')
            ->assertHasErrors(['items.0']);
    }

    public function test_admin_can_create_a_table_block_with_headers(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'table')
            ->set('headers.0', 'Perintah')
            ->call('addTableColumn')
            ->set('headers.1', 'Fungsi')
            ->set('rows.0.0', 'pwd')
            ->set('rows.0.1', 'Menampilkan lokasi folder')
            ->call('addTableRow')
            ->set('rows.1.0', 'ls')
            ->set('rows.1.1', 'Menampilkan isi folder')
            ->call('saveBlock')
            ->assertHasNoErrors();

        $block = $unit->contentBlocks()->first();
        $this->assertSame('table', $block->type);
        $this->assertEquals([
            'headers' => ['Perintah', 'Fungsi'],
            'rows' => [['pwd', 'Menampilkan lokasi folder'], ['ls', 'Menampilkan isi folder']],
        ], $block->content);
    }

    public function test_table_block_with_all_blank_headers_is_stored_as_headerless(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'table')
            ->set('rows.0.0', 'Satu sel saja')
            ->call('saveBlock')
            ->assertHasNoErrors();

        $this->assertSame([], $unit->contentBlocks()->first()->content['headers']);
    }

    public function test_removing_a_table_column_removes_it_from_every_row(): void
    {
        $unit = $this->unit();

        $component = Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'table')
            ->set('headers.0', 'A')
            ->call('addTableColumn')
            ->set('headers.1', 'B')
            ->call('addTableColumn')
            ->set('headers.2', 'C')
            ->set('rows.0.0', '1a')
            ->set('rows.0.1', '1b')
            ->set('rows.0.2', '1c')
            ->call('removeTableColumn', 1)
            ->assertSet('headers', ['A', 'C']);

        $component->call('saveBlock')->assertHasNoErrors();

        $content = $unit->contentBlocks()->first()->content;
        $this->assertSame(['A', 'C'], $content['headers']);
        $this->assertSame(['1a', '1c'], $content['rows'][0]);
    }

    public function test_removing_a_table_row_leaves_others_intact(): void
    {
        $unit = $this->unit();

        $component = Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'table')
            ->set('rows.0.0', 'baris1')
            ->call('addTableRow')
            ->set('rows.1.0', 'baris2')
            ->call('addTableRow')
            ->set('rows.2.0', 'baris3')
            ->call('removeTableRow', 1)
            ->assertSet('rows', [['baris1'], ['baris3']]);

        $component->call('saveBlock')->assertHasNoErrors();
        $this->assertSame([['baris1'], ['baris3']], $unit->contentBlocks()->first()->content['rows']);
    }

    public function test_table_block_cannot_remove_its_last_remaining_row_or_column(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'table')
            ->set('rows.0.0', 'satu-satunya')
            ->call('removeTableRow', 0)
            ->call('removeTableColumn', 0)
            ->assertSet('rows', [['satu-satunya']]);
    }

    public function test_admin_can_create_a_custom_html_block(): void
    {
        $unit = $this->unit();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('selectType', 'custom_html')
            ->set('html', '<div class="border p-2">Halo <strong>dunia</strong></div>')
            ->call('saveBlock')
            ->assertHasNoErrors();

        $block = $unit->contentBlocks()->first();
        $this->assertSame('custom_html', $block->type);
        // Stored VERBATIM (raw, unsanitized) — sanitization happens at render
        // time (HtmlSanitizer, called by the content-block component), not
        // at save time. This is an intentional, existing decision (see
        // resources/views/components/content-block/custom-html.blade.php),
        // not something this batch changes.
        $this->assertSame('<div class="border p-2">Halo <strong>dunia</strong></div>', $block->content['html']);
    }

    // --- Batch 2b: reorder ---

    public function test_moving_a_block_up_and_saving_persists_the_new_order(): void
    {
        $unit = $this->unit();
        $first = $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Pertama'], 'order' => 1]);
        $second = $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Kedua'], 'order' => 2]);
        $third = $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Ketiga'], 'order' => 3]);

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->assertSet('orderDirty', false)
            ->call('moveBlockUp', 2) // move Ketiga up, swapping with Kedua
            ->assertSet('orderDirty', true)
            ->call('saveOrder')
            ->assertSet('orderDirty', false);

        $this->assertSame(1, $first->fresh()->order);
        $this->assertSame(2, $third->fresh()->order);
        $this->assertSame(3, $second->fresh()->order);
    }

    public function test_reordering_without_saving_does_not_persist_to_the_database(): void
    {
        $unit = $this->unit();
        $first = $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Pertama'], 'order' => 1]);
        $second = $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Kedua'], 'order' => 2]);

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('moveBlockUp', 1)
            ->assertSet('orderDirty', true);
        // deliberately never calls saveOrder() — simulates navigating away.

        $this->assertSame(1, $first->fresh()->order);
        $this->assertSame(2, $second->fresh()->order);
    }

    public function test_first_block_cannot_move_up_and_last_block_cannot_move_down(): void
    {
        $unit = $this->unit();
        $first = $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Pertama'], 'order' => 1]);
        $second = $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Kedua'], 'order' => 2]);

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('moveBlockUp', 0) // already first — no-op
            ->assertSet('orderDirty', false)
            ->call('moveBlockDown', 1) // already last — no-op
            ->assertSet('orderDirty', false);
    }

    public function test_multiple_moves_then_save_bubbles_a_block_all_the_way_to_the_front(): void
    {
        $unit = $this->unit();
        $blocks = [];
        for ($i = 1; $i <= 4; $i++) {
            $blocks[$i] = $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => "Blok {$i}"], 'order' => $i]);
        }

        // Bubble block 4 (index 3) all the way to the front: 1,2,3,4 -> 1,2,4,3 -> 1,4,2,3 -> 4,1,2,3
        $component = Livewire::actingAs($this->admin())->test(ContentEditor::class, ['unit' => $unit]);
        $component->call('moveBlockUp', 3)->call('moveBlockUp', 2)->call('moveBlockUp', 1);
        $component->call('saveOrder');

        $this->assertSame(1, $blocks[4]->fresh()->order);
        $this->assertSame(2, $blocks[1]->fresh()->order);
        $this->assertSame(3, $blocks[2]->fresh()->order);
        $this->assertSame(4, $blocks[3]->fresh()->order);
    }

    public function test_adding_a_new_block_resyncs_order_and_discards_any_pending_unsaved_reorder(): void
    {
        $unit = $this->unit();
        $first = $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Pertama'], 'order' => 1]);
        $second = $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Kedua'], 'order' => 2]);

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['unit' => $unit])
            ->call('moveBlockUp', 1) // pending reorder, unsaved
            ->call('selectType', 'text')
            ->set('markdown', 'Ketiga')
            ->call('saveBlock')
            ->assertSet('orderDirty', false);

        // original two blocks keep their DB order — the unsaved swap was discarded.
        $this->assertSame(1, $first->fresh()->order);
        $this->assertSame(2, $second->fresh()->order);
        $this->assertSame(3, $unit->contentBlocks()->where('content->markdown', 'Ketiga')->first()->order);
    }

    // --- Batch 2b: preview ---

    public function test_non_admin_cannot_access_preview(): void
    {
        $unit = $this->unit();
        $exploration = $this->member('exploration_member');

        $this->actingAs($exploration)->get("/admin/curriculum/units/{$unit->id}/content/preview")->assertForbidden();
    }

    public function test_admin_preview_renders_the_units_blocks(): void
    {
        $unit = $this->unit();
        $unit->contentBlocks()->create(['type' => 'heading', 'content' => ['level' => 2, 'text' => 'Judul Preview'], 'order' => 1]);
        $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Isi paragraf preview.'], 'order' => 2]);

        $this->actingAs($this->admin())
            ->get("/admin/curriculum/units/{$unit->id}/content/preview")
            ->assertOk()
            ->assertSee('Judul Preview')
            ->assertSee('Isi paragraf preview.');
    }
}

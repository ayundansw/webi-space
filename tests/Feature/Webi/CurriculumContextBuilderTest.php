<?php

namespace Tests\Feature\Webi;

use App\Livewire\Eksplorasi\Webi\Chat;
use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserExplorationProgress;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 5 recon fix (2026-07-12): CurriculumContextBuilder used to read
 * Unit.content exclusively, even for units already migrated to
 * content_blocks (Fase 4 Editor Blok Konten) — meaning WEBI could answer
 * from stale/empty legacy text while the member-facing page correctly
 * showed the new block content. These tests drive the real Chat component
 * end-to-end (same pattern as PersonalizationTest) and inspect the captured
 * HTTP request's system prompt, not the service class in isolation — proves
 * the fix all the way through, not just that the extraction method works.
 */
class CurriculumContextBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExplorationSampleSeeder::class);

        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'jawaban']]]]],
        ], 200)]);
    }

    private function memberOnUnit(Unit $unit): User
    {
        $user = User::create([
            'name' => 'Member', 'email' => 'member@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member', 'membership_status' => 'active',
        ]);
        UserExplorationProgress::create([
            'user_id' => $user->id, 'current_level' => 1, 'level_name' => 'Pengenal',
            'total_points' => 0, 'current_unit_id' => $unit->id,
        ]);

        return $user;
    }

    private function capturedPrompt(): string
    {
        return collect(Http::recorded())->first()[0]->data()['systemInstruction']['parts'][0]['text'];
    }

    public function test_a_unit_migrated_to_content_blocks_is_read_from_blocks_not_the_stale_legacy_column(): void
    {
        $unit = Unit::first();
        $unit->update(['content' => 'TEKS LAMA YANG SUDAH USANG, seharusnya tidak muncul.']);
        $unit->contentBlocks()->create(['type' => 'heading', 'content' => ['level' => 1, 'text' => 'Judul Blok Baru'], 'order' => 1]);
        $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Paragraf baru dari Editor Blok Konten.'], 'order' => 2]);

        $user = $this->memberOnUnit($unit);

        Livewire::actingAs($user)->test(Chat::class)->set('messageText', 'Halo')->call('sendMessage');

        $prompt = $this->capturedPrompt();
        $this->assertStringContainsString('Judul Blok Baru', $prompt);
        $this->assertStringContainsString('Paragraf baru dari Editor Blok Konten.', $prompt);
        $this->assertStringNotContainsString('TEKS LAMA YANG SUDAH USANG', $prompt);
    }

    public function test_a_unit_not_yet_migrated_still_reads_the_legacy_content_column(): void
    {
        // Backward compatibility: no content_blocks rows at all for this unit.
        $unit = Unit::first();
        $unit->update(['content' => 'Konten lama biasa, belum pernah disentuh Editor Blok Konten.']);

        $user = $this->memberOnUnit($unit);

        Livewire::actingAs($user)->test(Chat::class)->set('messageText', 'Halo')->call('sendMessage');

        $prompt = $this->capturedPrompt();
        $this->assertStringContainsString('Konten lama biasa, belum pernah disentuh Editor Blok Konten.', $prompt);
    }

    public function test_all_relevant_block_types_are_extracted_as_readable_text(): void
    {
        $unit = Unit::first();
        $unit->contentBlocks()->create(['type' => 'heading', 'content' => ['level' => 2, 'text' => 'Judul Bagian'], 'order' => 1]);
        $unit->contentBlocks()->create(['type' => 'callout', 'content' => ['variant' => 'tip', 'title' => 'Tips', 'body' => 'Isi callout penting.'], 'order' => 2]);
        $unit->contentBlocks()->create(['type' => 'code', 'content' => ['language' => 'bash', 'code' => 'echo halo'], 'order' => 3]);
        $unit->contentBlocks()->create(['type' => 'list', 'content' => ['style' => 'unordered', 'items' => ['Item pertama', 'Item kedua']], 'order' => 4]);
        $unit->contentBlocks()->create(['type' => 'table', 'content' => ['headers' => ['Kolom A', 'Kolom B'], 'rows' => [['1a', '1b']]], 'order' => 5]);
        $unit->contentBlocks()->create(['type' => 'custom_html', 'content' => ['html' => '<div>Teks <strong>HTML</strong> murni.</div>'], 'order' => 6]);
        $unit->contentBlocks()->create(['type' => 'image', 'content' => ['url' => 'https://example.test/x.png', 'alt' => 'Gambar tak relevan'], 'order' => 7]);

        $user = $this->memberOnUnit($unit);

        Livewire::actingAs($user)->test(Chat::class)->set('messageText', 'Halo')->call('sendMessage');

        $prompt = $this->capturedPrompt();
        $this->assertStringContainsString('Judul Bagian', $prompt);
        $this->assertStringContainsString('Tips: Isi callout penting.', $prompt);
        $this->assertStringContainsString('[bash] echo halo', $prompt);
        $this->assertStringContainsString('- Item pertama', $prompt);
        $this->assertStringContainsString('Kolom A | Kolom B', $prompt);
        $this->assertStringContainsString('Teks HTML murni.', $prompt);
        // image block has no meaningful text — its alt text must NOT leak in
        // as if it were prose (it was never asked to be extracted).
        $this->assertStringNotContainsString('Gambar tak relevan', $prompt);
    }

    public function test_sajikan_directive_is_still_stripped_when_it_appears_inside_a_content_block(): void
    {
        $unit = Unit::first();
        $unit->contentBlocks()->create([
            'type' => 'text',
            'content' => ['markdown' => 'Teks biasa. [SAJIKAN: elemen UI khusus] Lanjutan teks.'],
            'order' => 1,
        ]);

        $user = $this->memberOnUnit($unit);

        Livewire::actingAs($user)->test(Chat::class)->set('messageText', 'Halo')->call('sendMessage');

        $prompt = $this->capturedPrompt();
        $this->assertStringContainsString('Teks biasa.', $prompt);
        $this->assertStringNotContainsString('[SAJIKAN:', $prompt);
    }

    public function test_related_unit_search_finds_a_unit_migrated_to_content_blocks_by_keyword(): void
    {
        $currentUnit = Unit::first();
        $currentUser = $this->memberOnUnit($currentUnit);

        // A DIFFERENT unit, findable only via its content_blocks text (its
        // legacy `content` column deliberately does not mention the keyword).
        $module = Module::where('order_number', 2)->first() ?? Module::first();
        $targetUnit = $module->units()->where('id', '!=', $currentUnit->id)->first();
        $targetUnit->update(['content' => 'Tidak menyebut kata kunci apa pun di sini.']);
        $targetUnit->contentBlocks()->create([
            'type' => 'text',
            'content' => ['markdown' => 'Penjelasan lengkap tentang xylofonografi, topik yang sangat spesifik.'],
            'order' => 1,
        ]);

        Livewire::actingAs($currentUser)
            ->test(Chat::class)
            ->set('messageText', 'Apa itu xylofonografi?')
            ->call('sendMessage');

        $prompt = $this->capturedPrompt();
        $this->assertStringContainsString($targetUnit->title, $prompt);
        $this->assertStringContainsString('xylofonografi', $prompt);
    }
}

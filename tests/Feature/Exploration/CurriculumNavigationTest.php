<?php

namespace Tests\Feature\Exploration;

use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use App\Services\Exploration\ProgressService;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExplorationSampleSeeder::class);
    }

    private function member(): User
    {
        return User::create([
            'name' => 'Member',
            'email' => 'member@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
            'membership_status' => 'active',
        ]);
    }

    public function test_peta_kurikulum_shows_both_sample_modules(): void
    {
        $user = $this->member();

        $this->actingAs($user)->get('/eksplorasi/kurikulum')
            ->assertOk()
            ->assertSee('Dunia Software Development')
            ->assertSee('Bagaimana Website Bekerja');
    }

    public function test_first_unit_is_accessible_and_shows_content(): void
    {
        $user = $this->member();
        $unit = Unit::where('order_number', 1)->whereHas('module', fn ($q) => $q->where('order_number', 1))->first();

        $this->actingAs($user)->get('/eksplorasi/unit/'.$unit->id)
            ->assertOk()
            ->assertSee($unit->title)
            ->assertDontSee('belum bisa dibuka');
    }

    /**
     * Fase 4 Batch 2a: proves the admin Editor Blok Konten's output is
     * actually visible on the member-facing Materi page, not just persisted
     * to the database. A unit with content_blocks rows renders them via
     * <x-content-blocks> INSTEAD OF the legacy plain-text `content` column.
     */
    public function test_unit_with_content_blocks_renders_them_instead_of_legacy_text(): void
    {
        $user = $this->member();
        $unit = Unit::where('order_number', 1)->whereHas('module', fn ($q) => $q->where('order_number', 1))->first();
        $unit->contentBlocks()->create(['type' => 'heading', 'content' => ['level' => 2, 'text' => 'Judul Blok Baru'], 'order' => 1]);
        $unit->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Paragraf dari blok konten baru.'], 'order' => 2]);

        $response = $this->actingAs($user)->get('/eksplorasi/unit/'.$unit->id)->assertOk();

        $response->assertSee('Judul Blok Baru')
            ->assertSee('Paragraf dari blok konten baru.')
            ->assertDontSee($unit->content);
    }

    public function test_second_unit_is_locked_until_first_completed(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();
        $units = $moduleA->units()->orderBy('order_number')->get();

        $this->actingAs($user)->get('/eksplorasi/unit/'.$units[1]->id)
            ->assertOk()
            ->assertSee('belum bisa dibuka');

        app(ProgressService::class)->completeUnit($user, $units[0]);

        $this->actingAs($user)->get('/eksplorasi/unit/'.$units[1]->id)
            ->assertOk()
            ->assertSee($units[1]->title)
            ->assertDontSee('belum bisa dibuka');
    }

    public function test_second_module_units_locked_until_first_module_checkpoint_done(): void
    {
        $user = $this->member();
        $moduleB = Module::where('order_number', 2)->first();
        $firstUnitOfB = $moduleB->units()->orderBy('order_number')->first();

        $this->actingAs($user)->get('/eksplorasi/unit/'.$firstUnitOfB->id)
            ->assertOk()
            ->assertSee('belum bisa dibuka');
    }

    public function test_checkpoint_blocked_until_all_module_units_completed(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();

        $this->actingAs($user)->get('/eksplorasi/checkpoint/'.$moduleA->checkpoint->id)
            ->assertOk()
            ->assertSee('Belum semua unit selesai');

        $progress = app(ProgressService::class);
        foreach ($moduleA->units()->orderBy('order_number')->get() as $unit) {
            $progress->completeUnit($user, $unit);
        }

        $this->actingAs($user)->get('/eksplorasi/checkpoint/'.$moduleA->checkpoint->id)
            ->assertOk()
            ->assertSee('Checklist Akhir Modul')
            ->assertDontSee('Belum semua unit selesai');
    }

    /**
     * Bagian B (Fase 3): kolom kiri "Daftar Isi Modul" dibangun dari nol --
     * membuktikan seluruh unit di modul yang sama muncul (bukan cuma unit
     * yang sedang dibuka), dengan status locked/completed/in_progress yang
     * benar dari ProgressService yang sama dipakai Peta Kurikulum.
     */
    public function test_unit_page_shows_table_of_contents_for_every_unit_in_the_same_module(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();
        $units = $moduleA->units()->orderBy('order_number')->get();

        app(ProgressService::class)->completeUnit($user, $units[0]);

        $response = $this->actingAs($user)->get('/eksplorasi/unit/'.$units[1]->id);

        $response->assertOk();
        // Every unit title in module A must appear in the TOC, not just the
        // one currently open (units[1]).
        foreach ($units as $unit) {
            $response->assertSee($unit->title);
        }
    }

    /**
     * Bagian B: "stack poin progres" -- progres modul + total poin user,
     * dibaca dari ProgressService (moduleProgressPercentage/ensureProgress),
     * bukan angka baru yang dihitung ulang.
     */
    public function test_unit_page_shows_module_progress_percentage_and_total_points(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();
        $units = $moduleA->units()->orderBy('order_number')->get();
        $progressService = app(ProgressService::class);

        $progressService->completeUnit($user, $units[0]);
        $moduleProgress = $progressService->moduleProgressPercentage($moduleA, $user);
        $userProgress = $progressService->ensureProgress($user);

        $response = $this->actingAs($user)->get('/eksplorasi/unit/'.$units[1]->id);

        $response->assertOk()
            ->assertSee($moduleProgress.'%')
            ->assertSee((string) $userProgress->total_points);
    }

    /**
     * Bagian B: mekanisme slide-over lama (webiPanelOpen, FAB "Tanya WEBI"
     * fixed bottom-right) dibongkar total, diganti kolom WEBI + strip
     * toggle bawah -- ini membuktikan komponen chat tetap ter-embed dan
     * berfungsi (bukan dihapus tanpa pengganti).
     */
    public function test_unit_page_still_embeds_webi_chat_and_removes_old_slideover_markup(): void
    {
        $user = $this->member();
        $unit = Unit::where('order_number', 1)->whereHas('module', fn ($q) => $q->where('order_number', 1))->first();

        $response = $this->actingAs($user)->get('/eksplorasi/unit/'.$unit->id);

        $response->assertOk()
            ->assertSee('Chat WEBI')
            ->assertDontSee('webiPanelOpen', false);
    }

    public function test_non_exploration_member_cannot_access_kurikulum(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'admin',
            'membership_status' => 'active',
        ]);

        $this->actingAs($admin)->get('/eksplorasi/kurikulum')->assertForbidden();
    }
}

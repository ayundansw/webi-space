<?php

namespace Tests\Feature\Exploration;

use App\Livewire\Eksplorasi\Resources\Index;
use App\Models\LearningResource;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Bagian A3 (Fase 3): redesign Referensi + form submit anggota, dibangun
 * setelah migrasi 2026_07_11_000001 (created_by + description) dikonfirmasi
 * & dieksekusi.
 */
class ResourcesTest extends TestCase
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
            'name' => 'Ahmad',
            'email' => 'ahmad@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
            'membership_status' => 'active',
        ]);
    }

    /**
     * Bagian A (perbaikan lanjutan, Fase 3): tombol "Ajukan Referensi"
     * dipindah supaya sejajar (bukan dempet di bawah) card breadcrumb --
     * lewat @push('breadcrumb-actions'). Livewire::test()->html() TIDAK
     * bisa memverifikasi ini (cuma me-render komponen sendiri, bukan
     * layout lengkap tempat breadcrumb dirender) -- makanya test ini
     * pakai full HTTP get(), dan mengecek urutan posisi string di HTML
     * final untuk membuktikan tombol benar-benar muncul SEBELUM (di kiri)
     * card breadcrumb, di kontainer mengambang yang sama.
     */
    public function test_ajukan_referensi_button_sits_beside_breadcrumb_card(): void
    {
        $member = $this->member();

        $response = $this->actingAs($member)->get('/eksplorasi/resources');
        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('Ajukan Referensi', $html);
        $this->assertStringContainsString('aria-label="Breadcrumb"', $html);

        $buttonPos = strpos($html, 'Ajukan Referensi');
        $breadcrumbPos = strpos($html, 'aria-label="Breadcrumb"');
        $this->assertLessThan($breadcrumbPos, $buttonPos, 'CTA button must render before (visually left of) the breadcrumb card.');
    }

    public function test_admin_seeded_resource_shows_dari_admin_badge(): void
    {
        $member = $this->member();
        $module = Module::first();

        LearningResource::create([
            'module_id' => $module->id,
            'created_by' => null,
            'title' => 'Panduan Resmi Laravel',
            'url' => 'https://laravel.com/docs',
            'source_name' => 'laravel.com',
        ]);

        Livewire::actingAs($member)->test(Index::class)
            ->assertSee('Panduan Resmi Laravel')
            ->assertSee('Dari Admin');
    }

    public function test_member_can_submit_a_new_resource_and_it_shows_dari_anggota_badge(): void
    {
        $member = $this->member();
        $module = Module::first();

        Livewire::actingAs($member)->test(Index::class)
            ->call('openSubmitForm')
            ->set('title', 'Cheatsheet Flexbox')
            ->set('url', 'https://css-tricks.com/snippets/css/a-guide-to-flexbox/')
            ->set('description', 'Referensi cepat properti flexbox.')
            ->set('moduleId', $module->id)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('Cheatsheet Flexbox')
            ->assertSee('Dari Anggota')
            ->assertSee('css-tricks.com');

        $resource = LearningResource::where('title', 'Cheatsheet Flexbox')->first();
        $this->assertNotNull($resource);
        $this->assertSame($member->id, $resource->created_by);
        $this->assertSame($module->id, $resource->module_id);
    }

    public function test_submit_requires_title_and_url_but_not_module(): void
    {
        $member = $this->member();

        Livewire::actingAs($member)->test(Index::class)
            ->call('openSubmitForm')
            ->set('title', '')
            ->set('url', '')
            ->set('moduleId', '')
            ->call('submit')
            ->assertHasErrors(['title', 'url'])
            ->assertHasNoErrors(['moduleId']);
    }

    public function test_submit_rejects_an_invalid_url(): void
    {
        $member = $this->member();
        $module = Module::first();

        Livewire::actingAs($member)->test(Index::class)
            ->call('openSubmitForm')
            ->set('title', 'Judul Valid')
            ->set('url', 'bukan-url-yang-valid')
            ->set('moduleId', $module->id)
            ->call('submit')
            ->assertHasErrors(['url']);
    }

    /**
     * Bagian A (perbaikan lanjutan, Fase 3): module_id dijadikan nullable
     * (migrasi 2026_07_11_000002, dikonfirmasi eksplisit) supaya anggota
     * bisa submit referensi "General" -- tidak terikat modul manapun.
     */
    public function test_member_can_submit_a_general_resource_without_a_module(): void
    {
        $member = $this->member();

        Livewire::actingAs($member)->test(Index::class)
            ->call('openSubmitForm')
            ->set('title', 'Tools Manajemen Waktu untuk Programmer')
            ->set('url', 'https://todoist.com')
            ->set('moduleId', '')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('Tools Manajemen Waktu untuk Programmer')
            ->assertSee('General');

        $resource = LearningResource::where('title', 'Tools Manajemen Waktu untuk Programmer')->first();
        $this->assertNotNull($resource);
        $this->assertNull($resource->module_id);
    }
}

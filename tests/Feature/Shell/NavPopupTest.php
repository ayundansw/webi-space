<?php

namespace Tests\Feature\Shell;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Popup navigation menu (RANCANGAN_FINAL_WEBI-SPACE_v2.md §1.5, replaces the
 * old sidebar) — data still comes straight from config('navigation.*')
 * (App\Support\NavigationMatcher), this only tests the presentation layer:
 * role-scoped items, disabled-slot styling, and active-item highlight.
 */
class NavPopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_admin_menu_items_not_other_roles_items(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk()
            ->assertSee('Manajemen Akun')
            ->assertSee('Project Ideas')
            ->assertDontSee('Peta Kurikulum')
            ->assertDontSee('Kalender Personal');
    }

    public function test_exploration_member_sees_exploration_menu_items(): void
    {
        $member = User::factory()->create();

        $response = $this->actingAs($member)->get('/eksplorasi/dashboard');

        $response->assertOk()
            ->assertSee('Peta Kurikulum')
            ->assertSee('Referensi')
            ->assertSee('WEBI')
            ->assertDontSee('Manajemen Akun')
            ->assertDontSee('Kalender Personal');
    }

    public function test_execution_member_sees_execution_menu_items(): void
    {
        $execution = User::factory()->executionMember()->create();

        $response = $this->actingAs($execution)->get('/eksekusi/dashboard');

        $response->assertOk()
            ->assertSee('Project Ideas')
            ->assertSee('Proyek')
            ->assertDontSee('Peta Kurikulum')
            ->assertDontSee('Manajemen Akun');
    }

    /**
     * Fase 8 Batch 6 (gap ditemukan Batch 4): execution_member's read-only
     * Eksplorasi access (Batch 3) had no UI navigation entry pointing at it
     * at all -- URL-only. This adds one.
     */
    public function test_execution_member_sees_a_link_to_eksplorasi(): void
    {
        $execution = User::factory()->executionMember()->create();

        $response = $this->actingAs($execution)->get('/eksekusi/dashboard');

        $response->assertOk()->assertSee('Jelajahi Eksplorasi');
    }

    public function test_disabled_slot_item_is_rendered_non_clickable(): void
    {
        // 'Praktik' (exploration_member), then 'Kalender Personal'
        // (execution_member), then 'Forum General' (execution_member) were
        // this test's earlier subjects while still placeholders
        // (enabled=false) — Praktik 2, Fase 7 Batch 2b, and the Forum
        // General Eksekusi task wired each up for real in turn. Zero
        // `enabled: false` slots remain ANYWHERE in config/navigation.php
        // now, so this test injects a synthetic one instead of depending on
        // production config to always have a spare placeholder lying
        // around — the behavior under test (a disabled slot renders as a
        // non-link <span> with the "segera hadir" hint, never a clickable
        // <a href>) is what matters, not which specific feature happens to
        // still be unbuilt.
        config(['navigation.execution_member' => array_merge(
            config('navigation.execution_member'),
            [['label' => 'Fitur Uji Disabled', 'route' => null, 'enabled' => false]],
        )]);
        $execution = User::factory()->executionMember()->create();

        $response = $this->actingAs($execution)->get('/eksekusi/dashboard');

        $response->assertOk()
            ->assertSee('Fitur Uji Disabled')
            ->assertSee('segera hadir');
    }

    public function test_active_page_gets_ring_highlight_class(): void
    {
        $member = User::factory()->create();

        $response = $this->actingAs($member)->get('/eksplorasi/kurikulum');

        $response->assertOk()
            ->assertSee('ring-1 ring-offset-4 ring-offset-white ring-accent', escape: false);
    }

    /**
     * Bagian A (perbaikan lanjutan): "menu yang sedang dikunjungi otomatis
     * ada ring, dan hilang lagi kalau sudah tidak dikunjungi" -- dibuktikan
     * konkret di sini lewat 2 skenario yang sebelumnya cuma diverifikasi
     * lewat 1 exact-route case (test_active_page_gets_ring_highlight_class
     * di atas): (1) sub-halaman (bukan URL index persis) tetap
     * mempertahankan ring nav item induknya, (2) mengunjungi halaman LAIN
     * membuat ring itu hilang -- membuktikan ring memang state per-request
     * (dibaca dari route aktif saat itu), bukan status yang "nyangkut".
     */
    public function test_ring_highlight_follows_sub_routes_and_disappears_when_navigating_away(): void
    {
        $member = User::factory()->create();

        $module = \App\Models\Module::first();
        $thread = \App\Models\ForumThread::create([
            'module_id' => $module?->id,
            'portal' => 'exploration',
            'created_by' => $member->id,
            'title' => 'Thread Uji Ring',
            'content' => 'Isi',
            'target' => 'peer',
        ]);

        // Forum thread detail (eksplorasi.forum.show) is a SUB-route of the
        // Forum nav item (eksplorasi.forum.index) -- ring must still show.
        $this->actingAs($member)->get('/eksplorasi/forum/'.$thread->id)
            ->assertOk()
            ->assertSee('ring-1 ring-offset-4 ring-offset-white ring-warning', escape: false);

        // Visiting an unrelated page (Peta Kurikulum) must NOT carry the
        // Forum ring over -- it should get its own (ring-accent) instead,
        // proving the highlight is freshly computed per request, not stuck.
        $response = $this->actingAs($member)->get('/eksplorasi/kurikulum');
        $response->assertOk()
            ->assertSee('ring-1 ring-offset-4 ring-offset-white ring-accent', escape: false)
            ->assertDontSee('ring-1 ring-offset-4 ring-offset-white ring-warning', escape: false);
    }
}

<?php

namespace Tests\Feature\Shell;

use App\Models\Module;
use App\Models\User;
use App\Support\Breadcrumbs;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Breadcrumb (RANCANGAN_FINAL_WEBI-SPACE_v2.md §1.6), generated automatically
 * by App\Support\Breadcrumbs — one case per portal plus the 4 distinct code
 * paths: dashboard-only, exact-match (list page), matched-with-entity-resolver
 * (detail page), and unmatched (reflection #[Title] fallback).
 */
class BreadcrumbsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function explorationMember(): User
    {
        return User::factory()->create();
    }

    private function executionMember(): User
    {
        return User::factory()->executionMember()->create();
    }

    public function test_dashboard_shows_only_beranda_with_no_link(): void
    {
        $admin = $this->admin();
        $route = app('router')->getRoutes()->getByName('admin.dashboard');

        $this->actingAs($admin);
        request()->setRouteResolver(fn () => $route);

        $trail = Breadcrumbs::trail();

        $this->assertSame([['label' => 'Beranda', 'url' => null]], $trail);
    }

    public function test_admin_list_page_shows_matching_nav_item_as_final_crumb(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk()->assertSeeInOrder(['Beranda', 'Manajemen Akun']);
    }

    public function test_execution_list_page_shows_matching_nav_item_as_final_crumb(): void
    {
        $execution = $this->executionMember();

        $response = $this->actingAs($execution)->get('/eksekusi/projects');

        $response->assertOk()->assertSeeInOrder(['Beranda', 'Proyek']);
    }

    public function test_exploration_list_page_shows_matching_nav_item_as_final_crumb(): void
    {
        $member = $this->explorationMember();

        $response = $this->actingAs($member)->get('/eksplorasi/kurikulum');

        $response->assertOk()->assertSeeInOrder(['Beranda', 'Peta Kurikulum']);
    }

    public function test_unit_detail_page_shows_parent_nav_item_then_unit_title(): void
    {
        $this->seed(ExplorationSampleSeeder::class);
        $member = $this->explorationMember();
        $unit = Module::where('order_number', 1)->first()->units()->orderBy('order_number')->first();

        $response = $this->actingAs($member)->get('/eksplorasi/unit/'.$unit->id);

        $response->assertOk()->assertSeeInOrder(['Beranda', 'Peta Kurikulum', $unit->title]);
    }

    public function test_page_with_no_matching_nav_item_falls_back_to_title_attribute(): void
    {
        $member = $this->explorationMember();

        $response = $this->actingAs($member)->get('/profile');

        // Profile\Edit has #[Title('Profil Saya')] and is not in any nav
        // config -- Beranda + the reflected title, no middle nav-item crumb.
        $response->assertOk()->assertSeeInOrder(['Beranda', 'Profil Saya']);
    }

    public function test_checkpoint_detail_page_uses_module_order_number_resolver(): void
    {
        $this->seed(ExplorationSampleSeeder::class);
        $member = $this->explorationMember();
        $module = Module::where('order_number', 1)->first();
        $checkpoint = $module->checkpoint;

        $response = $this->actingAs($member)->get('/eksplorasi/checkpoint/'.$checkpoint->id);

        $response->assertOk()->assertSeeInOrder(['Beranda', 'Peta Kurikulum', 'Checkpoint Modul 1']);
    }
}

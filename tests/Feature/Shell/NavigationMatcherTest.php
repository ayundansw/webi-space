<?php

namespace Tests\Feature\Shell;

use App\Support\NavigationMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * App\Support\NavigationMatcher — extracted from the old sidebar closure so
 * both <x-shell.nav-popup> and App\Support\Breadcrumbs share one matching
 * implementation. Pure logic, no view/DB dependency beyond the current
 * request's resolved route.
 */
class NavigationMatcherTest extends TestCase
{
    use RefreshDatabase;

    private function fakeRouteRequest(string $routeName): void
    {
        $route = app('router')->getRoutes()->getByName($routeName);
        request()->setRouteResolver(fn () => $route);
    }

    public function test_disabled_item_is_never_active_even_if_route_matches(): void
    {
        $this->fakeRouteRequest('eksplorasi.dashboard');

        $this->assertFalse(NavigationMatcher::isActive([
            'label' => 'Praktik', 'route' => null, 'enabled' => false,
        ]));
    }

    public function test_suffix_widening_matches_create_and_show_under_index_item(): void
    {
        $this->fakeRouteRequest('admin.users.create');

        $this->assertTrue(NavigationMatcher::isActive([
            'label' => 'Manajemen Akun', 'route' => 'admin.users.index', 'enabled' => true,
        ]));
    }

    public function test_active_when_override_matches_route_outside_own_prefix(): void
    {
        $this->fakeRouteRequest('eksekusi.tasks.show');

        $this->assertTrue(NavigationMatcher::isActive([
            'label' => 'Proyek', 'route' => 'eksekusi.projects.index', 'enabled' => true,
            'active_when' => ['eksekusi.tasks.show'],
        ]));
    }

    public function test_unrelated_route_does_not_match(): void
    {
        $this->fakeRouteRequest('profile.edit');

        $this->assertFalse(NavigationMatcher::isActive([
            'label' => 'Manajemen Akun', 'route' => 'admin.users.index', 'enabled' => true,
        ]));
    }

    public function test_current_item_returns_first_matching_item(): void
    {
        $this->fakeRouteRequest('eksplorasi.kurikulum');

        $item = NavigationMatcher::currentItem(config('navigation.exploration_member'));

        $this->assertSame('Peta Kurikulum', $item['label']);
    }
}

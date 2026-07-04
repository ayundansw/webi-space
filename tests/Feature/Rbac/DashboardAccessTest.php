<?php

namespace Tests\Feature\Rbac;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => 'User '.$role,
            'email' => $role.'@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => $role,
            'membership_status' => 'active',
        ]);
    }

    public function test_admin_can_access_admin_dashboard_only(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($admin)->get('/eksplorasi/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/eksekusi/dashboard')->assertForbidden();
    }

    public function test_exploration_member_can_access_eksplorasi_dashboard_only(): void
    {
        $member = $this->makeUser('exploration_member');

        $this->actingAs($member)->get('/eksplorasi/dashboard')->assertOk();
        $this->actingAs($member)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($member)->get('/eksekusi/dashboard')->assertForbidden();
    }

    public function test_execution_member_can_access_eksekusi_dashboard_only(): void
    {
        $member = $this->makeUser('execution_member');

        $this->actingAs($member)->get('/eksekusi/dashboard')->assertOk();
        $this->actingAs($member)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($member)->get('/eksplorasi/dashboard')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_for_any_dashboard(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/eksplorasi/dashboard')->assertRedirect('/login');
        $this->get('/eksekusi/dashboard')->assertRedirect('/login');
    }

    public function test_generic_dashboard_route_redirects_based_on_role(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/admin/dashboard');
    }
}

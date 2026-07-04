<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_login_and_is_redirected_to_role_dashboard(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'admin',
            'membership_status' => 'active',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'admin@example.test')
            ->set('password', 'secret123')
            ->call('login')
            ->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_exploration_member_is_redirected_to_eksplorasi_dashboard(): void
    {
        User::create([
            'name' => 'Explorer',
            'email' => 'explorer@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
            'membership_status' => 'active',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'explorer@example.test')
            ->set('password', 'secret123')
            ->call('login')
            ->assertRedirect('/eksplorasi/dashboard');
    }

    public function test_execution_member_is_redirected_to_eksekusi_dashboard(): void
    {
        User::create([
            'name' => 'Executor',
            'email' => 'executor@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'execution_member',
            'membership_status' => 'active',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'executor@example.test')
            ->set('password', 'secret123')
            ->call('login')
            ->assertRedirect('/eksekusi/dashboard');
    }

    public function test_wrong_password_rejects_login(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'admin',
            'membership_status' => 'active',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'admin@example.test')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_login(): void
    {
        User::create([
            'name' => 'Inactive',
            'email' => 'inactive@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
            'membership_status' => 'inactive',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'inactive@example.test')
            ->set('password', 'secret123')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }
}

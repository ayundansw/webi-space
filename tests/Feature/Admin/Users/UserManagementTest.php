<?php

namespace Tests\Feature\Admin\Users;

use App\Livewire\Admin\Users\Create;
use App\Livewire\Admin\Users\Edit;
use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'admin',
            'membership_status' => 'active',
        ]);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $member = User::create([
            'name' => 'Member',
            'email' => 'member@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
            'membership_status' => 'active',
        ]);

        $this->actingAs($member)->get('/admin/users')->assertForbidden();
        $this->actingAs($member)->get('/admin/users/create')->assertForbidden();
    }

    public function test_admin_can_create_user_and_generated_password_actually_works(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('name', 'Anggota Baru')
            ->set('email', 'baru@example.test')
            ->set('role', 'exploration_member')
            ->call('save')
            ->assertRedirect('/admin/users');

        $newUser = User::where('email', 'baru@example.test')->first();
        $this->assertNotNull($newUser);
        $this->assertSame('exploration_member', $newUser->role);
        $this->assertSame('active', $newUser->membership_status);

        $generatedPassword = session('generated_password');
        $this->assertNotEmpty($generatedPassword);

        // the generated password must actually authenticate the new user
        Livewire::test(Login::class)
            ->set('email', 'baru@example.test')
            ->set('password', $generatedPassword)
            ->call('login')
            ->assertRedirect('/eksplorasi/dashboard');
    }

    public function test_admin_can_edit_user_role_and_status(): void
    {
        $admin = $this->admin();
        $member = User::create([
            'name' => 'Member',
            'email' => 'member@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
            'membership_status' => 'active',
        ]);

        Livewire::actingAs($admin)
            ->test(Edit::class, ['user' => $member])
            ->set('role', 'execution_member')
            ->set('membership_status', 'inactive')
            ->call('save');

        $member->refresh();
        $this->assertSame('execution_member', $member->role);
        $this->assertSame('inactive', $member->membership_status);
    }

    public function test_deactivated_user_fails_login_after_admin_deactivates(): void
    {
        $admin = $this->admin();
        $member = User::create([
            'name' => 'Member',
            'email' => 'member@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
            'membership_status' => 'active',
        ]);

        // sanity check: can log in while active
        Livewire::test(Login::class)
            ->set('email', 'member@example.test')
            ->set('password', 'secret123')
            ->call('login')
            ->assertRedirect('/eksplorasi/dashboard');

        Livewire::actingAs($admin)
            ->test(Edit::class, ['user' => $member])
            ->set('membership_status', 'inactive')
            ->call('save');

        Livewire::test(Login::class)
            ->set('email', 'member@example.test')
            ->set('password', 'secret123')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    /**
     * Task 2.6 Batch 3 finding: the test above only proved a NEW login
     * attempt is blocked after deactivation. A member with an already-active
     * session kept full access until that session expired, since nothing
     * re-checked membership_status per request — fixed via the globally
     * registered App\Http\Middleware\EnsureMembershipIsActive.
     */
    public function test_deactivating_a_member_with_an_active_session_cuts_off_access_immediately(): void
    {
        $admin = $this->admin();
        $member = User::create([
            'name' => 'Member',
            'email' => 'member@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
            'membership_status' => 'active',
        ]);

        $this->actingAs($member)->get('/eksplorasi/dashboard')->assertOk();

        Livewire::actingAs($admin)
            ->test(Edit::class, ['user' => $member])
            ->set('membership_status', 'inactive')
            ->call('save');

        // actingAs() binds the User object directly into the guard rather than
        // re-querying the DB per request, so the in-memory $member instance
        // must be refreshed to reflect the admin's update before re-acting as
        // them — otherwise this would silently re-assert the stale "active" copy.
        $this->actingAs($member->refresh())->get('/eksplorasi/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_cannot_deactivate_or_demote_own_account(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(Edit::class, ['user' => $admin])
            ->set('membership_status', 'inactive')
            ->call('save')
            ->assertHasErrors('membership_status');

        $admin->refresh();
        $this->assertSame('active', $admin->membership_status);

        Livewire::actingAs($admin)
            ->test(Edit::class, ['user' => $admin])
            ->set('role', 'exploration_member')
            ->call('save')
            ->assertHasErrors('role');

        $admin->refresh();
        $this->assertSame('admin', $admin->role);
    }

    public function test_admin_can_reset_password_and_old_password_stops_working(): void
    {
        $admin = $this->admin();
        $member = User::create([
            'name' => 'Member',
            'email' => 'member@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
            'membership_status' => 'active',
        ]);

        $rendered = Livewire::actingAs($admin)
            ->test(Edit::class, ['user' => $member])
            ->call('resetPassword')
            ->html();

        preg_match('/data-testid="generated-password">([^<]+)</', $rendered, $matches);
        $newPassword = isset($matches[1]) ? html_entity_decode($matches[1]) : null;
        $this->assertNotEmpty($newPassword);

        $member->refresh();
        $this->assertFalse(Hash::check('secret123', $member->password_hash));
        $this->assertTrue(Hash::check($newPassword, $member->password_hash));

        Livewire::test(Login::class)
            ->set('email', 'member@example.test')
            ->set('password', 'secret123')
            ->call('login')
            ->assertHasErrors('email');

        Livewire::test(Login::class)
            ->set('email', 'member@example.test')
            ->set('password', $newPassword)
            ->call('login')
            ->assertRedirect('/eksplorasi/dashboard');
    }
}

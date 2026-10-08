<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    /**
     * Pre-deploy audit finding: rehash-on-login writes to the column named by
     * getAuthPasswordName(). Without that override it targeted a "password"
     * column that doesn't exist, so a BCRYPT_ROUNDS mismatch between seeding
     * and the server made login throw instead of succeed.
     */
    public function test_login_rehashes_into_password_hash_when_bcrypt_rounds_changed(): void
    {
        $oldHash = Hash::make('secret123', ['rounds' => 4]);

        $user = User::create([
            'name' => 'Rehash',
            'email' => 'rehash@example.test',
            'password_hash' => $oldHash,
            'role' => 'exploration_member',
            'membership_status' => 'active',
        ]);

        config(['hashing.bcrypt.rounds' => 12]);
        Hash::driver('bcrypt')->setRounds(12);

        Livewire::test(Login::class)
            ->set('email', 'rehash@example.test')
            ->set('password', 'secret123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/eksplorasi/dashboard');

        $this->assertAuthenticatedAs($user);

        $newHash = $user->fresh()->password_hash;

        $this->assertNotSame($oldHash, $newHash);
        $this->assertTrue(Hash::check('secret123', $newHash));
        $this->assertSame(12, Hash::info($newHash)['options']['cost']);
    }
}

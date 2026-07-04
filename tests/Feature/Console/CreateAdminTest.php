<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 2.7 Batch 1: permanent replacement for manual-tinker admin creation
 * (flagged as temporary back in 2.1). Credentials are only ever supplied
 * interactively at runtime, never hardcoded — this test drives the real
 * console command via prompts, the same way a human operator would.
 */
class CreateAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_an_admin_account_from_interactive_prompts(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Nama admin', 'Ayunda')
            ->expectsQuestion('Email admin', 'ayunda@example.test')
            ->expectsQuestion('Password (minimal 8 karakter, tidak ditampilkan di layar)', 'secret1234')
            ->expectsQuestion('Ulangi password', 'secret1234')
            ->assertExitCode(0);

        $admin = User::where('email', 'ayunda@example.test')->first();
        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role);
        $this->assertSame('active', $admin->membership_status);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('secret1234', $admin->password_hash));
    }

    public function test_rejects_a_password_shorter_than_eight_characters(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Nama admin', 'Ayunda')
            ->expectsQuestion('Email admin', 'ayunda@example.test')
            ->expectsQuestion('Password (minimal 8 karakter, tidak ditampilkan di layar)', 'short')
            ->expectsQuestion('Ulangi password', 'short')
            ->assertExitCode(1);

        $this->assertNull(User::where('email', 'ayunda@example.test')->first());
    }

    public function test_rejects_mismatched_password_confirmation(): void
    {
        $this->artisan('app:create-admin')
            ->expectsQuestion('Nama admin', 'Ayunda')
            ->expectsQuestion('Email admin', 'ayunda@example.test')
            ->expectsQuestion('Password (minimal 8 karakter, tidak ditampilkan di layar)', 'secret1234')
            ->expectsQuestion('Ulangi password', 'different123')
            ->assertExitCode(1);

        $this->assertNull(User::where('email', 'ayunda@example.test')->first());
    }

    public function test_rejects_an_email_already_in_use(): void
    {
        User::create([
            'name' => 'Existing', 'email' => 'taken@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ]);

        $this->artisan('app:create-admin')
            ->expectsQuestion('Nama admin', 'Ayunda')
            ->expectsQuestion('Email admin', 'taken@example.test')
            ->expectsQuestion('Password (minimal 8 karakter, tidak ditampilkan di layar)', 'secret1234')
            ->expectsQuestion('Ulangi password', 'secret1234')
            ->assertExitCode(1);

        $this->assertSame(1, User::where('email', 'taken@example.test')->count());
    }
}

<?php

namespace Tests\Feature\Console;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `php artisan app:seed-eksekusi-demo` is guarded to only ever run in the
 * local environment (see App\Console\Commands\SeedEksekusiDemo docblock) --
 * phpunit always runs with APP_ENV=testing (phpunit.xml), so the guard
 * itself is exactly what's exercised here. Same limitation as the dev
 * preview routes (local-env-gated, verified manually in a real local
 * terminal) — actually populating the 3 demo projects needs a manual run
 * outside this suite, this test only proves the safety guard works and
 * never risks writing dummy data into a non-local database by accident.
 */
class SeedEksekusiDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_refuses_to_run_outside_local_environment(): void
    {
        $this->assertNotEquals('local', app()->environment());

        $this->artisan('app:seed-eksekusi-demo', ['email' => 'someone@example.test'])
            ->assertExitCode(1);

        $this->assertSame(0, Project::count());
    }

    /**
     * Verifies the actual seeding logic (3 projects, idempotency) against
     * the isolated test database by forcing the environment check to pass
     * for this one test only (`detectEnvironment`) -- phpunit's own DB
     * connection is never the real local dev database, so this is safe:
     * it proves the command's business logic is correct without ever
     * running it against real data.
     */
    public function test_seeds_three_varied_demo_projects_idempotently(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'admin', 'membership_status' => 'active',
        ]);
        $member = User::create([
            'name' => 'Ahmad', 'email' => 'ahmad@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'execution_member', 'membership_status' => 'active',
        ]);

        $this->artisan('app:seed-eksekusi-demo', ['email' => 'ahmad@example.test'])
            ->assertExitCode(0);

        $this->assertSame(3, Project::count());
        $this->assertTrue(Project::where('status', 'on_hold')->exists());

        // Idempotent: re-running must not duplicate.
        $this->artisan('app:seed-eksekusi-demo', ['email' => 'ahmad@example.test'])
            ->assertExitCode(0);

        $this->assertSame(3, Project::count());
    }
}

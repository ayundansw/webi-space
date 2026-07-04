<?php

namespace Tests\Feature\Console;

use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 2.7 Batch 1: `DatabaseSeeder` is what runs on `php artisan migrate:fresh
 * --seed` — the production/dev seeding entrypoint. Must seed the real
 * curriculum only, and create zero users (the first admin is created
 * separately via the interactive `app:create-admin` command).
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_creates_real_curriculum_but_no_users_at_all(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(10, Module::count(), 'Real curriculum has 10 modules (docs/kurikulum-eksplorasi.md).');
        $this->assertSame(67, Unit::count(), 'Real curriculum has 67 units.');
        $this->assertSame(0, User::count(), 'No user of any kind (including admin) should exist right after a fresh seed.');
    }
}

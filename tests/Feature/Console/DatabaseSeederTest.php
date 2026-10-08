<?php

namespace Tests\Feature\Console;

use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 2.7 Batch 1 (superseded by Fase 0 + kurikulum v2.0 batch terakhir):
 * `DatabaseSeeder` is what runs on `php artisan migrate:fresh --seed` — the
 * production/dev seeding entrypoint. Originally seeded the curriculum only
 * with zero users (first admin created separately via `app:create-admin`),
 * but Fase 0 changed this deliberately: production now seeds 16 named
 * accounts directly (AccountSeeder) alongside the full v2.0 curriculum
 * (CurriculumSeeder, 10 modules/42 units) in the same entrypoint.
 */
class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_creates_the_real_v2_curriculum_and_the_16_named_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(10, Module::count(), 'Real v2.0 curriculum has 10 modules.');
        $this->assertSame(42, Unit::count(), 'Real v2.0 curriculum has 42 units.');
        $this->assertSame(16, User::count(), 'Fase 0 seeds 16 named accounts (3 pegangan + 13 real) directly, not zero.');
    }
}

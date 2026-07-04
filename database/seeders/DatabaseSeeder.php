<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Task 2.7: production/dev seeding entrypoint (`php artisan migrate:fresh --seed`).
 * Seeds ONLY the real curriculum content — no sample/dummy data, no users of
 * any kind. `ExplorationSampleSeeder` stays test-only (called explicitly via
 * `$this->seed(ExplorationSampleSeeder::class)` in test setUp() methods, never
 * from here). The first admin account is created afterward via the
 * interactive `php artisan app:create-admin` command — never hardcoded here,
 * per the no-hardcoded-credentials rule from task 2.1 (public repo).
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CurriculumSeeder::class);
    }
}

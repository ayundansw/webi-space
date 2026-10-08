<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Fase 0 + kurikulum v2.0 + Praktik + Eksekusi: entrypoint seeding
 * produksi/dev (`php artisan migrate:fresh --seed`). AccountSeeder (16
 * akun), CurriculumSeeder (10 modul/42 unit), ChallengeSeeder (15
 * Challenge + Track Map), dan ExecutionSeeder (4 Project Ideas + 1 Project
 * dummy) semua disambungkan di sini -- satu proses seed lengkap.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AccountSeeder::class);
        $this->call(CurriculumSeeder::class);
        $this->call(ChallengeSeeder::class);
        $this->call(ExecutionSeeder::class);
    }
}

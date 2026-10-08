<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Fase 0, Bagian 1-3: akun pegangan + akun real anggota Eksplorasi dan
 * Eksekusi. Data (termasuk password) dibaca dari file terpisah yang
 * di-gitignore (database/seeders/data/akun_awal.php), tidak pernah
 * di-hardcode di file ini karena repo publik.
 */
class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/akun_awal.php');

        if (! file_exists($path)) {
            $this->command->error("File data akun tidak ditemukan di {$path}. Buat file ini dulu (lihat prompt seeder akun), jangan commit ke git.");

            return;
        }

        $akunList = require $path;

        foreach ($akunList as $akun) {
            User::updateOrCreate(
                ['email' => $akun['email']],
                [
                    'name' => $akun['name'],
                    'password_hash' => bcrypt($akun['password']),
                    'role' => $akun['role'],
                    'membership_status' => 'active',
                    'interest_field' => null,
                    'avatar_url' => null,
                ]
            );
        }

        $this->command->info('Selesai: '.count($akunList).' akun diproses.');
    }
}

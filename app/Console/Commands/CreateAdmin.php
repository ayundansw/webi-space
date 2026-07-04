<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Task 2.7: permanent, production-safe replacement for the manual-tinker
 * admin creation used during early development. Prompts interactively for
 * name/email/password — never accepts them as command-line arguments (those
 * would land in shell history) and never hardcodes credentials in code,
 * since this repo is public. `database/seeders/DatabaseSeeder.php` no longer
 * creates any user at all; this command is the only way to create the first
 * (or any subsequent) admin account outside the `/admin/users` UI.
 */
class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Buat akun admin baru secara interaktif (nama, email, password ditanyakan lewat prompt, tidak pernah di-hardcode).';

    public function handle(): int
    {
        $name = $this->ask('Nama admin');
        $email = $this->ask('Email admin');
        $password = $this->secret('Password (minimal 8 karakter, tidak ditampilkan di layar)');
        $passwordConfirmation = $this->secret('Ulangi password');

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $validated = $validator->validated();

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password_hash' => bcrypt($validated['password']),
            'role' => 'admin',
            'membership_status' => 'active',
        ]);

        $this->info("Akun admin '{$validated['name']}' ({$validated['email']}) berhasil dibuat.");

        return self::SUCCESS;
    }
}

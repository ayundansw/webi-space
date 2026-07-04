<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Masuk')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public function login(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            $this->addError('email', 'Email atau password salah.');

            return;
        }

        $user = Auth::user();

        if ($user->membership_status !== 'active') {
            Auth::logout();

            $this->addError('email', 'Akun ini nonaktif. Hubungi admin untuk mengaktifkan kembali.');

            return;
        }

        $this->redirect($user->dashboardPath(), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}

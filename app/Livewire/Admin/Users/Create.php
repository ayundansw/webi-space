<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Buat Akun Baru')]
class Create extends Component
{
    public string $name = '';

    public string $email = '';

    public string $role = 'exploration_member';

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:exploration_member,execution_member,admin'],
        ]);

        $password = Str::password(12);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password_hash' => bcrypt($password),
            'role' => $validated['role'],
            'membership_status' => 'active',
        ]);

        session()->flash('generated_password', $password);
        session()->flash('generated_password_user', $user->name);

        $this->redirect('/admin/users', navigate: false);
    }

    public function render()
    {
        return view('livewire.admin.users.create');
    }
}

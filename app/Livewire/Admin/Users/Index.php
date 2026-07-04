<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Manajemen Akun')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.admin.users.index', [
            'users' => User::orderBy('name')->get(),
        ]);
    }
}

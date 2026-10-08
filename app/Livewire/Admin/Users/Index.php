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
    public string $search = '';

    public string $roleFilter = '';

    public string $statusFilter = '';

    public function render()
    {
        $users = User::query()
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->roleFilter !== '', fn ($query) => $query->where('role', $this->roleFilter))
            ->when($this->statusFilter !== '', fn ($query) => $query->where('membership_status', $this->statusFilter))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.users.index', [
            'users' => $users,
        ]);
    }
}

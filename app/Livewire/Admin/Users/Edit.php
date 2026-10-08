<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Services\DualMode\DualModeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Kelola Akun')]
class Edit extends Component
{
    public User $user;

    public string $name = '';

    public string $email = '';

    public string $role = '';

    public string $membership_status = '';

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->membership_status = $user->membership_status;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$this->user->id],
            'role' => ['required', 'in:exploration_member,execution_member,admin'],
            'membership_status' => ['required', 'in:active,inactive'],
        ]);

        if ($this->user->id === Auth::id()) {
            if ($validated['membership_status'] !== 'active') {
                $this->addError('membership_status', 'Kamu tidak bisa menonaktifkan akunmu sendiri.');

                return;
            }

            if ($validated['role'] !== 'admin') {
                $this->addError('role', 'Kamu tidak bisa mengubah role akunmu sendiri.');

                return;
            }
        }

        $this->user->update($validated);

        session()->flash('status', 'Perubahan disimpan.');
    }

    public function resetPassword(): void
    {
        $password = Str::password(12);

        $this->user->update(['password_hash' => bcrypt($password)]);

        session()->flash('generated_password', $password);
        session()->flash('generated_password_user', $this->user->name);
    }

    /**
     * Fase 8 Batch 5 (§5.3 "Cabut Akses"). All status mutation stays in
     * DualModeService::revoke() (Batch 4) — that method already resets
     * `active_mode` to null unconditionally whenever access is revoked,
     * so a user currently active in Mode Eksekusi is never left stranded
     * in a mode they no longer have access to. No change to that service
     * was needed for this batch.
     */
    public function revokeDualModeAccess(DualModeService $service): void
    {
        $service->revoke($this->user, Auth::user());
        $this->user->refresh();

        session()->flash('status', "Akses Mode Eksekusi {$this->user->name} dicabut.");
    }

    public function render()
    {
        return view('livewire.admin.users.edit');
    }
}

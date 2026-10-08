<?php

namespace App\Livewire\Eksekusi;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Pilih avatar hewan Eksekusi — 5 pilihan bebas, tanpa syarat unlock, bisa
 * ganti kapan saja. TIDAK ADA hubungan dengan tingkat Fox Eksplorasi (lihat
 * FoxAvatarService) — dua mekanisme berbeda aturan bisnis.
 *
 * Disimpan ke `users.avatar_url` (kolom sudah ada, sebelumnya tidak
 * terpakai) — diisi slug hewan (bukan URL sungguhan), reuse yang disengaja;
 * kalau nanti perlu nama kolom lebih akurat, itu migrasi terpisah.
 */
#[Layout('components.layouts.app')]
#[Title('Pilih Avatar')]
class AvatarPicker extends Component
{
    public const array OPTIONS = ['elang', 'serigala', 'singa', 'harimau', 'cheetah'];

    public string $selected = '';

    public function mount(): void
    {
        $this->selected = Auth::user()->avatar_url ?? '';
    }

    public function choose(string $jenis): void
    {
        if (! in_array($jenis, self::OPTIONS, true)) {
            return;
        }

        Auth::user()->update(['avatar_url' => $jenis]);

        $this->selected = $jenis;

        session()->flash('status', 'Avatar berhasil diganti.');
    }

    public function render()
    {
        return view('livewire.eksekusi.avatar-picker', [
            'options' => self::OPTIONS,
        ]);
    }
}

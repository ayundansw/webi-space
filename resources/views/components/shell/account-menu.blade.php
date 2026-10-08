@php
    $user = auth()->user();
@endphp

<div class="relative" x-data="{ accountMenuOpen: false }" @click.outside="accountMenuOpen = false">
    <button
        type="button"
        @click="accountMenuOpen = !accountMenuOpen"
        :aria-expanded="accountMenuOpen.toString()"
        aria-label="Menu akun"
        class="flex items-center gap-2 rounded-control border border-muted/40 py-1 pr-2.5 pl-1 transition-colors duration-150 hover:border-ink hover:bg-accent-soft"
    >
        @if ($user->role === 'exploration_member')
            <x-avatar.fox :tingkat="app(\App\Services\Exploration\FoxAvatarService::class)->tierFor($user)" size="sm" />
        @elseif ($user->role === 'execution_member')
            <x-avatar.eksekusi :jenis="$user->avatar_url ?? 'elang'" size="sm" />
        @else
            <x-shell.admin-avatar-icon size="sm" />
        @endif
        <span class="hidden text-sm text-ink sm:inline">{{ $user->name }}</span>
    </button>

    <div
        x-show="accountMenuOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 z-40 mt-2 w-48 rounded-xl border border-muted/25 bg-white p-2 shadow-warm-lg"
    >
        <a
            href="{{ url('/profile') }}"
            wire:navigate
            @click="accountMenuOpen = false"
            class="block rounded-lg px-3 py-2 text-sm text-ink hover:bg-surface-alt"
        >
            Profil Saya
        </a>

        {{--
            Fase 8 Batch 6: opsi switch mode, cuma untuk user yang
            hasDualModeCapability(). exploration_member yang approved: aksi
            SUNGGUHAN (POST -> SwitchModeController, memutasi active_mode).
            execution_member: murni navigasi (<a> biasa, tidak ada state di
            DB untuk role ini -- lihat User::isInSwitchedMode() docblock).
        --}}
        @if ($user->hasDualModeCapability())
            <div class="my-1 border-t border-muted/20"></div>

            @if ($user->role === 'exploration_member')
                <form method="POST" action="{{ route('mode.switch') }}">
                    @csrf
                    <input type="hidden" name="mode" value="{{ $user->isInSwitchedMode() ? 'origin' : 'execution' }}">
                    <button
                        type="submit"
                        @click="accountMenuOpen = false"
                        class="block w-full rounded-lg px-3 py-2 text-left text-sm text-ink hover:bg-surface-alt"
                    >
                        {{ $user->isInSwitchedMode() ? 'Kembali ke Mode Asal' : 'Beralih ke Mode Eksekusi' }}
                    </button>
                </form>
            @else
                <a
                    href="{{ $user->isInSwitchedMode() ? route('eksekusi.dashboard') : route('eksplorasi.kurikulum') }}"
                    wire:navigate
                    @click="accountMenuOpen = false"
                    class="block rounded-lg px-3 py-2 text-sm text-ink hover:bg-surface-alt"
                >
                    {{ $user->isInSwitchedMode() ? 'Kembali ke Mode Asal' : 'Beralih ke Mode Eksplorasi' }}
                </a>
            @endif
        @endif

        <form method="POST" action="{{ url('/logout') }}">
            @csrf
            <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-danger hover:bg-danger-soft">
                Keluar
            </button>
        </form>
    </div>
</div>

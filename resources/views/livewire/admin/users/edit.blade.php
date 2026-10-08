@push('breadcrumb-actions')
    <a href="{{ url('/admin/users') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-control border border-muted/40 px-4 py-2 text-sm font-medium text-ink shadow-warm-xs hover:border-ink hover:bg-surface-alt">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
        </svg>
        Kembali
    </a>
@endpush

<div class="max-w-lg">
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold text-ink">Kelola Akun</h1>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-muted/25 bg-green-50 p-4 text-sm text-green-700">
            {{ session('status') }}
        </div>
    @endif

    @if (session('generated_password'))
        <div class="mb-6 rounded-xl border border-accent/50 bg-accent-soft/40 p-4">
            <p class="text-sm text-ink">
                Password baru untuk <strong>{{ session('generated_password_user') }}</strong> (tampil sekali, catat sekarang):
            </p>
            <p class="font-mono mt-1 text-lg font-medium text-ink" data-testid="generated-password">{{ session('generated_password') }}</p>
        </div>
    @endif

    <form wire:submit="save" class="space-y-4 rounded-xl border border-muted/25 p-6">
        <div>
            <label for="name" class="mb-1 block text-sm text-ink">Nama</label>
            <input
                type="text"
                id="name"
                wire:model="name"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm text-ink">Email</label>
            <input
                type="email"
                id="email"
                wire:model="email"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="role" class="mb-1 block text-sm text-ink">Role</label>
            <select
                id="role"
                wire:model="role"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
                <option value="exploration_member">Anggota Eksplorasi</option>
                <option value="execution_member">Anggota Eksekusi</option>
                <option value="admin">Admin</option>
            </select>
            @error('role')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="membership_status" class="mb-1 block text-sm text-ink">Status Keanggotaan</label>
            <select
                id="membership_status"
                wire:model="membership_status"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
            </select>
            @error('membership_status')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            class="w-full rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60"
        >
            Simpan Perubahan
        </button>
    </form>

    <div class="mt-6 rounded-xl border border-muted/25 p-6">
        <h2 class="mb-2 text-sm font-medium text-ink">Reset Password</h2>
        <p class="mb-3 text-xs text-muted">
            Generate password baru untuk akun ini. Password lama langsung tidak berlaku.
        </p>
        <button
            type="button"
            wire:click="resetPassword"
            wire:confirm="Yakin reset password akun ini?"
            class="rounded-lg border border-muted/40 px-4 py-2 text-sm text-ink hover:border-ink"
        >
            Reset Password
        </button>
    </div>

    {{-- Fase 8 Batch 5 (§5.3 "Cabut Akses") -- cuma tampil untuk akun yang
         BENAR-BENAR sedang punya akses Mode Eksekusi aktif. --}}
    @if ($user->dual_mode_status === 'approved')
        <div class="mt-6 rounded-xl border border-danger/30 p-6">
            <h2 class="mb-2 text-sm font-medium text-ink">Akses Mode Ganda</h2>
            <p class="mb-3 text-xs text-muted">
                Akun ini punya akses Mode Eksekusi aktif{{ $user->active_mode === 'execution' ? ' dan sedang dipakai sekarang' : '' }}.
                Mencabut akses akan mengembalikan akun ini ke mode Eksplorasi (mode asal) seketika.
            </p>
            <button
                type="button"
                wire:click="revokeDualModeAccess"
                wire:confirm="Yakin cabut akses Mode Eksekusi untuk {{ $user->name }}? Akun ini akan kembali ke mode Eksplorasi."
                class="rounded-lg border border-danger/40 px-4 py-2 text-sm text-danger hover:bg-danger-soft"
            >
                Cabut Akses
            </button>
        </div>
    @endif
</div>

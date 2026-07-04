<div class="max-w-lg">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="font-display text-2xl font-bold text-ink">Buat Akun Baru</h1>
        <a href="{{ url('/admin/users') }}" class="text-sm text-muted hover:text-ink">Kembali</a>
    </div>

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

        <p class="text-xs text-muted">
            Password awal akan digenerate otomatis dan ditampilkan sekali setelah akun dibuat. Catat dan sampaikan ke anggota secara manual.
        </p>

        <button
            type="submit"
            wire:loading.attr="disabled"
            class="w-full rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60"
        >
            Buat Akun
        </button>
    </form>
</div>

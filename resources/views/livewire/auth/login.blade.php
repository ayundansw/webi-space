<div>
    <h1 class="font-display mb-6 text-center text-xl font-semibold text-ink">Masuk ke WEBI-SPACE</h1>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="email" class="mb-1 block text-sm text-ink">Email</label>
            <input
                type="email"
                id="email"
                wire:model="email"
                autofocus
                autocomplete="username"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm text-ink">Password</label>
            <input
                type="password"
                id="password"
                wire:model="password"
                autocomplete="current-password"
                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
            >
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            class="w-full rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60"
        >
            <span wire:loading.remove>Masuk</span>
            <span wire:loading>Memproses...</span>
        </button>
    </form>

    <p class="mt-6 text-center text-xs text-muted">
        Belum punya akun? Hubungi admin divisi untuk dibuatkan akun.
    </p>
</div>

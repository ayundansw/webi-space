<header class="sticky top-0 z-20 border-b border-muted/40 bg-white shadow-warm-xs">
    {{-- Aksen dekoratif pixel-art, lapisan belakang -- lihat .navbar-pixel-accent di app.css --}}
    <div class="navbar-pixel-accent" aria-hidden="true"></div>

    <div class="relative mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-y-3 px-6 py-4">
        <a href="{{ url('/dashboard') }}" wire:navigate>
            <x-brand.wordmark size="sm" />
        </a>

        @auth
            <div class="flex items-center gap-3">
                <x-shell.mode-badge />

                <livewire:notifications.bell />

                <x-shell.nav-popup />

                <x-shell.account-menu />
            </div>
        @endauth
    </div>
</header>

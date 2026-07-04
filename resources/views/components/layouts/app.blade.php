<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'WEBI-SPACE') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-white text-ink font-sans antialiased">
    <header class="border-b border-muted/25">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-y-3 px-6 py-4">
            <a href="{{ url('/dashboard') }}" class="font-display text-lg font-bold text-ink">WEBI-SPACE</a>

            @auth
                <nav class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                    <span class="text-muted">{{ auth()->user()->name }}</span>

                    @if (auth()->user()->role === 'admin')
                        <a href="{{ url('/admin/dashboard') }}" class="text-ink hover:text-accent">Dashboard</a>
                        <a href="{{ url('/admin/users') }}" class="text-ink hover:text-accent">Manajemen Akun</a>
                        <a href="{{ url('/eksekusi/ideas') }}" class="text-ink hover:text-accent">Project Ideas</a>
                        <a href="{{ url('/eksekusi/projects') }}" class="text-ink hover:text-accent">Proyek</a>
                        <a href="{{ url('/admin/webi') }}" class="text-ink hover:text-accent">Log WEBI</a>
                    @elseif (auth()->user()->role === 'exploration_member')
                        <a href="{{ url('/eksplorasi/dashboard') }}" class="text-ink hover:text-accent">Dashboard</a>
                        <a href="{{ url('/eksplorasi/kurikulum') }}" class="text-ink hover:text-accent">Peta Kurikulum</a>
                        <a href="{{ url('/eksplorasi/resources') }}" class="text-ink hover:text-accent">Referensi</a>
                        <a href="{{ url('/eksplorasi/forum') }}" class="text-ink hover:text-accent">Forum</a>
                        <a href="{{ url('/eksplorasi/webi') }}" class="text-ink hover:text-accent">WEBI</a>
                    @elseif (auth()->user()->role === 'execution_member')
                        <a href="{{ url('/eksekusi/dashboard') }}" class="text-ink hover:text-accent">Dashboard</a>
                        <a href="{{ url('/eksekusi/ideas') }}" class="text-ink hover:text-accent">Project Ideas</a>
                        <a href="{{ url('/eksekusi/projects') }}" class="text-ink hover:text-accent">Proyek</a>
                    @endif

                    <livewire:notifications.bell />

                    <form method="POST" action="{{ url('/logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-muted/40 px-3 py-1.5 text-ink hover:border-ink">
                            Keluar
                        </button>
                    </form>
                </nav>
            @endauth
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-6 py-10">
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>

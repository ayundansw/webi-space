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
    <div class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-8 text-center">
                <span class="font-display text-2xl font-bold text-ink">WEBI-SPACE</span>
            </div>

            <div class="rounded-xl border border-muted/25 bg-white p-6">
                {{ $slot }}
            </div>
        </div>
    </div>
    @livewireScripts
</body>
</html>

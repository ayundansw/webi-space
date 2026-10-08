<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>[DEV] Preview Shell</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-surface p-8 font-sans text-ink antialiased">
    <div class="mx-auto max-w-5xl">
        <div class="mb-8 rounded-xl border-2 border-dashed border-amber-300 bg-amber-50 p-4 text-sm text-ink">
            <strong>Halaman preview developer.</strong> Hanya aktif di environment
            <code class="font-mono">local</code>, tidak masuk navigasi produksi. Dipakai untuk
            verifikasi visual shell baru (navbar + popup menu + breadcrumb, Fase 2 Langkah
            5) untuk ketiga role sekaligus — disposable, akan dihapus setelah verifikasi.
            Klik ikon grid (navigasi) dan avatar (akun) di tiap navbar untuk buka popup-nya
            — sungguhan interaktif, bukan gambar statis. Tiap panel independen (buka satu
            popup tidak ikut membuka punya panel lain).
        </div>

        @foreach ($panels as $role => $panel)
            <div class="mb-10" x-data="{ popupOpen: false }">
                <h1 class="font-display mb-3 text-xl font-bold text-heading">
                    {{ match ($role) {
                        'admin' => 'Admin',
                        'exploration_member' => 'Exploration Member',
                        'execution_member' => 'Execution Member',
                    } }}
                    <span class="font-mono text-xs font-normal text-caption">({{ $panel['user']->name }}, sedang "melihat" halaman list-nya sendiri)</span>
                </h1>

                <div class="overflow-hidden rounded-xl border border-muted/25">
                    {!! $panel['navbarHtml'] !!}
                    {{--
                        `relative` di sini SENGAJA meniru <main> di app.blade.php
                        -- breadcrumb sekarang card mengambang `absolute` yang
                        butuh ancestor `relative` sebagai containing block.
                        Tanpa ini breadcrumb akan "lepas" ke body/viewport,
                        bukan ke area konten panel ini.
                    --}}
                    <div class="relative bg-white p-6 text-sm text-muted">
                        {!! $panel['breadcrumbHtml'] !!}
                        (area konten halaman — tidak direplikasi di preview ini, cukup navbar + breadcrumb)
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @livewireScripts
</body>
</html>

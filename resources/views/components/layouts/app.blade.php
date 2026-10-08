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
<body
    class="flex min-h-screen flex-col bg-white text-ink font-sans antialiased"
    x-data="{ popupOpen: false }"
    x-init="document.addEventListener('livewire:navigated', () => { popupOpen = false })"
>
    {{--
        Bagian A (perbaikan lanjutan): @persist('shell-navbar') DIHAPUS.
        Navbar berisi <x-shell.nav-popup> yang ring aktifnya dihitung
        SERVER-SIDE per request (App\Support\NavigationMatcher, dari route
        saat ini) -- sama persis alasan breadcrumb di bawah SENGAJA tidak
        di-@persist (lihat komentarnya). @persist membekukan markup-nya di
        render pertama; setelah itu wire:navigate cuma menukar {{ $slot }},
        navbar (dan ring aktifnya) tidak pernah dihitung ulang -- persis bug
        yang dilaporkan Aye (pindah ke Dashboard, ring masih di Forum).
        Konsekuensi: <header> sekarang anak langsung <body> tanpa wrapper
        <div x-persist> sama sekali, jadi bug sticky yang dulu di-fix pakai
        `display: contents` (lihat app.css) juga otomatis tidak terjadi lagi
        -- aturan CSS itu ikut dihapus karena tidak ada elemen x-persist
        lagi yang perlu ditarget.
    --}}
    <x-shell.navbar />

    {{--
        Breadcrumb lives INSIDE <main> now (card mengambang kanan-atas,
        bukan baris tersendiri) -- `relative` di <main> di bawah ini adalah
        containing block-nya. Deliberately NOT wrapped in @persist (same
        reasoning as the old sidebar's active-route highlight): it's
        server-computed PHP based on the current route
        (App\Support\Breadcrumbs::trail()), so persisting the DOM would
        freeze it at whatever page first rendered it. Self-guards to
        rendering nothing for guests/routes with no trail
        (Breadcrumbs::trail() returns []), so no @auth wrapper needed here.
    --}}
    <main class="relative mx-auto w-full max-w-7xl flex-1 px-6 py-10">
        <x-shell.breadcrumb />

        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>

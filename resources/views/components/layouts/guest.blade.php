@php
    /**
     * Sapaan maskot (RANCANGAN_FINAL Modul 2 §2.1) -- dipindah ke sini dari
     * login.blade.php (Langkah 7 revisi layout dua-kolom): teks 3 variasi
     * TIDAK berubah, cuma lokasinya -- sekarang bagian kolom kiri
     * "identitas & sambutan" milik shell ini, bukan konten form. Dipilih
     * acak tiap load halaman (murni PHP array_rand, tidak ada state/JS).
     * Role user belum diketahui di titik ini (belum login), jadi teksnya
     * sengaja generik untuk Eksplorasi maupun Eksekusi.
     */
    $greetings = [
        'Yuk masuk, progresmu menunggu, dan aku siap menemani setiap langkahnya!',
        'Satu langkah lagi buat lanjut belajar & berkarya. Ayo masuk, aku di sini kalau butuh teman diskusi!',
        'Selangkah lebih dekat ke level berikutnya, yuk masuk dan kita lanjutkan bareng!',
    ];
    $greeting = $greetings[array_rand($greetings)];
@endphp
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
<body class="relative min-h-screen bg-surface text-ink font-sans antialiased">
    {{-- Motif sirkuit/node ala Retro-Tech, dekoratif, murni CSS -- lihat .guest-bg-circuit di app.css --}}
    <div class="guest-bg-circuit" aria-hidden="true"></div>

    <div class="relative z-10 flex min-h-screen items-center justify-center px-4 py-12">
        {{--
            Dua kolom berdampingan di lg+ (breakpoint sama dengan pivot
            shell lama, konsisten satu app), bertumpuk vertikal di bawah
            lg supaya tidak sempit/terpotong di HP -- kolom kiri (identitas)
            tetap di ATAS, kolom kanan (form) di BAWAH saat bertumpuk,
            sesuai urutan dokumen di markup.
        --}}
        <div class="flex w-full max-w-3xl flex-col items-center gap-6 lg:flex-row lg:items-stretch lg:justify-center">
            {{--
                Kolom kiri: identitas & sambutan. Card TERPISAH (bukan satu
                card besar dibagi 2 kolom internal) supaya konsisten dengan
                pola card yang sudah ada di seluruh aplikasi (mis.
                <x-stat-card>, panel dashboard) -- di sini SETIAP blok
                konten logis selalu jadi card-nya sendiri, tidak ada
                preseden "satu card, dibagi divider internal" di manapun
                di kode sekarang, jadi dua-card-terpisah yang paling
                konsisten, bukan pola baru.
            --}}
            <div class="flex w-full max-w-sm flex-col items-center justify-center gap-4 overflow-hidden rounded-card border border-border bg-white p-6 text-center shadow-warm-md lg:w-80 lg:shrink-0">
                <x-brand.wordmark size="md" />

                <div class="guest-mascot-float">
                    <x-brand.mascot size="lg" />
                </div>

                <p class="text-sm text-ink">{{ $greeting }}</p>
            </div>

            {{-- Kolom kanan: form login saja -- bersih, fokus. --}}
            <div class="w-full max-w-sm rounded-card border border-border bg-white p-6 shadow-warm-md lg:w-96">
                {{ $slot }}
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>

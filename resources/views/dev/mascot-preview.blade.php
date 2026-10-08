<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>[DEV] Preview Maskot</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface p-8 font-sans text-ink antialiased">
    <div class="mx-auto max-w-4xl">
        <div class="mb-6 rounded-xl border-2 border-dashed border-amber-300 bg-amber-50 p-4 text-sm text-ink">
            <strong>Halaman preview developer.</strong> Hanya aktif di environment
            <code class="font-mono">local</code>, tidak masuk navigasi produksi. Dipakai
            untuk verifikasi visual maskot "Boxy Blocky" (Fase 2 Langkah 3) sebelum
            langkah ini dianggap selesai — disposable, akan dihapus setelah verifikasi.
        </div>

        <h1 class="font-display mb-4 text-xl font-bold text-heading">Referensi asli</h1>
        <img src="{{ $referenceImage }}" alt="Referensi Boxy Blocky" class="mb-10 max-w-md rounded-lg border border-muted/25">

        <h1 class="font-display mb-4 text-xl font-bold text-heading">variant="full" — per ukuran</h1>
        <div class="mb-10 flex flex-wrap items-end gap-8 rounded-xl border border-muted/25 bg-white p-6">
            @foreach (['sm', 'md', 'lg', 'xl'] as $size)
                <div class="flex flex-col items-center gap-2">
                    <x-brand.mascot variant="full" :size="$size" />
                    <span class="font-mono text-xs text-caption">size="{{ $size }}"</span>
                </div>
            @endforeach
        </div>

        <h1 class="font-display mb-4 text-xl font-bold text-heading">variant="chat-icon" (~44px)</h1>
        <div class="flex items-end gap-8 rounded-xl border border-muted/25 bg-white p-6">
            <div class="flex flex-col items-center gap-2">
                <x-brand.mascot variant="chat-icon" />
                <span class="font-mono text-xs text-caption">default (44px)</span>
            </div>
            <div class="flex flex-col items-center gap-2">
                <x-brand.mascot variant="chat-icon" size="lg" />
                <span class="font-mono text-xs text-caption">size="lg" (diperbesar buat cek detail)</span>
            </div>
        </div>
    </div>
</body>
</html>

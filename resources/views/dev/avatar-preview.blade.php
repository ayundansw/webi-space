<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>[DEV] Preview Avatar</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface p-8 font-sans text-ink antialiased">
    <div class="mx-auto max-w-5xl">
        <div class="mb-6 rounded-xl border-2 border-dashed border-amber-300 bg-amber-50 p-4 text-sm text-ink">
            <strong>Halaman preview developer.</strong> Hanya aktif di environment
            <code class="font-mono">local</code>, tidak masuk navigasi produksi. Dipakai
            untuk verifikasi visual avatar Fox Eksplorasi + Hewan Eksekusi (Fase 2
            Langkah 4) sebelum langkah ini dianggap selesai — disposable, akan dihapus
            setelah verifikasi.
        </div>

        <h1 class="font-display mb-4 text-xl font-bold text-heading">Fox Eksplorasi (5 Tingkat)</h1>
        <div class="mb-10 space-y-4">
            @foreach ($foxReferences as $tingkat => $referenceImage)
                <div class="flex flex-wrap items-center gap-6 rounded-xl border border-muted/25 bg-white p-6">
                    <div class="flex flex-col items-center gap-2">
                        <img src="{{ $referenceImage }}" alt="Referensi Fox tingkat {{ $tingkat }}" class="h-32 w-auto rounded-lg border border-muted/25">
                        <span class="font-mono text-xs text-caption">referensi</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-avatar.fox :tingkat="$tingkat" size="lg" />
                        <span class="font-mono text-xs text-caption">tingkat="{{ $tingkat }}" size="lg"</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-avatar.fox :tingkat="$tingkat" size="md" />
                        <span class="font-mono text-xs text-caption">size="md"</span>
                    </div>
                </div>
            @endforeach
        </div>

        <h1 class="font-display mb-4 text-xl font-bold text-heading">Hewan Eksekusi (5 Pilihan)</h1>
        <div class="space-y-4">
            @foreach ($eksekusiReferences as $jenis => $referenceImage)
                <div class="flex flex-wrap items-center gap-6 rounded-xl border border-muted/25 bg-white p-6">
                    <div class="flex flex-col items-center gap-2">
                        <img src="{{ $referenceImage }}" alt="Referensi {{ $jenis }}" class="h-32 w-auto rounded-lg border border-muted/25">
                        <span class="font-mono text-xs text-caption">referensi</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-avatar.eksekusi :jenis="$jenis" size="lg" />
                        <span class="font-mono text-xs text-caption">jenis="{{ $jenis }}" size="lg"</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <x-avatar.eksekusi :jenis="$jenis" size="md" />
                        <span class="font-mono text-xs text-caption">size="md"</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>

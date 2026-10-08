<x-layouts.app title="[DEV] Preview Blok Konten">
    <div class="rounded-xl border-2 border-dashed border-amber-300 bg-amber-50 p-4 text-sm text-ink">
        <strong>Halaman preview developer.</strong> Hanya aktif di environment
        <code class="font-mono">local</code>, tidak masuk navigasi produksi.
        Dipakai untuk verifikasi visual seluruh 9 tipe blok konten (2.2.4a) —
        lihat <code class="font-mono">docs/v_2.0/archive/sumber-konsolidasi/content-blocks-spec.md</code>.
        Data di bawah ini dummy, tidak tersimpan di database.
    </div>

    <div class="mt-6">
        <x-content-blocks :blocks="$blocks" />
    </div>
</x-layouts.app>

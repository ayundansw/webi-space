<div class="max-w-xl">
    <a href="{{ url('/eksplorasi/kurikulum') }}" class="text-sm text-muted hover:text-ink">&larr; Kembali ke Peta Kurikulum</a>

    <h1 class="font-display mt-4 text-2xl font-bold text-ink">Checkpoint Modul {{ $checkpoint->module->order_number }}</h1>
    <p class="mt-1 text-sm text-muted">Checklist akhir modul, intermezo refleksi, dan tanggapanmu soal modul ini.</p>

    @if (! $unitsDone)
        <div class="mt-6 rounded-xl border border-muted/25 p-8 text-center">
            <p class="text-lg font-medium text-ink">Belum semua unit selesai</p>
            <p class="mt-2 text-sm text-muted">Selesaikan dulu semua unit di modul ini sebelum mengerjakan checkpoint ya.</p>
        </div>
    @elseif ($completion)
        <div class="mt-6 rounded-xl border border-accent/40 bg-accent-soft/30 p-6">
            <p class="text-sm font-medium text-ink">Checkpoint ini sudah kamu tuntaskan. Keren!</p>
            <p class="mt-1 text-sm text-muted">Kamu dapat {{ $completion->points_awarded }} poin bonus dari checkpoint ini.</p>
        </div>
    @else
        <form wire:submit="submit" class="mt-6 space-y-8">
            <div>
                <h2 class="font-display text-sm font-semibold text-ink">Checklist Akhir Modul</h2>
                <p class="mt-1 text-xs text-muted">Centang yang sudah kamu kuasai. Tidak masalah kalau belum semua, ini bukan ujian.</p>
                <div class="mt-3 space-y-2">
                    @foreach ($checkpoint->checklist_items as $index => $item)
                        <label class="flex items-start gap-2 text-sm text-ink">
                            <input type="checkbox" wire:model="checklistAnswers.{{ $index }}" class="mt-1">
                            <span>{{ $item }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <h2 class="font-display text-sm font-semibold text-ink">Intermezo</h2>
                <p class="mt-1 text-xs text-muted">Jeda reflektif sebelum lanjut ke modul berikutnya.</p>
                <div class="mt-3 space-y-3">
                    @foreach ($checkpoint->intermezo_questions as $index => $question)
                        <div>
                            <label class="mb-1 block text-sm text-ink">{{ $question }}</label>
                            <textarea
                                wire:model="intermezoAnswers.{{ $index }}"
                                rows="2"
                                class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                            ></textarea>
                            @error('intermezoAnswers.'.$index)
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h2 class="font-display text-sm font-semibold text-ink">Form Tanggapan Modul</h2>
                <label class="mb-1 mt-1 block text-xs text-muted">Bagaimana tanggapanmu akan modul ini? Boleh isi apapun: kesan, kesulitan, atau saran.</label>
                <textarea
                    wire:model="formTanggapan"
                    rows="3"
                    class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                ></textarea>
                @error('formTanggapan')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60"
            >
                Selesaikan Checkpoint
            </button>
        </form>
    @endif
</div>

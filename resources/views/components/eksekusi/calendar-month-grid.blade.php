@props(['weeks', 'monthStart', 'onPrevious', 'onNext', 'onToday' => 'goToToday', 'interactive' => false])

{{--
    Fase 7 Batch 2b: shared between the project Kalender tab and the
    personal Kalender page — both pass a $weeks grid built by
    App\Livewire\Eksekusi\Concerns\BuildsCalendarWeeks from
    CalendarService's merged Kegiatan+Acara item list.

    Task 7 (Tahap B, Isi Proyek revisi): navigasi lebih cepat lewat
    `<input type="month">` (wire:model.live="monthInput", properti dengan
    nama SAMA wajib ada di kedua host component -- App\Livewire\Eksekusi\Projects\Tabs\Kalender
    dan App\Livewire\Eksekusi\Kalender) plus tombol "Hari Ini". Warna Acara
    diganti biru (bg-blue-100/text-blue-700/bg-blue-500) supaya jelas beda
    dari Kegiatan (hitam/ink) -- tidak ada token biru di design-tokens,
    dipakai Tailwind biru standar sesuai arahan.

    Task 1 (perf revisi, Kalender+Gantt overhaul): `onDayClick` (nama method
    Livewire) DIHAPUS -- klik tanggal dulu memicu full round-trip
    (openEventForm()) cuma untuk toggle tampilan popup, padahal itu murni
    state UI. `interactive` (boolean) SEKARANG cuma menyalakan/mematikan
    handler Alpine murni yang menyetel `selectedDate`/`showForm` milik host
    (x-data harus ada di elemen pembungkus komponen ini di kedua host blade)
    -- nol panggilan Livewire untuk buka popup, submit form yang tetap lewat
    Livewire seperti biasa.
--}}
<div class="mt-6 rounded-card border border-muted/20 bg-white p-4 shadow-warm-xs">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <button type="button" wire:click="{{ $onPrevious }}" aria-label="Bulan sebelumnya" class="rounded-control border border-muted/40 px-2 py-1 text-xs text-ink hover:border-accent hover:text-accent">&larr;</button>
            <h2 class="font-display w-36 text-center text-sm font-semibold text-ink">{{ $monthStart->copy()->locale('id')->translatedFormat('F Y') }}</h2>
            <button type="button" wire:click="{{ $onNext }}" aria-label="Bulan berikutnya" class="rounded-control border border-muted/40 px-2 py-1 text-xs text-ink hover:border-accent hover:text-accent">&rarr;</button>
        </div>

        <div class="flex items-center gap-2">
            <input type="month" wire:model.live="monthInput" aria-label="Lompat ke bulan" class="rounded-control border border-muted/40 px-2 py-1 text-xs text-ink focus:border-accent focus:outline-none">
            <button type="button" wire:click="{{ $onToday }}" class="rounded-control border border-muted/40 px-2 py-1 text-xs text-ink hover:border-accent hover:text-accent">Hari Ini</button>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-muted">
        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-ink"></span> Kegiatan (deadline task &amp; milestone)</span>
        <span class="flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-blue-500"></span> Acara</span>
    </div>

    <div class="mt-3 grid grid-cols-7 gap-1 text-center font-mono text-[10px] uppercase text-muted">
        <div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div><div>Min</div>
    </div>

    <div class="mt-1 grid grid-cols-7 gap-1">
        @foreach ($weeks as $week)
            @foreach ($week as $day)
                <button
                    type="button"
                    @if ($interactive) @click="selectedDate = '{{ $day['date']->toDateString() }}'; showForm = true" @endif
                    class="min-h-18 rounded-lg border p-1 text-left align-top text-xs transition-colors duration-150 {{ $day['inMonth'] ? 'border-muted/20 bg-white hover:border-accent' : 'border-muted/10 bg-surface/40' }} {{ $day['date']->isToday() ? 'ring-2 ring-accent' : '' }}"
                >
                    <span class="font-mono {{ $day['inMonth'] ? 'text-ink' : 'text-muted' }}">{{ $day['date']->day }}</span>
                    <div class="mt-1 space-y-0.5">
                        @foreach ($day['items']->take(3) as $item)
                            <div class="truncate rounded px-1 py-0.5 text-[10px] {{ $item['category'] === 'kegiatan' ? 'bg-ink/10 text-ink' : 'bg-blue-100 text-blue-700' }}">{{ $item['title'] }}</div>
                        @endforeach
                        @if ($day['items']->count() > 3)
                            <div class="text-[10px] text-muted">+{{ $day['items']->count() - 3 }} lagi</div>
                        @endif
                    </div>
                </button>
            @endforeach
        @endforeach
    </div>
</div>

{{--
    Dibagikan antara Create dan Edit (pola @include yang sama dengan
    _idea-card.blade.php/_track-map.blade.php) — form per tipe soal cukup
    kompleks (repeater multiple_choice/matching/ordering) untuk sepadan
    diekstrak, bukan diduplikasi dua kali.
--}}
<div>
    <label for="question_type" class="mb-1 block text-sm text-ink">Tipe Soal</label>
    <select
        id="question_type"
        wire:model.live="question_type"
        class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
    >
        <option value="multiple_choice">Pilihan Ganda</option>
        <option value="matching">Mencocokkan</option>
        <option value="ordering">Mengurutkan</option>
        <option value="essay">Esai</option>
        <option value="practice">Praktik</option>
    </select>
    @error('question_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label for="question_text" class="mb-1 block text-sm text-ink">Pertanyaan / Instruksi</label>
    <textarea
        id="question_text"
        wire:model="question_text"
        rows="3"
        class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
    ></textarea>
    @error('question_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

@if ($question_type === 'multiple_choice')
    <div>
        <label class="mb-1 block text-sm text-ink">Opsi Jawaban &amp; Kunci</label>
        <p class="mb-2 text-xs text-muted">Tulis opsi bebas, lalu pilih SATU sebagai kunci jawaban lewat radio button di sampingnya.</p>
        <div class="space-y-2">
            @foreach ($mcOptions as $index => $option)
                <div class="flex items-center gap-2">
                    <input
                        type="radio"
                        wire:model="mcCorrectIndex"
                        value="{{ $index }}"
                        aria-label="Jadikan kunci jawaban"
                    >
                    <input
                        type="text"
                        wire:model="mcOptions.{{ $index }}"
                        placeholder="Opsi {{ $index + 1 }}"
                        class="flex-1 rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                    >
                    <button
                        type="button"
                        wire:click="removeMcOption({{ $index }})"
                        @if (count($mcOptions) <= 2) disabled @endif
                        class="shrink-0 text-sm text-red-600 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-30"
                    >Hapus</button>
                </div>
                @error('mcOptions.'.$index) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            @endforeach
        </div>
        <button type="button" wire:click="addMcOption" class="mt-2 rounded-lg border border-muted/40 px-3 py-1.5 text-xs text-ink hover:border-accent">+ Tambah Opsi</button>
        @error('mcOptions') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        @error('mcCorrectIndex') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
@elseif ($question_type === 'matching')
    <div>
        <label class="mb-1 block text-sm text-ink">Pasangan Kiri &harr; Kanan</label>
        <p class="mb-2 text-xs text-muted">Kunci jawaban diturunkan otomatis dari pasangan ini: tidak ada input kunci terpisah.</p>
        <div class="space-y-2">
            @foreach ($matchPairs as $index => $pair)
                <div class="flex items-center gap-2">
                    <input
                        type="text"
                        wire:model="matchPairs.{{ $index }}.left"
                        placeholder="Kiri"
                        class="flex-1 rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                    >
                    <span class="text-muted">&harr;</span>
                    <input
                        type="text"
                        wire:model="matchPairs.{{ $index }}.right"
                        placeholder="Kanan"
                        class="flex-1 rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                    >
                    <button
                        type="button"
                        wire:click="removeMatchPair({{ $index }})"
                        @if (count($matchPairs) <= 1) disabled @endif
                        class="shrink-0 text-sm text-red-600 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-30"
                    >Hapus</button>
                </div>
                @error('matchPairs.'.$index.'.left') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @error('matchPairs.'.$index.'.right') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            @endforeach
        </div>
        <button type="button" wire:click="addMatchPair" class="mt-2 rounded-lg border border-muted/40 px-3 py-1.5 text-xs text-ink hover:border-accent">+ Tambah Pasangan</button>
        @error('matchPairs') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
@elseif ($question_type === 'ordering')
    <div class="rounded-lg border border-warning/40 bg-warning-soft p-3 text-xs text-ink">
        Urutan Kunci Jawaban dan Urutan Tampil ke User HARUS diisi terpisah:
        urutan tampil idealnya diacak dari urutan kunci, supaya soal
        benar-benar menguji (bukan otomatis benar begitu ditampilkan).
    </div>

    <div>
        <label class="mb-1 block text-sm text-ink">1. Urutan Kunci Jawaban (yang benar)</label>
        <div class="space-y-2">
            @foreach ($orderingItems as $index => $item)
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs text-muted">{{ $index + 1 }}.</span>
                    <input
                        type="text"
                        wire:model="orderingItems.{{ $index }}"
                        placeholder="Langkah {{ $index + 1 }}"
                        class="flex-1 rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                    >
                    <button
                        type="button"
                        wire:click="removeOrderingItem({{ $index }})"
                        @if (count($orderingItems) <= 2) disabled @endif
                        class="shrink-0 text-sm text-red-600 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-30"
                    >Hapus</button>
                </div>
                @error('orderingItems.'.$index) <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            @endforeach
        </div>
        <button type="button" wire:click="addOrderingItem" class="mt-2 rounded-lg border border-muted/40 px-3 py-1.5 text-xs text-ink hover:border-accent">+ Tambah Langkah</button>
        @error('orderingItems') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <div class="flex items-center justify-between">
            <label class="mb-1 block text-sm text-ink">2. Urutan Tampil ke User</label>
            <button type="button" wire:click="shuffleDisplayOrder" class="text-xs text-accent hover:underline">Acak Otomatis</button>
        </div>
        <div class="space-y-1">
            @foreach ($orderingDisplayIndexes as $position => $itemIndex)
                <div class="flex items-center gap-2 rounded-lg border border-muted/25 px-3 py-2 text-sm text-ink">
                    <span class="font-mono text-xs text-muted">{{ $position + 1 }}.</span>
                    <span class="flex-1">{{ $orderingItems[$itemIndex] ?? '' }}</span>
                    <button
                        type="button"
                        wire:click="moveDisplayOrder({{ $position }}, 'up')"
                        class="rounded border border-muted/40 px-2 py-0.5 text-xs text-ink hover:border-accent disabled:opacity-30"
                        @disabled($position === 0)
                    >&uarr;</button>
                    <button
                        type="button"
                        wire:click="moveDisplayOrder({{ $position }}, 'down')"
                        class="rounded border border-muted/40 px-2 py-0.5 text-xs text-ink hover:border-accent disabled:opacity-30"
                        @disabled($position === count($orderingDisplayIndexes) - 1)
                    >&darr;</button>
                </div>
            @endforeach
        </div>
    </div>
@elseif (in_array($question_type, ['essay', 'practice']))
    <p class="text-xs text-muted">Tipe ini cuma butuh pertanyaan/instruksi di atas: tidak ada opsi atau kunci jawaban.</p>
@endif

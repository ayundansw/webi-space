<div>
    @if ($mode === 'result')
        <div class="rounded-xl border border-accent/40 bg-accent-soft/30 p-6">
            <p class="text-sm font-medium text-ink">
                Selamat! Kamu mendapatkan {{ $unit->point_value }} poin karena menuntaskan unit ini. Terus jaga semangatmu!
            </p>

            @if (! empty($resultDetails))
                <div class="mt-4 space-y-3">
                    @foreach ($resultDetails as $detail)
                        @if ($detail['is_essay'])
                            <div class="rounded-lg border border-muted/30 bg-white p-3">
                                <p class="text-sm text-ink">{{ $detail['question'] }}</p>
                                <p class="mt-1 text-sm text-ink">Jawabanmu: <span class="font-medium">{{ $detail['selected'] }}</span></p>
                            </div>
                        @elseif ($detail['type'] === 'matching')
                            <div class="rounded-lg border {{ $detail['is_correct'] ? 'border-accent/40' : 'border-muted/30' }} bg-white p-3">
                                <p class="text-sm text-ink">{{ $detail['question'] }}</p>
                                <ul class="mt-2 space-y-1 text-sm">
                                    @foreach ($detail['correct_answer'] as $left => $correctRight)
                                        @php($selectedRight = $detail['selected'][$left] ?? null)
                                        <li class="{{ $selectedRight === $correctRight ? 'text-ink' : 'text-muted' }}">
                                            {{ $left }} &rarr; <span class="font-medium">{{ $selectedRight ?? '(belum dicocokkan)' }}</span>
                                            @if ($selectedRight !== $correctRight)
                                                <span class="text-xs">(harusnya: {{ $correctRight }})</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @elseif ($detail['type'] === 'ordering')
                            <div class="rounded-lg border {{ $detail['is_correct'] ? 'border-accent/40' : 'border-muted/30' }} bg-white p-3">
                                <p class="text-sm text-ink">{{ $detail['question'] }}</p>
                                <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm text-ink">
                                    @foreach ($detail['selected'] as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ol>
                                @unless ($detail['is_correct'])
                                    <p class="mt-2 text-sm text-muted">Urutan yang benar:</p>
                                    <ol class="list-decimal space-y-1 pl-5 text-sm text-muted">
                                        @foreach ($detail['correct_answer'] as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ol>
                                @endunless
                            </div>
                        @else
                            <div class="rounded-lg border {{ $detail['is_correct'] ? 'border-accent/40' : 'border-muted/30' }} bg-white p-3">
                                <p class="text-sm text-ink">{{ $detail['question'] }}</p>
                                <p class="mt-1 text-sm {{ $detail['is_correct'] ? 'text-ink' : 'text-muted' }}">
                                    Jawabanmu: <span class="font-medium">{{ $detail['selected'] }}</span>
                                    {{ $detail['is_correct'] ? '— tepat!' : '— belum tepat' }}
                                </p>
                                @unless ($detail['is_correct'])
                                    <p class="mt-1 text-sm text-ink">Jawaban yang benar: <span class="font-medium">{{ $detail['correct_answer'] }}</span></p>
                                @endunless
                            </div>
                        @endif
                    @endforeach
                </div>

                @if ($resultIsCorrect === false)
                    <p class="mt-3 text-sm text-muted">
                        Ada yang belum tepat, tapi tidak apa-apa &mdash; poinmu tetap tercatat. Mau coba lagi supaya makin paham?
                    </p>
                @endif
            @endif

            <div class="mt-4 flex flex-wrap gap-3">
                @if ($resultIsCorrect === false)
                    <button
                        type="button"
                        wire:click="retry"
                        class="inline-block rounded-lg border border-ink px-4 py-2 text-sm font-medium text-ink hover:bg-ink hover:text-white"
                    >
                        Coba Lagi
                    </button>
                @endif

                @if ($nextUnitId)
                    <a href="{{ url('/eksplorasi/unit/'.$nextUnitId) }}" class="inline-block rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">
                        Lanjut ke {{ $nextUnitTitle }}
                    </a>
                @else
                    <a href="{{ url('/eksplorasi/kurikulum') }}" class="inline-block rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90">
                        Kembali ke Peta Kurikulum
                    </a>
                @endif
            </div>
        </div>
    @elseif (in_array($unit->evaluation_type, ['quiz_multiple_choice', 'quiz_matching', 'quiz_ordering']))
        <form wire:submit="submitQuiz" class="space-y-6">
            @foreach ($unit->evaluations as $question)
                <div>
                    <p class="text-sm font-medium text-ink">{{ $loop->iteration }}. {{ $question->question_text }}</p>
                    @if ($question->question_type === 'essay')
                        <textarea
                            wire:model="quizAnswers.{{ $question->id }}"
                            rows="3"
                            class="mt-2 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                            placeholder="Tulis jawabanmu di sini, tidak ada jawaban salah untuk ini."
                        ></textarea>
                    @elseif ($question->question_type === 'matching')
                        @php($rightOptions = collect($question->options['pairs'])->pluck('right'))
                        <div class="mt-2 space-y-2">
                            @foreach ($question->options['pairs'] as $pair)
                                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-3">
                                    <span class="text-sm text-ink sm:w-1/3">{{ $pair['left'] }}</span>
                                    <select
                                        wire:model="quizAnswers.{{ $question->id }}.{{ $pair['left'] }}"
                                        class="w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none sm:w-2/3"
                                    >
                                        <option value="">Pilih pasangannya&hellip;</option>
                                        @foreach ($rightOptions as $right)
                                            <option value="{{ $right }}">{{ $right }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    @elseif ($question->question_type === 'ordering')
                        <div class="mt-2 space-y-1">
                            @foreach ($quizAnswers[$question->id] ?? $question->options as $index => $item)
                                <div class="flex items-center gap-2 rounded-lg border border-muted/25 px-3 py-2 text-sm text-ink">
                                    <span class="font-mono text-xs text-muted">{{ $index + 1 }}.</span>
                                    <span class="flex-1">{{ $item }}</span>
                                    <button
                                        type="button"
                                        wire:click="moveOrderItem('{{ $question->id }}', {{ $index }}, 'up')"
                                        class="rounded border border-muted/40 px-2 py-0.5 text-xs text-ink hover:border-accent disabled:opacity-30"
                                        @disabled($index === 0)
                                    >&uarr;</button>
                                    <button
                                        type="button"
                                        wire:click="moveOrderItem('{{ $question->id }}', {{ $index }}, 'down')"
                                        class="rounded border border-muted/40 px-2 py-0.5 text-xs text-ink hover:border-accent disabled:opacity-30"
                                        @disabled($index === count($quizAnswers[$question->id] ?? $question->options) - 1)
                                    >&darr;</button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-2 space-y-1">
                            @foreach ($question->options as $option)
                                <label class="flex items-center gap-2 text-sm text-ink">
                                    <input type="radio" wire:model="quizAnswers.{{ $question->id }}" value="{{ $option }}">
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('quizAnswers.'.$question->id)
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60">
                Kirim Jawaban
            </button>
        </form>
    @elseif ($unit->evaluation_type === 'essay' || $unit->evaluation_type === 'practice')
        <form wire:submit="submitFreeText" class="space-y-4">
            <div>
                <p class="text-sm font-medium text-ink">{{ $unit->evaluations->first()?->question_text }}</p>
                <textarea
                    wire:model="freeTextAnswer"
                    rows="4"
                    class="mt-2 w-full rounded-lg border border-muted/40 px-3 py-2 text-sm focus:border-accent focus:outline-none"
                    placeholder="Tulis jawabanmu di sini, tidak ada jawaban salah untuk ini."
                ></textarea>
                @error('freeTextAnswer')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60">
                Kirim Jawaban
            </button>
        </form>
    @elseif ($unit->evaluation_type === 'none')
        <button wire:click="markAsRead" wire:loading.attr="disabled" class="rounded-lg bg-ink px-4 py-2 text-sm font-medium text-white hover:bg-ink/90 disabled:opacity-60">
            Tandai Sudah Selesai Dibaca
        </button>
    @else
        <p class="text-sm text-muted">Tipe evaluasi ini ({{ $unit->evaluation_type }}) belum didukung di versi ini &mdash; menyusul saat konten kurikulum penuh dibangun.</p>
    @endif
</div>

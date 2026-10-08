<?php

namespace App\Livewire\Admin\Curriculum\Units\Evaluations\Concerns;

/**
 * Shared by Create and Edit — per-type sub-forms (repeaters for
 * multiple_choice/matching/ordering, ~150 lines) are complex enough that
 * duplicating them would be real unjustified duplication.
 *
 * GOTCHA: `question_type` below is `unit_evaluations.question_type` (NO
 * `quiz_` prefix) — a DIFFERENT column on a DIFFERENT table from
 * `units.evaluation_type` (WITH the `quiz_` prefix). This trait never
 * touches `units.evaluation_type`.
 */
trait HandlesQuestionForm
{
    public string $question_type = 'multiple_choice';

    public string $question_text = '';

    // --- multiple_choice ---

    /** @var array<int, string> */
    public array $mcOptions = ['', ''];

    public string $mcCorrectIndex = '';

    // --- matching ---

    /** @var array<int, array{left: string, right: string}> */
    public array $matchPairs = [['left' => '', 'right' => '']];

    // --- ordering ---
    // Perbaikan bug (Fase 4 Batch 3, keputusan dikunci di RANCANGAN_FINAL
    // Modul 5 §5.1.C): $orderingItems adalah daftar kanonik (urutan di sini
    // = correct_answer). $orderingDisplayIndexes adalah SUSUNAN TERPISAH
    // (indeks ke $orderingItems) yang jadi urutan tampil ke user (options)
    // — admin menyusunnya lewat moveDisplayOrder()/shuffleDisplayOrder(),
    // independen dari $orderingItems. Disimpan sebagai INDEKS (bukan salinan
    // teks) supaya mengedit teks sebuah item tidak pernah membuat urutan
    // tampil "basi" (menampilkan teks lama) — beda dari bug lama yang
    // menyalin options dari correct_answer secara membabi buta.

    /** @var array<int, string> */
    public array $orderingItems = ['', ''];

    /** @var array<int, int> */
    public array $orderingDisplayIndexes = [0, 1];

    public function addMcOption(): void
    {
        $this->mcOptions[] = '';
    }

    public function removeMcOption(int $index): void
    {
        if (count($this->mcOptions) <= 2) {
            return;
        }

        unset($this->mcOptions[$index]);
        $this->mcOptions = array_values($this->mcOptions);

        if ((string) $index === $this->mcCorrectIndex) {
            $this->mcCorrectIndex = '';
        }
    }

    public function addMatchPair(): void
    {
        $this->matchPairs[] = ['left' => '', 'right' => ''];
    }

    public function removeMatchPair(int $index): void
    {
        if (count($this->matchPairs) <= 1) {
            return;
        }

        unset($this->matchPairs[$index]);
        $this->matchPairs = array_values($this->matchPairs);
    }

    public function addOrderingItem(): void
    {
        $this->orderingItems[] = '';
        $this->orderingDisplayIndexes[] = count($this->orderingItems) - 1;
    }

    public function removeOrderingItem(int $index): void
    {
        if (count($this->orderingItems) <= 2) {
            return;
        }

        unset($this->orderingItems[$index]);
        $this->orderingItems = array_values($this->orderingItems);

        $this->orderingDisplayIndexes = collect($this->orderingDisplayIndexes)
            ->filter(fn (int $i) => $i !== $index)
            ->map(fn (int $i) => $i > $index ? $i - 1 : $i)
            ->values()
            ->all();
    }

    public function moveDisplayOrder(int $position, string $direction): void
    {
        $target = $direction === 'up' ? $position - 1 : $position + 1;

        if ($target < 0 || $target >= count($this->orderingDisplayIndexes)) {
            return;
        }

        [$this->orderingDisplayIndexes[$position], $this->orderingDisplayIndexes[$target]]
            = [$this->orderingDisplayIndexes[$target], $this->orderingDisplayIndexes[$position]];
    }

    /**
     * Convenience per "idealnya diacak dari urutan benar" — a one-click
     * starting point, admin can still fine-tune manually with
     * moveDisplayOrder() afterward.
     */
    public function shuffleDisplayOrder(): void
    {
        shuffle($this->orderingDisplayIndexes);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function typeSpecificRules(): array
    {
        return match ($this->question_type) {
            'multiple_choice' => [
                'mcOptions' => ['required', 'array', 'min:2'],
                'mcOptions.*' => ['required', 'string'],
                'mcCorrectIndex' => ['required', 'integer'],
            ],
            'matching' => [
                'matchPairs' => ['required', 'array', 'min:1'],
                'matchPairs.*.left' => ['required', 'string'],
                'matchPairs.*.right' => ['required', 'string'],
            ],
            'ordering' => [
                'orderingItems' => ['required', 'array', 'min:2'],
                'orderingItems.*' => ['required', 'string'],
            ],
            default => [],
        };
    }

    /**
     * multiple_choice's kunci jawaban needs a bounds check beyond what the
     * `integer` validation rule alone guarantees (a stale index left over
     * from a removed option, for example) — called right after validate()
     * in save(), only for multiple_choice.
     */
    private function multipleChoiceCorrectIndexIsValid(): bool
    {
        return array_key_exists((int) $this->mcCorrectIndex, $this->mcOptions);
    }

    /**
     * @return array{0: mixed, 1: mixed} [options, correct_answer]
     */
    private function buildOptionsAndAnswer(): array
    {
        return match ($this->question_type) {
            'multiple_choice' => [
                array_values($this->mcOptions),
                $this->mcOptions[(int) $this->mcCorrectIndex],
            ],
            // correct_answer diturunkan OTOMATIS dari struktur pasangan itu
            // sendiri (array_column left=>right) — bukan input terpisah yang
            // bisa berbeda/inkonsisten dari pasangan yang diinput admin.
            'matching' => [
                ['pairs' => array_map(fn (array $p) => ['left' => $p['left'], 'right' => $p['right']], $this->matchPairs)],
                array_column($this->matchPairs, 'right', 'left'),
            ],
            'ordering' => [
                array_map(fn (int $i) => $this->orderingItems[$i], $this->orderingDisplayIndexes),
                array_values($this->orderingItems),
            ],
            default => [null, null],
        };
    }

    private function loadTypedFieldsFrom(\App\Models\UnitEvaluation $evaluation): void
    {
        match ($evaluation->question_type) {
            'multiple_choice' => $this->loadMultipleChoice($evaluation),
            'matching' => $this->loadMatching($evaluation),
            'ordering' => $this->loadOrdering($evaluation),
            default => null,
        };
    }

    private function loadMultipleChoice(\App\Models\UnitEvaluation $evaluation): void
    {
        $this->mcOptions = $evaluation->options ?: ['', ''];
        $index = array_search($evaluation->correct_answer, $this->mcOptions, true);
        $this->mcCorrectIndex = $index !== false ? (string) $index : '';
    }

    private function loadMatching(\App\Models\UnitEvaluation $evaluation): void
    {
        $this->matchPairs = $evaluation->options['pairs'] ?? [['left' => '', 'right' => '']];
    }

    /**
     * Reconstructs $orderingDisplayIndexes by matching each displayed
     * `options` string back to its position in `correct_answer` (which
     * becomes $orderingItems) — needed because the DB only stores the two
     * resolved text arrays, not our in-memory index representation. Falls
     * back to identity order if the text can't be cleanly matched back
     * (e.g. legacy buggy rows where options === correct_answer, or the
     * rare case of duplicate item text colliding under array_search).
     */
    private function loadOrdering(\App\Models\UnitEvaluation $evaluation): void
    {
        $this->orderingItems = $evaluation->correct_answer ?: [];
        $displayOptions = $evaluation->options ?: $this->orderingItems;

        $indexes = collect($displayOptions)
            ->map(fn ($text) => array_search($text, $this->orderingItems, true))
            ->filter(fn ($i) => $i !== false)
            ->values()
            ->all();

        $this->orderingDisplayIndexes = count($indexes) === count($this->orderingItems)
            ? $indexes
            : array_keys($this->orderingItems);
    }
}

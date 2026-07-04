<?php

namespace App\Livewire\Eksplorasi;

use App\Models\EvaluationSubmission;
use App\Models\Unit;
use App\Services\Exploration\ProgressService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UnitEvaluation extends Component
{
    public Unit $unit;

    public array $quizAnswers = [];

    public string $freeTextAnswer = '';

    /** 'form' or 'result' */
    public string $mode = 'form';

    public ?bool $resultIsCorrect = null;

    public array $resultDetails = [];

    public ?string $nextUnitId = null;

    public ?string $nextUnitTitle = null;

    public function mount(Unit $unit): void
    {
        $this->unit = $unit;

        // Matching/ordering need a starting shape to bind the form to — matching
        // starts empty (nothing paired yet), ordering starts as the seeded option
        // order so there's something to reorder via moveOrderItem().
        foreach ($unit->evaluations as $question) {
            if ($question->question_type === 'ordering') {
                $this->quizAnswers[$question->id] = $question->options;
            } elseif ($question->question_type === 'matching') {
                $this->quizAnswers[$question->id] = [];
            }
        }

        // Ordered by id (UUIDv7, time-sortable to millisecond precision) rather than
        // submitted_at (second-precision timestamp column) so rapid retries within the
        // same second still resolve to the actual latest attempt.
        $submission = EvaluationSubmission::where('user_id', Auth::id())
            ->where('unit_id', $unit->id)
            ->orderByDesc('id')
            ->first();

        if ($submission) {
            $this->showResultFor($submission);
        }
    }

    /**
     * Reorders a `quiz_ordering` question's current answer by swapping two
     * adjacent items — the closest browser-safe equivalent to drag-and-drop
     * that's fully testable via Livewire::test()->call() without a real
     * browser (this environment has none).
     */
    public function moveOrderItem(string $questionId, int $index, string $direction): void
    {
        $items = $this->quizAnswers[$questionId] ?? [];
        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($targetIndex < 0 || $targetIndex >= count($items)) {
            return;
        }

        [$items[$index], $items[$targetIndex]] = [$items[$targetIndex], $items[$index]];
        $this->quizAnswers[$questionId] = $items;
    }

    public function submitQuiz(ProgressService $progress): void
    {
        $questions = $this->unit->evaluations()->orderBy('sort_order')->get();

        $rules = [];
        foreach ($questions as $question) {
            $rules['quizAnswers.'.$question->id] = match ($question->question_type) {
                'matching', 'ordering' => ['required', 'array', 'min:1'],
                default => ['required', 'string'],
            };
        }

        $this->validate($rules, [
            '*.required' => 'Yuk pilih salah satu jawaban dulu sebelum lanjut.',
        ]);

        $isFirstAttempt = ! EvaluationSubmission::where('user_id', Auth::id())
            ->where('unit_id', $this->unit->id)
            ->exists();

        $allCorrect = true;
        $answersPayload = [];

        foreach ($questions as $question) {
            $selected = $this->quizAnswers[$question->id] ?? null;
            $answersPayload[$question->id] = $selected;

            // Essay-type questions inside a quiz_multiple_choice unit (e.g. Unit 5.8)
            // have no correct_answer to grade against — they only need a non-empty
            // answer (already enforced by the 'required' validation rule above).
            if ($question->question_type === 'essay') {
                continue;
            }

            if (! $this->isCorrectAnswer($question, $selected)) {
                $allCorrect = false;
            }
        }

        $submission = EvaluationSubmission::create([
            'user_id' => Auth::id(),
            'unit_id' => $this->unit->id,
            'answers' => $answersPayload,
            'is_correct' => $allCorrect,
            'points_awarded' => $isFirstAttempt ? $this->unit->point_value : 0,
        ]);

        $progress->completeUnit(Auth::user(), $this->unit);

        $this->showResultFor($submission);
    }

    /**
     * Matching answers are graded key-order-independent (ksort both sides
     * before comparing) — the pairs a user builds while matching don't have
     * a meaningful "order" the way an ordering question's sequence does, so
     * a strict `===` here would wrongly fail a fully-correct-but-differently-
     * ordered answer. Ordering keeps strict `===`: sequence IS the answer.
     */
    private function isCorrectAnswer($question, mixed $selected): bool
    {
        if ($question->question_type === 'matching' && is_array($selected) && is_array($question->correct_answer)) {
            $selectedSorted = $selected;
            $correctSorted = $question->correct_answer;
            ksort($selectedSorted);
            ksort($correctSorted);

            return $selectedSorted === $correctSorted;
        }

        return $selected === $question->correct_answer;
    }

    public function submitFreeText(ProgressService $progress): void
    {
        $this->validate([
            'freeTextAnswer' => ['required', 'string', 'min:3'],
        ], [], [
            'freeTextAnswer' => 'jawaban',
        ]);

        $question = $this->unit->evaluations()->first();

        $submission = EvaluationSubmission::create([
            'user_id' => Auth::id(),
            'unit_id' => $this->unit->id,
            'answers' => [($question?->id ?? 'freetext') => $this->freeTextAnswer],
            'is_correct' => null,
            'points_awarded' => $this->unit->point_value,
        ]);

        $progress->completeUnit(Auth::user(), $this->unit);

        $this->showResultFor($submission);
    }

    public function markAsRead(ProgressService $progress): void
    {
        $progress->completeUnit(Auth::user(), $this->unit);

        $this->mode = 'result';
        $this->resultIsCorrect = null;
        $this->resultDetails = [];
        $this->setNextUnit($progress);
    }

    public function retry(): void
    {
        $this->mode = 'form';
        $this->resultIsCorrect = null;
        $this->resultDetails = [];
    }

    protected function showResultFor(EvaluationSubmission $submission): void
    {
        $this->mode = 'result';
        $this->resultIsCorrect = $submission->is_correct;

        if (in_array($this->unit->evaluation_type, ['quiz_multiple_choice', 'quiz_matching', 'quiz_ordering'], true)) {
            $questions = $this->unit->evaluations()->orderBy('sort_order')->get();

            $this->resultDetails = $questions->map(function ($question) use ($submission) {
                $selected = $submission->answers[$question->id] ?? null;

                if ($question->question_type === 'essay') {
                    return [
                        'type' => 'essay',
                        'question' => $question->question_text,
                        'selected' => $selected,
                        'correct_answer' => null,
                        'is_correct' => null,
                        'is_essay' => true,
                    ];
                }

                return [
                    'type' => $question->question_type,
                    'question' => $question->question_text,
                    'selected' => $selected,
                    'correct_answer' => $question->correct_answer,
                    'is_correct' => $this->isCorrectAnswer($question, $selected),
                    'is_essay' => false,
                ];
            })->all();
        } else {
            $this->resultDetails = [];
        }

        $this->setNextUnit(app(ProgressService::class));
    }

    protected function setNextUnit(ProgressService $progress): void
    {
        $next = $progress->nextUnitFor(Auth::user());
        $this->nextUnitId = $next?->id;
        $this->nextUnitTitle = $next?->title;
    }

    public function render()
    {
        return view('livewire.eksplorasi.unit-evaluation');
    }
}

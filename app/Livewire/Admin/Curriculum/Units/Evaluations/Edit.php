<?php

namespace App\Livewire\Admin\Curriculum\Units\Evaluations;

use App\Livewire\Admin\Curriculum\Units\Evaluations\Concerns\HandlesQuestionForm;
use App\Models\Unit;
use App\Models\UnitEvaluation;
use App\Services\Content\CurriculumReorderService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Kelola Soal')]
class Edit extends Component
{
    use HandlesQuestionForm;

    public Unit $unit;

    public UnitEvaluation $evaluation;

    public string $sort_order = '';

    /**
     * Snapshot of sort_order as of mount() — same reasoning as Units\Edit's
     * originalOrderNumber (needed by CurriculumReorderService::moveToPosition()
     * to compute the shift).
     */
    public int $originalSortOrder = 0;

    public function mount(Unit $unit, UnitEvaluation $evaluation): void
    {
        abort_if($evaluation->unit_id !== $unit->id, 404);

        $this->unit = $unit;
        $this->evaluation = $evaluation;
        $this->sort_order = (string) $evaluation->sort_order;
        $this->originalSortOrder = $evaluation->sort_order;
        $this->question_type = $evaluation->question_type;
        $this->question_text = $evaluation->question_text;

        $this->loadTypedFieldsFrom($evaluation);
    }

    public function save(CurriculumReorderService $reorder): void
    {
        $validated = $this->validate(array_merge([
            'question_type' => ['required', 'in:multiple_choice,matching,ordering,essay,practice'],
            'question_text' => ['required', 'string'],
            'sort_order' => ['required', 'integer', 'min:1'],
        ], $this->typeSpecificRules()));

        if ($validated['question_type'] === 'multiple_choice' && ! $this->multipleChoiceCorrectIndexIsValid()) {
            $this->addError('mcCorrectIndex', 'Pilih salah satu opsi sebagai kunci jawaban.');

            return;
        }

        [$options, $correctAnswer] = $this->buildOptionsAndAnswer();
        $newSortOrder = (int) $validated['sort_order'];

        DB::transaction(function () use ($validated, $reorder, $options, $correctAnswer, $newSortOrder) {
            $reorder->moveToPosition(
                UnitEvaluation::class,
                ['unit_id' => $this->unit->id],
                $this->evaluation->id,
                $this->originalSortOrder,
                $newSortOrder,
                'sort_order',
            );

            $this->evaluation->update([
                'question_type' => $validated['question_type'],
                'question_text' => $validated['question_text'],
                'options' => $options,
                'correct_answer' => $correctAnswer,
                'sort_order' => $newSortOrder,
            ]);
        });

        $this->originalSortOrder = $newSortOrder;

        session()->flash('status', 'Perubahan soal disimpan.');
    }

    public function render()
    {
        return view('livewire.admin.curriculum.units.evaluations.edit');
    }
}

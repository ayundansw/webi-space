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
#[Title('Tambah Soal')]
class Create extends Component
{
    use HandlesQuestionForm;

    public Unit $unit;

    public string $sort_order = '';

    public function mount(Unit $unit): void
    {
        $this->unit = $unit;
    }

    public function save(CurriculumReorderService $reorder): void
    {
        // question_type di sini adalah unit_evaluations.question_type
        // (TANPA prefix quiz_) — beda dari units.evaluation_type. Lihat
        // docblock HandlesQuestionForm untuk detail gotcha ini.
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
        $sortOrder = (int) $validated['sort_order'];

        DB::transaction(function () use ($validated, $reorder, $options, $correctAnswer, $sortOrder) {
            $reorder->makeRoomForNewPosition(UnitEvaluation::class, ['unit_id' => $this->unit->id], $sortOrder, 'sort_order');

            $this->unit->evaluations()->create([
                'question_type' => $validated['question_type'],
                'question_text' => $validated['question_text'],
                'options' => $options,
                'correct_answer' => $correctAnswer,
                'sort_order' => $sortOrder,
            ]);
        });

        session()->flash('status', 'Soal baru berhasil dibuat.');

        $this->redirect('/admin/curriculum/units/'.$this->unit->id.'/evaluations', navigate: false);
    }

    public function render()
    {
        return view('livewire.admin.curriculum.units.evaluations.create');
    }
}

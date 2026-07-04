<?php

namespace Tests\Feature\Exploration;

use App\Livewire\Eksplorasi\UnitEvaluation;
use App\Models\EvaluationSubmission;
use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserUnitProgress;
use Database\Seeders\CurriculumSeeder;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnitEvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExplorationSampleSeeder::class);
    }

    private function member(): User
    {
        return User::create([
            'name' => 'Member',
            'email' => 'member@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
            'membership_status' => 'active',
        ]);
    }

    public function test_quiz_submission_with_all_correct_answers_awards_points_and_marks_correct(): void
    {
        $user = $this->member();
        $unit = Module::where('order_number', 1)->first()->units()->orderBy('order_number')->first();
        $questions = $unit->evaluations()->orderBy('sort_order')->get();

        $component = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit]);

        foreach ($questions as $question) {
            $component->set('quizAnswers.'.$question->id, $question->correct_answer);
        }

        $component->call('submitQuiz');

        $submission = EvaluationSubmission::where('user_id', $user->id)->where('unit_id', $unit->id)->first();
        $this->assertNotNull($submission);
        $this->assertTrue($submission->is_correct);
        $this->assertSame($unit->point_value, $submission->points_awarded);

        $progress = UserUnitProgress::where('user_id', $user->id)->where('unit_id', $unit->id)->first();
        $this->assertSame('completed', $progress->status);
    }

    public function test_quiz_submission_with_wrong_answer_still_awards_points_but_marked_incorrect(): void
    {
        $user = $this->member();
        $unit = Module::where('order_number', 1)->first()->units()->orderBy('order_number')->first();
        $questions = $unit->evaluations()->orderBy('sort_order')->get();

        $component = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit]);

        foreach ($questions as $question) {
            // pick a wrong option deliberately
            $wrong = collect($question->options)->first(fn ($opt) => $opt !== $question->correct_answer);
            $component->set('quizAnswers.'.$question->id, $wrong);
        }

        $component->call('submitQuiz');

        $submission = EvaluationSubmission::where('user_id', $user->id)->where('unit_id', $unit->id)->first();
        $this->assertFalse($submission->is_correct);
        // points still awarded — participation-based, per PRD 3.1.2 philosophy
        $this->assertSame($unit->point_value, $submission->points_awarded);

        $progress = UserUnitProgress::where('user_id', $user->id)->where('unit_id', $unit->id)->first();
        $this->assertSame('completed', $progress->status);
    }

    public function test_essay_submission_auto_approves_with_null_is_correct(): void
    {
        $user = $this->member();
        $unit = Module::where('order_number', 1)->first()->units()->orderBy('order_number')->get()[1]; // essay unit

        Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit])
            ->set('freeTextAnswer', 'Menurutku peran frontend paling menarik karena aku suka hal visual.')
            ->call('submitFreeText');

        $submission = EvaluationSubmission::where('user_id', $user->id)->where('unit_id', $unit->id)->first();
        $this->assertNotNull($submission);
        $this->assertNull($submission->is_correct);
        $this->assertSame($unit->point_value, $submission->points_awarded);
    }

    public function test_practice_submission_auto_approves(): void
    {
        $user = $this->member();
        $unit = Module::where('order_number', 1)->first()->units()->orderBy('order_number')->get()[2]; // practice unit

        Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit])
            ->set('freeTextAnswer', 'Berhasil pasang VS Code, lancar tanpa kendala.')
            ->call('submitFreeText');

        $submission = EvaluationSubmission::where('user_id', $user->id)->where('unit_id', $unit->id)->first();
        $this->assertNotNull($submission);
        $this->assertNull($submission->is_correct);
        $this->assertSame(15, $submission->points_awarded);
    }

    public function test_wrong_quiz_answer_shows_per_question_feedback_and_correct_answer(): void
    {
        $user = $this->member();
        $unit = Module::where('order_number', 1)->first()->units()->orderBy('order_number')->first();
        $questions = $unit->evaluations()->orderBy('sort_order')->get();

        $component = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit]);

        foreach ($questions as $question) {
            $wrong = collect($question->options)->first(fn ($opt) => $opt !== $question->correct_answer);
            $component->set('quizAnswers.'.$question->id, $wrong);
        }

        $component->call('submitQuiz');

        $component->assertSet('resultIsCorrect', false);
        $component->assertSee('belum tepat');
        $component->assertSee('Jawaban yang benar');
        $component->assertSee($questions->first()->correct_answer);
        $component->assertSee('Coba Lagi');
    }

    public function test_correct_quiz_answer_does_not_offer_retry(): void
    {
        $user = $this->member();
        $unit = Module::where('order_number', 1)->first()->units()->orderBy('order_number')->first();
        $questions = $unit->evaluations()->orderBy('sort_order')->get();

        $component = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit]);

        foreach ($questions as $question) {
            $component->set('quizAnswers.'.$question->id, $question->correct_answer);
        }

        $component->call('submitQuiz');

        $component->assertSet('resultIsCorrect', true);
        $component->assertDontSee('Coba Lagi');
    }

    public function test_quiz_retry_after_wrong_answer_does_not_double_award_points(): void
    {
        $user = $this->member();
        $unit = Module::where('order_number', 1)->first()->units()->orderBy('order_number')->first();
        $questions = $unit->evaluations()->orderBy('sort_order')->get();

        $component = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit]);

        foreach ($questions as $question) {
            $wrong = collect($question->options)->first(fn ($opt) => $opt !== $question->correct_answer);
            $component->set('quizAnswers.'.$question->id, $wrong);
        }
        $component->call('submitQuiz');

        // retry with the correct answers this time
        $component->call('retry');
        $component->assertSet('mode', 'form');

        foreach ($questions as $question) {
            $component->set('quizAnswers.'.$question->id, $question->correct_answer);
        }
        $component->call('submitQuiz');

        $component->assertSet('resultIsCorrect', true);

        $this->assertSame(2, EvaluationSubmission::where('user_id', $user->id)->where('unit_id', $unit->id)->count());

        $latest = EvaluationSubmission::where('user_id', $user->id)->where('unit_id', $unit->id)->orderByDesc('id')->first();
        $this->assertTrue($latest->is_correct);
        $this->assertSame(0, $latest->points_awarded);

        $userProgress = \App\Models\UserExplorationProgress::where('user_id', $user->id)->first();
        $this->assertSame($unit->point_value, $userProgress->total_points);
    }

    public function test_reopening_unit_after_wrong_quiz_still_allows_retry(): void
    {
        $user = $this->member();
        $unit = Module::where('order_number', 1)->first()->units()->orderBy('order_number')->first();
        $questions = $unit->evaluations()->orderBy('sort_order')->get();

        $first = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit]);
        foreach ($questions as $question) {
            $wrong = collect($question->options)->first(fn ($opt) => $opt !== $question->correct_answer);
            $first->set('quizAnswers.'.$question->id, $wrong);
        }
        $first->call('submitQuiz');

        // simulate reopening the page (fresh component mount, not the same instance)
        $reopened = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit]);
        $reopened->assertSet('mode', 'result');
        $reopened->assertSet('resultIsCorrect', false);
        $reopened->assertSee('Coba Lagi');
    }

    public function test_mixed_quiz_essay_unit_requires_essay_answer_before_completion(): void
    {
        $user = $this->member();
        $this->seed(CurriculumSeeder::class);
        $unit = Unit::where('title', 'like', 'Menangani Merge Conflict%')->firstOrFail();
        $questions = $unit->evaluations()->orderBy('sort_order')->get();
        $this->assertSame('essay', $questions->last()->question_type);

        $component = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit]);

        // fill only the two multiple-choice questions, leave the essay blank
        foreach ($questions->take(2) as $question) {
            $component->set('quizAnswers.'.$question->id, $question->correct_answer);
        }
        $component->call('submitQuiz');
        $component->assertHasErrors('quizAnswers.'.$questions->last()->id);
        $this->assertDatabaseCount('evaluation_submissions', 0);

        $progress = UserUnitProgress::where('user_id', $user->id)->where('unit_id', $unit->id)->first();
        $this->assertTrue(is_null($progress) || $progress->status !== 'completed');
    }

    public function test_mixed_quiz_essay_unit_completes_once_essay_is_filled(): void
    {
        $user = $this->member();
        $this->seed(CurriculumSeeder::class);
        $unit = Unit::where('title', 'like', 'Menangani Merge Conflict%')->firstOrFail();
        $questions = $unit->evaluations()->orderBy('sort_order')->get();

        $component = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit]);

        foreach ($questions as $question) {
            $answer = $question->question_type === 'essay'
                ? 'Karena masing-masing orang mengubah baris yang sama secara berbeda.'
                : $question->correct_answer;
            $component->set('quizAnswers.'.$question->id, $answer);
        }
        $component->call('submitQuiz');

        // both multiple-choice answers correct, essay ungraded -> overall correct
        $component->assertSet('resultIsCorrect', true);

        $submission = EvaluationSubmission::where('user_id', $user->id)->where('unit_id', $unit->id)->first();
        $this->assertNotNull($submission);
        $this->assertSame($unit->point_value, $submission->points_awarded);

        $progress = UserUnitProgress::where('user_id', $user->id)->where('unit_id', $unit->id)->first();
        $this->assertSame('completed', $progress->status);
    }

    public function test_empty_essay_answer_is_rejected(): void
    {
        $user = $this->member();
        $unit = Module::where('order_number', 1)->first()->units()->orderBy('order_number')->get()[1];

        Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit])
            ->set('freeTextAnswer', '')
            ->call('submitFreeText')
            ->assertHasErrors('freeTextAnswer');

        $this->assertDatabaseCount('evaluation_submissions', 0);
    }
}

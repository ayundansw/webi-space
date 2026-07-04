<?php

namespace Tests\Feature\Exploration;

use App\Livewire\Eksplorasi\UnitEvaluation;
use App\Models\EvaluationSubmission;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserUnitProgress;
use Database\Seeders\CurriculumSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Task 2.7 Batch 2 finding: `quiz_matching` and `quiz_ordering` units — used
 * throughout the REAL curriculum (CurriculumSeeder), including Unit 1.2 and
 * 1.3, the second and third unit in the entire curriculum — had NO form UI at
 * all in unit-evaluation.blade.php. The outer @elseif only matched
 * 'quiz_multiple_choice', so any unit of these two types fell through to the
 * "Tipe evaluasi ini belum didukung" message with no way to answer, and even
 * the server-side `submitQuiz()` validation demanded a `string` answer
 * (matching/ordering answers are arrays) — a real, previously-undiscovered
 * bug that blocked all progress past Unit 1.1 for every real user. Fixed by
 * adding matching (dropdown-per-pair) and ordering (move up/down) form UIs
 * plus order-independent grading for matching answers.
 */
class MatchingAndOrderingEvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CurriculumSeeder::class);
    }

    private function member(): User
    {
        return User::create([
            'name' => 'Member', 'email' => 'member@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ]);
    }

    public function test_matching_question_with_all_correct_pairs_is_graded_correct(): void
    {
        $user = $this->member();
        $unit = Unit::where('title', 'Peran-Peran dalam Tim Development')->firstOrFail();
        $question = $unit->evaluations()->firstOrFail();

        $component = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit]);

        foreach ($question->correct_answer as $left => $right) {
            $component->set("quizAnswers.{$question->id}.{$left}", $right);
        }
        $component->call('submitQuiz');

        $component->assertSet('resultIsCorrect', true);

        $submission = EvaluationSubmission::where('user_id', $user->id)->where('unit_id', $unit->id)->first();
        $this->assertTrue($submission->is_correct);
        $this->assertSame('completed', UserUnitProgress::where('user_id', $user->id)->where('unit_id', $unit->id)->first()->status);
    }

    public function test_matching_pair_order_does_not_affect_correctness(): void
    {
        $user = $this->member();
        $unit = Unit::where('title', 'Peran-Peran dalam Tim Development')->firstOrFail();
        $question = $unit->evaluations()->firstOrFail();

        // submit the correct pairs but in reverse key order — must still grade correct
        $component = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit]);
        foreach (array_reverse($question->correct_answer, true) as $left => $right) {
            $component->set("quizAnswers.{$question->id}.{$left}", $right);
        }
        $component->call('submitQuiz');

        $component->assertSet('resultIsCorrect', true);
    }

    public function test_matching_question_with_one_wrong_pair_is_graded_incorrect_but_still_awards_points(): void
    {
        $user = $this->member();
        $unit = Unit::where('title', 'Peran-Peran dalam Tim Development')->firstOrFail();
        $question = $unit->evaluations()->firstOrFail();

        $component = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit]);
        $wrongRight = array_values($question->correct_answer)[1];
        foreach ($question->correct_answer as $left => $right) {
            $component->set("quizAnswers.{$question->id}.{$left}", $left === array_key_first($question->correct_answer) ? $wrongRight : $right);
        }
        $component->call('submitQuiz');

        $component->assertSet('resultIsCorrect', false);

        $submission = EvaluationSubmission::where('user_id', $user->id)->where('unit_id', $unit->id)->first();
        $this->assertFalse($submission->is_correct);
        $this->assertSame($unit->point_value, $submission->points_awarded);
        $this->assertSame('completed', UserUnitProgress::where('user_id', $user->id)->where('unit_id', $unit->id)->first()->status);
    }

    public function test_ordering_question_move_up_and_down_reorders_items(): void
    {
        $user = $this->member();
        $unit = Unit::where('title', 'Siklus Hidup Pengembangan Software (SDLC)')->firstOrFail();
        $question = $unit->evaluations()->firstOrFail();

        $component = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit]);
        $component->call('moveOrderItem', $question->id, 0, 'down');

        $reordered = $component->get('quizAnswers')[$question->id];
        $this->assertSame($question->options[1], $reordered[0]);
        $this->assertSame($question->options[0], $reordered[1]);
    }

    public function test_ordering_question_correct_sequence_is_graded_correct(): void
    {
        $user = $this->member();
        $unit = Unit::where('title', 'Siklus Hidup Pengembangan Software (SDLC)')->firstOrFail();
        $question = $unit->evaluations()->firstOrFail();

        // component initializes quizAnswers to the seeded (already-correct) order by default
        Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit])
            ->call('submitQuiz')
            ->assertSet('resultIsCorrect', true);
    }

    public function test_ordering_question_wrong_sequence_is_graded_incorrect_but_still_awards_points(): void
    {
        $user = $this->member();
        $unit = Unit::where('title', 'Siklus Hidup Pengembangan Software (SDLC)')->firstOrFail();
        $question = $unit->evaluations()->firstOrFail();

        $component = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit]);
        $component->call('moveOrderItem', $question->id, 0, 'down');
        $component->call('submitQuiz');

        $component->assertSet('resultIsCorrect', false);

        $submission = EvaluationSubmission::where('user_id', $user->id)->where('unit_id', $unit->id)->first();
        $this->assertFalse($submission->is_correct);
        $this->assertSame($unit->point_value, $submission->points_awarded);
    }

    public function test_matching_and_ordering_units_render_a_real_answerable_form_not_the_unsupported_message(): void
    {
        $matchingUnit = Unit::where('title', 'Peran-Peran dalam Tim Development')->firstOrFail();
        $orderingUnit = Unit::where('title', 'Siklus Hidup Pengembangan Software (SDLC)')->firstOrFail();
        $user = $this->member();

        $matchingHtml = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $matchingUnit])->html();
        $orderingHtml = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $orderingUnit])->html();

        $this->assertStringNotContainsString('belum didukung di versi ini', $matchingHtml);
        $this->assertStringNotContainsString('belum didukung di versi ini', $orderingHtml);
    }
}

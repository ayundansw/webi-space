<?php

namespace Tests\Feature\Admin\Curriculum;

use App\Livewire\Admin\Curriculum\Units\Evaluations\Create;
use App\Livewire\Admin\Curriculum\Units\Evaluations\Edit;
use App\Models\Module;
use App\Models\Unit;
use App\Models\UnitEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 4 Batch 3 (Kelola Evaluasi). GOTCHA per task brief: `question_type`
 * asserted here is `unit_evaluations.question_type` (multiple_choice/
 * matching/ordering/essay/practice, NO `quiz_` prefix) — never
 * `units.evaluation_type` (a different column on a different table, WITH
 * the prefix). Every fixture below uses `evaluation_type => 'none'` on the
 * Unit itself since this test suite never exercises that column.
 */
class EvaluationManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function member(string $role): User
    {
        return User::create([
            'name' => 'Member', 'email' => $role.'@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => $role, 'membership_status' => 'active',
        ]);
    }

    private function unit(): Unit
    {
        $module = Module::create(['order_number' => 1, 'title' => 'Modul 1', 'description' => 'D', 'level_number' => 1]);

        return Unit::create([
            'module_id' => $module->id, 'order_number' => 1, 'title' => 'Unit Uji',
            'content' => '', 'estimated_minutes' => 15, 'unit_type' => 'concept',
            'point_value' => 10, 'evaluation_type' => 'none',
        ]);
    }

    private function questionAt(Unit $unit, int $order, string $type = 'essay'): UnitEvaluation
    {
        return $unit->evaluations()->create([
            'question_type' => $type,
            'question_text' => "Soal urutan {$order}",
            'sort_order' => $order,
        ]);
    }

    private function assertNoDuplicateSortOrdersInUnit(Unit $unit): void
    {
        $numbers = UnitEvaluation::where('unit_id', $unit->id)->pluck('sort_order')->all();
        $this->assertSame(count($numbers), count(array_unique($numbers)), 'Ditemukan sort_order soal yang duplikat dalam satu unit.');
    }

    public function test_non_admin_cannot_access_evaluation_management(): void
    {
        $unit = $this->unit();
        $exploration = $this->member('exploration_member');
        $execution = $this->member('execution_member');

        foreach ([$exploration, $execution] as $user) {
            $this->actingAs($user)->get("/admin/curriculum/units/{$unit->id}/evaluations")->assertForbidden();
            $this->actingAs($user)->get("/admin/curriculum/units/{$unit->id}/evaluations/create")->assertForbidden();
        }
    }

    public function test_admin_can_create_a_multiple_choice_question(): void
    {
        $admin = $this->admin();
        $unit = $this->unit();

        Livewire::actingAs($admin)
            ->test(Create::class, ['unit' => $unit])
            ->set('sort_order', '1')
            ->set('question_type', 'multiple_choice')
            ->set('question_text', 'Apa itu HTML?')
            ->set('mcOptions', ['Bahasa markup', 'Bahasa pemrograman', 'Database'])
            ->set('mcCorrectIndex', '0')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect("/admin/curriculum/units/{$unit->id}/evaluations");

        $question = UnitEvaluation::where('question_text', 'Apa itu HTML?')->firstOrFail();
        $this->assertSame('multiple_choice', $question->question_type);
        $this->assertSame(['Bahasa markup', 'Bahasa pemrograman', 'Database'], $question->options);
        $this->assertSame('Bahasa markup', $question->correct_answer);
    }

    public function test_multiple_choice_requires_a_correct_answer_to_be_selected(): void
    {
        $admin = $this->admin();
        $unit = $this->unit();

        Livewire::actingAs($admin)
            ->test(Create::class, ['unit' => $unit])
            ->set('sort_order', '1')
            ->set('question_type', 'multiple_choice')
            ->set('question_text', 'Apa itu HTML?')
            ->set('mcOptions', ['A', 'B'])
            ->set('mcCorrectIndex', '')
            ->call('save')
            ->assertHasErrors('mcCorrectIndex');

        $this->assertDatabaseCount('unit_evaluations', 0);
    }

    /**
     * PALING RUMIT (task brief): correct_answer HARUS diturunkan otomatis
     * dari struktur pasangan, bukan input terpisah admin bisa salah ketik.
     */
    public function test_admin_can_create_a_matching_question_with_auto_derived_correct_answer(): void
    {
        $admin = $this->admin();
        $unit = $this->unit();

        Livewire::actingAs($admin)
            ->test(Create::class, ['unit' => $unit])
            ->set('sort_order', '1')
            ->set('question_type', 'matching')
            ->set('question_text', 'Cocokkan istilah dengan definisinya.')
            ->set('matchPairs', [
                ['left' => 'HTML', 'right' => 'Struktur'],
                ['left' => 'CSS', 'right' => 'Tampilan'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $question = UnitEvaluation::where('question_text', 'Cocokkan istilah dengan definisinya.')->firstOrFail();
        $this->assertSame('matching', $question->question_type);
        $this->assertEquals([
            'pairs' => [
                ['left' => 'HTML', 'right' => 'Struktur'],
                ['left' => 'CSS', 'right' => 'Tampilan'],
            ],
        ], $question->options);
        // Diturunkan otomatis dari $matchPairs — TIDAK ADA field input terpisah untuk ini.
        $this->assertEquals(['HTML' => 'Struktur', 'CSS' => 'Tampilan'], $question->correct_answer);
    }

    /**
     * Perbaikan bug utama batch ini: options (urutan tampil) dan
     * correct_answer (kunci) HARUS beda, diisi lewat dua tindakan admin
     * yang terpisah (bukan satu input yang otomatis disalin ke keduanya).
     */
    public function test_admin_can_create_an_ordering_question_with_separately_shuffled_display_order(): void
    {
        $admin = $this->admin();
        $unit = $this->unit();

        Livewire::actingAs($admin)
            ->test(Create::class, ['unit' => $unit])
            ->set('sort_order', '1')
            ->set('question_type', 'ordering')
            ->set('question_text', 'Susun langkah-langkah ini.')
            ->set('orderingItems', ['Langkah 1', 'Langkah 2', 'Langkah 3'])
            // Admin menyusun urutan TAMPIL secara terpisah, di sini dibalik total.
            ->set('orderingDisplayIndexes', [2, 1, 0])
            ->call('save')
            ->assertHasNoErrors();

        $question = UnitEvaluation::where('question_text', 'Susun langkah-langkah ini.')->firstOrFail();
        $this->assertSame(['Langkah 1', 'Langkah 2', 'Langkah 3'], $question->correct_answer);
        $this->assertSame(['Langkah 3', 'Langkah 2', 'Langkah 1'], $question->options);
        // Bug lama: options === correct_answer. Sekarang harus beda.
        $this->assertNotEquals($question->options, $question->correct_answer);
    }

    public function test_move_display_order_swaps_adjacent_items_without_touching_correct_order(): void
    {
        $admin = $this->admin();
        $unit = $this->unit();

        $component = Livewire::actingAs($admin)
            ->test(Create::class, ['unit' => $unit])
            // Setting both directly mirrors the synced state addOrderingItem()
            // normally builds up one click at a time (set() alone doesn't run
            // that sync logic, so both must be given explicitly here).
            ->set('orderingItems', ['A', 'B', 'C'])
            ->set('orderingDisplayIndexes', [0, 1, 2])
            ->call('moveDisplayOrder', 0, 'down');

        $this->assertSame([1, 0, 2], $component->get('orderingDisplayIndexes'));
        $this->assertSame(['A', 'B', 'C'], $component->get('orderingItems'));
    }

    public function test_admin_can_create_an_essay_question_without_options_or_correct_answer(): void
    {
        $admin = $this->admin();
        $unit = $this->unit();

        Livewire::actingAs($admin)
            ->test(Create::class, ['unit' => $unit])
            ->set('sort_order', '1')
            ->set('question_type', 'essay')
            ->set('question_text', 'Jelaskan pemahamanmu soal HTML.')
            ->call('save')
            ->assertHasNoErrors();

        $question = UnitEvaluation::where('question_text', 'Jelaskan pemahamanmu soal HTML.')->firstOrFail();
        $this->assertSame('essay', $question->question_type);
        $this->assertNull($question->options);
        $this->assertNull($question->correct_answer);
    }

    public function test_admin_can_create_a_practice_question_without_options_or_correct_answer(): void
    {
        $admin = $this->admin();
        $unit = $this->unit();

        Livewire::actingAs($admin)
            ->test(Create::class, ['unit' => $unit])
            ->set('sort_order', '1')
            ->set('question_type', 'practice')
            ->set('question_text', 'Buat halaman HTML sederhana.')
            ->call('save')
            ->assertHasNoErrors();

        $question = UnitEvaluation::where('question_text', 'Buat halaman HTML sederhana.')->firstOrFail();
        $this->assertNull($question->options);
        $this->assertNull($question->correct_answer);
    }

    public function test_creating_a_question_at_an_occupied_position_shifts_only_that_units_questions(): void
    {
        $admin = $this->admin();
        $unitA = $this->unit();
        $unitB = $this->unit();
        $questions = [];
        for ($i = 1; $i <= 3; $i++) {
            $questions[$i] = $this->questionAt($unitA, $i);
        }
        $foreignQuestion = $this->questionAt($unitB, 2);

        Livewire::actingAs($admin)
            ->test(Create::class, ['unit' => $unitA])
            ->set('sort_order', '2')
            ->set('question_type', 'essay')
            ->set('question_text', 'Soal Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, UnitEvaluation::where('question_text', 'Soal Baru')->first()->sort_order);
        $this->assertSame(3, $questions[2]->fresh()->sort_order);
        $this->assertSame(4, $questions[3]->fresh()->sort_order);
        $this->assertSame(1, $questions[1]->fresh()->sort_order);
        // a different unit's question at the same numeric position is untouched.
        $this->assertSame(2, $foreignQuestion->fresh()->sort_order);
        $this->assertNoDuplicateSortOrdersInUnit($unitA);
    }

    public function test_admin_can_edit_a_question(): void
    {
        $admin = $this->admin();
        $unit = $this->unit();
        $question = $this->questionAt($unit, 1);

        Livewire::actingAs($admin)
            ->test(Edit::class, ['unit' => $unit, 'evaluation' => $question])
            ->set('question_text', 'Teks Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Teks Baru', $question->fresh()->question_text);
    }

    public function test_editing_loads_multiple_choice_fields_correctly(): void
    {
        $admin = $this->admin();
        $unit = $this->unit();
        $question = $unit->evaluations()->create([
            'question_type' => 'multiple_choice',
            'question_text' => 'Soal MC',
            'options' => ['X', 'Y', 'Z'],
            'correct_answer' => 'Y',
            'sort_order' => 1,
        ]);

        $component = Livewire::actingAs($admin)->test(Edit::class, ['unit' => $unit, 'evaluation' => $question]);

        $this->assertSame(['X', 'Y', 'Z'], $component->get('mcOptions'));
        $this->assertSame('1', $component->get('mcCorrectIndex'));
    }

    public function test_editing_a_question_from_a_different_unit_is_rejected_with_404(): void
    {
        $admin = $this->admin();
        $unitA = $this->unit();
        $unitB = $this->unit();
        $questionOfB = $this->questionAt($unitB, 1);

        $this->actingAs($admin)
            ->get("/admin/curriculum/units/{$unitA->id}/evaluations/{$questionOfB->id}/edit")
            ->assertNotFound();
    }

    public function test_editing_a_question_to_move_it_later_shifts_the_range_back(): void
    {
        $admin = $this->admin();
        $unit = $this->unit();
        $questions = [];
        for ($i = 1; $i <= 5; $i++) {
            $questions[$i] = $this->questionAt($unit, $i);
        }

        Livewire::actingAs($admin)
            ->test(Edit::class, ['unit' => $unit, 'evaluation' => $questions[1]])
            ->set('sort_order', '4')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(4, $questions[1]->fresh()->sort_order);
        $this->assertSame(1, $questions[2]->fresh()->sort_order);
        $this->assertSame(2, $questions[3]->fresh()->sort_order);
        $this->assertSame(3, $questions[4]->fresh()->sort_order);
        $this->assertSame(5, $questions[5]->fresh()->sort_order);
        $this->assertNoDuplicateSortOrdersInUnit($unit);
    }

    public function test_admin_can_delete_a_question(): void
    {
        $admin = $this->admin();
        $unit = $this->unit();
        $question = $this->questionAt($unit, 1);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Curriculum\Units\Evaluations\Index::class, ['unit' => $unit])
            ->call('delete', $question->id);

        $this->assertDatabaseMissing('unit_evaluations', ['id' => $question->id]);
    }

    public function test_a_unit_can_have_mixed_question_types(): void
    {
        $admin = $this->admin();
        $unit = $this->unit();

        Livewire::actingAs($admin)->test(Create::class, ['unit' => $unit])
            ->set('sort_order', '1')->set('question_type', 'multiple_choice')
            ->set('question_text', 'Q1')->set('mcOptions', ['A', 'B'])->set('mcCorrectIndex', '0')
            ->call('save')->assertHasNoErrors();

        Livewire::actingAs($admin)->test(Create::class, ['unit' => $unit])
            ->set('sort_order', '2')->set('question_type', 'essay')
            ->set('question_text', 'Q2')
            ->call('save')->assertHasNoErrors();

        $this->assertSame(2, $unit->evaluations()->count());
        $this->assertSame(['multiple_choice', 'essay'], $unit->evaluations()->orderBy('sort_order')->pluck('question_type')->all());
    }
}

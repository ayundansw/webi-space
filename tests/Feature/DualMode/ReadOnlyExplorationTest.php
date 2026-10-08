<?php

namespace Tests\Feature\DualMode;

use App\Livewire\Eksplorasi\CheckpointShow;
use App\Livewire\Eksplorasi\Forum\Create as ForumCreate;
use App\Livewire\Eksplorasi\Forum\Show as ForumShow;
use App\Livewire\Eksplorasi\PetaKurikulum;
use App\Livewire\Eksplorasi\Resources\Index as ResourcesIndex;
use App\Livewire\Eksplorasi\UnitEvaluation;
use App\Livewire\Eksplorasi\UnitShow;
use App\Models\Checkpoint;
use App\Models\ForumThread;
use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserExplorationProgress;
use App\Models\UserUnitProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 8 Batch 3 (docs/v_2.0/archive/sumber-konsolidasi/RANCANGAN_FINAL_WEBI-SPACE_v2.md §2.2.A —
 * "Origin Eksekusi -> Mode Eksplorasi, BEBAS tanpa persetujuan, TAPI
 * READ-ONLY"). RECON_fase8_mode_ganda.md flagged this as the single
 * riskiest point in Fase 8: route middleware alone can't separate read
 * from write when both live on the same route. This file's most important
 * test is test_no_progress_record_is_ever_created_for_a_read_only_execution_member
 * — direct proof the recon's bug is actually closed, not just guarded.
 */
class ReadOnlyExplorationTest extends TestCase
{
    use RefreshDatabase;

    private function executionMember(string $email = 'exec@example.test'): User
    {
        return User::create([
            'name' => 'Executor', 'email' => $email,
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);
    }

    private function explorationMember(string $email = 'explorer@example.test'): User
    {
        return User::create([
            'name' => 'Explorer', 'email' => $email,
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ]);
    }

    /**
     * @return array{0: Module, 1: Unit, 2: Unit, 3: Unit, 4: Checkpoint}
     */
    private function curriculumFixtures(): array
    {
        $module1 = Module::create(['order_number' => 1, 'title' => 'Modul 1', 'description' => 'D', 'level_number' => 1]);

        $quizUnit = Unit::create([
            'module_id' => $module1->id, 'order_number' => 1, 'title' => 'Unit Kuis',
            'content' => 'Materi kuis.', 'estimated_minutes' => 15, 'unit_type' => 'concept',
            'point_value' => 10, 'evaluation_type' => 'quiz_multiple_choice',
        ]);
        $quizUnit->evaluations()->create([
            'question_type' => 'multiple_choice', 'question_text' => 'Soal?',
            'options' => ['A', 'B'], 'correct_answer' => 'A', 'sort_order' => 1,
        ]);

        $essayUnit = Unit::create([
            'module_id' => $module1->id, 'order_number' => 2, 'title' => 'Unit Essay',
            'content' => 'Materi essay.', 'estimated_minutes' => 15, 'unit_type' => 'concept',
            'point_value' => 10, 'evaluation_type' => 'essay',
        ]);
        $essayUnit->evaluations()->create([
            'question_type' => 'essay', 'question_text' => 'Jelaskan?', 'sort_order' => 1,
        ]);

        $readUnit = Unit::create([
            'module_id' => $module1->id, 'order_number' => 3, 'title' => 'Unit Baca Saja',
            'content' => 'Materi baca saja.', 'estimated_minutes' => 15, 'unit_type' => 'concept',
            'point_value' => 10, 'evaluation_type' => 'none',
        ]);

        $checkpoint = Checkpoint::create([
            'module_id' => $module1->id,
            'checklist_items' => ['Item 1'],
            'intermezo_questions' => ['Pertanyaan 1'],
        ]);

        return [$module1, $quizUnit, $essayUnit, $readUnit, $checkpoint];
    }

    // ------------------------------------------------------------------
    // 1. Akses BACA — semua halaman yang dibuka
    // ------------------------------------------------------------------

    public function test_execution_member_can_read_every_route_opened_this_batch(): void
    {
        [, $quizUnit, , , $checkpoint] = $this->curriculumFixtures();
        $user = $this->executionMember();

        $urls = [
            '/eksplorasi/kurikulum',
            "/eksplorasi/unit/{$quizUnit->id}",
            "/eksplorasi/checkpoint/{$checkpoint->id}",
            '/eksplorasi/resources',
            '/eksplorasi/webi',
            '/eksplorasi/forum',
        ];

        foreach ($urls as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_a_read_only_user_sees_the_notice_banner_on_peta_kurikulum_and_unit_show(): void
    {
        [, $quizUnit] = $this->curriculumFixtures();
        $user = $this->executionMember();

        $this->actingAs($user)->get('/eksplorasi/kurikulum')
            ->assertSee('menjelajah Eksplorasi');

        $this->actingAs($user)->get("/eksplorasi/unit/{$quizUnit->id}")
            ->assertSee('menjelajah Eksplorasi');
    }

    public function test_a_real_exploration_member_never_sees_the_read_only_banner(): void
    {
        [, $quizUnit] = $this->curriculumFixtures();
        $user = $this->explorationMember();

        $this->actingAs($user)->get('/eksplorasi/kurikulum')
            ->assertDontSee('menjelajah Eksplorasi');
    }

    // ------------------------------------------------------------------
    // 2. Sistem lock DILEWATI untuk mode baca
    // ------------------------------------------------------------------

    public function test_every_unit_shows_unlocked_for_a_read_only_user_even_across_multiple_modules(): void
    {
        [$module1] = $this->curriculumFixtures();
        $module2 = Module::create(['order_number' => 2, 'title' => 'Modul 2', 'description' => 'D', 'level_number' => 1]);
        $unitInModule2 = Unit::create([
            'module_id' => $module2->id, 'order_number' => 1, 'title' => 'Unit Modul 2',
            'content' => 'C', 'estimated_minutes' => 15, 'unit_type' => 'concept',
            'point_value' => 10, 'evaluation_type' => 'none',
        ]);

        $user = $this->executionMember();

        // Sanity check first: a FRESH exploration_member (zero progress)
        // WOULD find Modul 2 itself locked (Modul 1 not completed yet) --
        // the blade collapses a locked module to a generic placeholder
        // message and never even renders its units individually, proving
        // the fixture is a meaningful test of the bypass, not a trivial
        // always-unlocked-anyway case.
        $freshExplorer = $this->explorationMember();
        $html = $this->actingAs($freshExplorer)->get('/eksplorasi/kurikulum')->getContent();
        $this->assertStringContainsString('Selesaikan dulu modul sebelumnya', $html, 'Sanity check failed: expected the fresh exploration_member to see Modul 2 locked.');
        $this->assertStringNotContainsString($unitInModule2->title, $html, 'Sanity check failed: a locked module should never render its units at all.');

        $readOnlyHtml = $this->actingAs($user)->get('/eksplorasi/kurikulum')->getContent();
        $this->assertStringNotContainsString('Selesaikan dulu modul sebelumnya', $readOnlyHtml, 'No module may show as locked for a read-only user.');
        $this->assertStringContainsString($unitInModule2->title, $readOnlyHtml, 'Modul 2 unit must be visible (module treated as active) for a read-only user.');

        // Directly opening that "should be locked" unit page must also
        // succeed (not redirected to a locked-unit message).
        $this->actingAs($user)->get("/eksplorasi/unit/{$unitInModule2->id}")
            ->assertOk()
            ->assertDontSee('Unit ini belum bisa dibuka');
    }

    // ------------------------------------------------------------------
    // 3. TEST PALING PENTING: nol UserExplorationProgress/UserUnitProgress
    //    baru untuk execution_member mode baca
    // ------------------------------------------------------------------

    public function test_no_progress_record_is_ever_created_for_a_read_only_execution_member(): void
    {
        [, $quizUnit, $essayUnit, $readUnit, $checkpoint] = $this->curriculumFixtures();
        $user = $this->executionMember();

        $this->assertSame(0, UserExplorationProgress::count());
        $this->assertSame(0, UserUnitProgress::count());

        // Visit Peta Kurikulum (calls ensureProgress() for a real
        // exploration_member -- must NOT for this user).
        $this->actingAs($user)->get('/eksplorasi/kurikulum')->assertOk();
        $this->assertSame(0, UserExplorationProgress::count(), 'PetaKurikulum must not create a progress row.');

        // Open THREE different units (recordUnitOpened() is the exact bug
        // recon found -- writes UserUnitProgress just from opening a page).
        foreach ([$quizUnit, $essayUnit, $readUnit] as $unit) {
            $this->actingAs($user)->get("/eksplorasi/unit/{$unit->id}")->assertOk();
        }
        $this->assertSame(0, UserUnitProgress::count(), 'Opening unit pages must not create UserUnitProgress rows.');
        $this->assertSame(0, UserExplorationProgress::count(), 'Opening unit pages must not create a UserExplorationProgress row either.');

        // Open the checkpoint page too, for completeness.
        $this->actingAs($user)->get("/eksplorasi/checkpoint/{$checkpoint->id}")->assertOk();
        $this->assertSame(0, UserExplorationProgress::count());
        $this->assertSame(0, UserUnitProgress::count());

        // Final sweep: literally zero rows in either table, for ANY user,
        // after all this browsing.
        $this->assertSame(0, UserExplorationProgress::query()->count());
        $this->assertSame(0, UserUnitProgress::query()->count());
    }

    public function test_a_real_exploration_member_still_gets_normal_progress_tracking_unaffected(): void
    {
        [, $quizUnit] = $this->curriculumFixtures();
        $user = $this->explorationMember();

        $this->actingAs($user)->get('/eksplorasi/kurikulum')->assertOk();
        $this->assertSame(1, UserExplorationProgress::where('user_id', $user->id)->count(), 'Regression: a real exploration_member must still get ensureProgress().');

        $this->actingAs($user)->get("/eksplorasi/unit/{$quizUnit->id}")->assertOk();
        $this->assertTrue(
            UserUnitProgress::where('user_id', $user->id)->where('unit_id', $quizUnit->id)->where('status', 'in_progress')->exists(),
            'Regression: a real exploration_member must still get recordUnitOpened().'
        );
    }

    // ------------------------------------------------------------------
    // 4. GUARD LEVEL-AKSI — SETIAP titik submit ditolak, satu-satu
    // ------------------------------------------------------------------

    public function test_submitQuiz_is_rejected_for_a_read_only_user(): void
    {
        [, $quizUnit] = $this->curriculumFixtures();
        $user = $this->executionMember();
        $question = $quizUnit->evaluations()->first();

        Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $quizUnit])
            ->set("quizAnswers.{$question->id}", 'A')
            ->call('submitQuiz')
            ->assertForbidden();

        $this->assertSame(0, \App\Models\EvaluationSubmission::count());
    }

    public function test_submitFreeText_is_rejected_for_a_read_only_user(): void
    {
        [, , $essayUnit] = $this->curriculumFixtures();
        $user = $this->executionMember();

        Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $essayUnit])
            ->set('freeTextAnswer', 'Jawaban bebas.')
            ->call('submitFreeText')
            ->assertForbidden();

        $this->assertSame(0, \App\Models\EvaluationSubmission::count());
    }

    public function test_markAsRead_is_rejected_for_a_read_only_user(): void
    {
        [, , , $readUnit] = $this->curriculumFixtures();
        $user = $this->executionMember();

        Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $readUnit])
            ->call('markAsRead')
            ->assertForbidden();

        $this->assertSame(0, UserUnitProgress::count());
    }

    public function test_checkpoint_submit_is_rejected_for_a_read_only_user(): void
    {
        [$module1, , , , $checkpoint] = $this->curriculumFixtures();
        $user = $this->executionMember();

        Livewire::actingAs($user)->test(CheckpointShow::class, ['checkpoint' => $checkpoint])
            ->set('formTanggapan', 'Tanggapan saya.')
            ->set('intermezoAnswers.0', 'Jawaban.')
            ->call('submit')
            ->assertForbidden();

        $this->assertSame(0, \App\Models\CheckpointCompletion::count());
    }

    public function test_resources_submit_is_rejected_for_a_read_only_user(): void
    {
        $this->curriculumFixtures();
        $user = $this->executionMember();

        Livewire::actingAs($user)->test(ResourcesIndex::class)
            ->set('title', 'Tutorial Bagus')
            ->set('url', 'https://example.com/tutorial')
            ->call('submit')
            ->assertForbidden();

        $this->assertSame(0, \App\Models\LearningResource::count());
    }

    public function test_forum_create_save_is_rejected_for_a_read_only_user_via_direct_component_call(): void
    {
        $user = $this->executionMember();

        Livewire::actingAs($user)->test(ForumCreate::class)
            ->set('title', 'Thread Baru')
            ->set('content', 'Isi')
            ->set('target', 'peer')
            ->call('save')
            ->assertForbidden();

        $this->assertSame(0, ForumThread::count());
    }

    /**
     * The exact trap recon warned about: Forum\Create is embedded as a
     * MODAL inside Forum\Index (`<livewire:eksplorasi.forum.create />`,
     * see that class's own docblock) even though /eksplorasi/forum/create
     * itself stays closed to read-only mode. Confirms (1) a read-only user
     * really can load Forum\Index where that modal lives, and (2) the
     * modal component is genuinely embedded in what they receive — the
     * PRECEDING test already proves save() itself refuses regardless of
     * entry path (Livewire calls hit the component class directly, not a
     * route, so there's only one save() to guard, not a separate
     * "modal version").
     */
    public function test_forum_index_page_a_read_only_user_can_open_genuinely_embeds_the_create_modal_component(): void
    {
        $user = $this->executionMember();

        $html = $this->actingAs($user)->get('/eksplorasi/forum')->assertOk()->getContent();

        $this->assertStringContainsString('wire:name="eksplorasi.forum.create"', $html);
    }

    public function test_forum_reply_is_rejected_for_a_read_only_user(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
        $module = Module::create(['order_number' => 1, 'title' => 'Modul 1', 'description' => 'D', 'level_number' => 1]);
        $thread = ForumThread::create([
            'module_id' => $module->id, 'portal' => 'exploration', 'created_by' => $admin->id,
            'title' => 'Thread', 'content' => 'Isi', 'target' => 'peer',
        ]);
        $user = $this->executionMember();

        Livewire::actingAs($user)->test(ForumShow::class, ['thread' => $thread])
            ->set('replyContent', 'Balasan saya')
            ->call('reply')
            ->assertForbidden();

        $this->assertSame(0, \App\Models\ForumReply::count());
    }

    // ------------------------------------------------------------------
    // 5. Rute yang TIDAK dibuka tetap tertutup
    // ------------------------------------------------------------------

    public function test_routes_deliberately_not_opened_stay_closed_to_execution_member(): void
    {
        $user = $this->executionMember();

        $stillClosed = [
            '/eksplorasi/dashboard',
            '/eksplorasi/praktik',
            '/eksplorasi/forum/create',
        ];

        foreach ($stillClosed as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }

    // ------------------------------------------------------------------
    // 6. WEBI TETAP fungsional penuh (satu-satunya pengecualian tulis)
    // ------------------------------------------------------------------

    public function test_webi_chat_still_accepts_a_message_from_a_read_only_user(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Halo!']]]]],
            ], 200),
        ]);

        $user = $this->executionMember();

        Livewire::actingAs($user)->test(\App\Livewire\Eksplorasi\Webi\Chat::class)
            ->set('messageText', 'Halo WEBI')
            ->call('sendMessage')
            ->assertSet('errorMessage', null);

        $this->assertTrue(\App\Models\Message::where('content', 'Halo WEBI')->exists());
    }
}

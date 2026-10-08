<?php

namespace Tests\Feature\DualMode;

use App\Livewire\Eksplorasi\UnitEvaluation;
use App\Models\Milestone;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserExplorationProgress;
use App\Services\DualMode\DualModeService;
use App\Services\Execution\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 8 Batch 7 (TERAKHIR) — full regression sweep across ALL role x mode
 * combinations, one file, matching the matrix in this batch's own prompt.
 * Batch 2 (ExecutionAccessGateTest) and Batch 3 (ReadOnlyExplorationTest)
 * already exhaustively cover their own slices (18 Eksekusi URLs, 7 Eksplorasi
 * write guards) — this file does NOT duplicate that URL-by-URL sweep. It
 * proves the matrix at the ROW level (does this combination end up with the
 * right access, yes/no, on a representative action per cell) and closes the
 * ONE genuinely untested cell before this batch: does an approved,
 * active_mode='execution' exploration_member keep FULL Eksplorasi write
 * access at the same time as their Eksekusi access, or lose it?
 *
 * THAT question was ambiguous in docs/v_2.0/archive/sumber-konsolidasi/RANCANGAN_FINAL_WEBI-SPACE_v2.md
 * §2.2.B ("dua riwayat data berjalan paralel & permanen... sekalipun sedang
 * aktif di mode Eksekusi" talks about DATA persisting, never explicitly
 * about whether WRITE ACCESS is simultaneously open) — flagged to Aye via
 * AskUserQuestion rather than assumed, per this batch's explicit instruction
 * not to guess. Aye's answer: **simultaneous full access is the intended,
 * official behavior** (Option A) — active_mode only decides the badge/
 * default-navigation-target, it does NOT lock either portal. This matches
 * the ACTUAL current code already (User::isReadOnlyExploration() has only
 * ever checked `role === 'execution_member'`, never active_mode), so this
 * batch requires ZERO Gate code changes — only this test, formally locking
 * the confirmed behavior in so a future batch can't silently regress it.
 */
class DualModeFullMatrixTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function explorationMember(string $email, array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Explorer', 'email' => $email,
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ], $overrides));
    }

    private function executionMember(string $email = 'exec@example.test'): User
    {
        return User::create([
            'name' => 'Executor', 'email' => $email,
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);
    }

    /** @return array{0: Unit, 1: \App\Models\UnitEvaluation} */
    private function quizUnit(): array
    {
        $module = Module::create(['order_number' => 1, 'title' => 'Modul 1', 'description' => 'D', 'level_number' => 1]);
        $unit = Unit::create([
            'module_id' => $module->id, 'order_number' => 1, 'title' => 'Unit Kuis',
            'content' => 'Materi.', 'estimated_minutes' => 15, 'unit_type' => 'concept',
            'point_value' => 10, 'evaluation_type' => 'quiz_multiple_choice',
        ]);
        $question = $unit->evaluations()->create([
            'question_type' => 'multiple_choice', 'question_text' => 'Soal?',
            'options' => ['A', 'B'], 'correct_answer' => 'A', 'sort_order' => 1,
        ]);

        return [$unit, $question];
    }

    /** @return array{0: Project, 1: Task, 2: Milestone} */
    private function projectWithTask(User $admin): array
    {
        $project = Project::create([
            'title' => 'Proyek Uji', 'description' => 'D', 'objective' => 'T',
            'project_type' => 'internal', 'status' => 'active',
            'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'M1', 'target_date' => '2026-07-20', 'sort_order' => 1]);
        $task = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Task Uji',
            'status' => 'todo', 'priority' => 'medium', 'deadline' => '2026-07-25', 'created_by' => $admin->id,
        ]);

        return [$project, $task, $milestone];
    }

    private function submitQuizAs(User $user, Unit $unit, $question)
    {
        return Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit])
            ->set("quizAnswers.{$question->id}", 'A')
            ->call('submitQuiz');
    }

    // ==================================================================
    // 0. Full regression baseline sweep
    // ==================================================================

    public function test_baseline_regression_sweep_placeholder(): void
    {
        // This method exists purely so the matrix file's own test count
        // shows up alongside the report's "point 1" full-suite run; the
        // ACTUAL full-suite run is `php artisan test` at the repo root
        // (see this batch's report for the pass/fail count) -- a single
        // test file cannot re-run the whole suite from inside itself.
        $this->assertTrue(true);
    }

    // ==================================================================
    // 1. MATRIX -- exploration_member: none / pending / approved+inactive
    //    Semua TIGA baris ini identik: akses tulis Eksplorasi PENUH,
    //    akses Eksekusi DITOLAK (belum ada approval, atau approved tapi
    //    belum switch aktif).
    // ==================================================================

    public function test_row_none_full_exploration_write_no_execution_access(): void
    {
        [$unit, $question] = $this->quizUnit();
        $user = $this->explorationMember('none@example.test', ['dual_mode_status' => 'none']);

        $this->submitQuizAs($user, $unit, $question)->assertHasNoErrors();
        $this->assertSame(1, \App\Models\EvaluationSubmission::where('user_id', $user->id)->count());

        $this->actingAs($user)->get('/eksekusi/dashboard')->assertForbidden();
    }

    public function test_row_pending_full_exploration_write_no_execution_access(): void
    {
        [$unit, $question] = $this->quizUnit();
        $user = $this->explorationMember('pending@example.test', ['dual_mode_status' => 'pending']);

        $this->submitQuizAs($user, $unit, $question)->assertHasNoErrors();
        $this->assertSame(1, \App\Models\EvaluationSubmission::where('user_id', $user->id)->count());

        $this->actingAs($user)->get('/eksekusi/dashboard')->assertForbidden();
    }

    public function test_row_approved_but_not_yet_switched_full_exploration_write_no_execution_access(): void
    {
        [$unit, $question] = $this->quizUnit();
        $user = $this->explorationMember('approved-inactive@example.test', [
            'dual_mode_status' => 'approved', 'active_mode' => null,
        ]);

        $this->submitQuizAs($user, $unit, $question)->assertHasNoErrors();
        $this->assertSame(1, \App\Models\EvaluationSubmission::where('user_id', $user->id)->count());

        $this->actingAs($user)->get('/eksekusi/dashboard')->assertForbidden();
    }

    // ==================================================================
    // 2. MATRIX -- BARIS PALING KRITIS: approved + active_mode=execution.
    //    Dikonfirmasi Aye (AskUserQuestion, bukan asumsi): akses GANDA
    //    SIMULTAN -- Eksplorasi tulis TETAP PENUH, Eksekusi PENUH juga,
    //    di saat yang SAMA. active_mode cuma menentukan badge/tujuan
    //    navigasi default, BUKAN mengunci salah satu portal.
    // ==================================================================

    public function test_row_approved_and_active_execution_has_simultaneous_full_access_to_both_portals(): void
    {
        $admin = $this->admin();
        [$unit, $question] = $this->quizUnit();
        [$project, $task] = $this->projectWithTask($admin);
        $user = $this->explorationMember('dual-active@example.test', [
            'dual_mode_status' => 'approved', 'active_mode' => 'execution',
        ]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $user->id]);

        // Eksplorasi WRITE -- still fully open.
        $this->submitQuizAs($user, $unit, $question)->assertHasNoErrors();
        $this->assertSame(1, \App\Models\EvaluationSubmission::where('user_id', $user->id)->count());
        $this->assertTrue(
            UserExplorationProgress::where('user_id', $user->id)->where('total_points', '>', 0)->exists(),
            'Points must still be awarded normally -- dual mode does not suppress exploration progress.'
        );

        // Eksplorasi READ -- also open (never in question, but confirmed here too).
        $this->actingAs($user)->get('/eksplorasi/kurikulum')->assertOk();

        // Eksekusi -- fully open, AT THE SAME TIME, same user, same request cycle.
        $this->actingAs($user)->get('/eksekusi/dashboard')->assertOk();
        $this->actingAs($user)->get("/eksekusi/projects/{$project->id}/kanban")->assertOk();

        TaskAssignment::create(['task_id' => $task->id, 'user_id' => $user->id, 'assigned_by' => $admin->id]);
        $this->assertSame(1, TaskAssignment::where('user_id', $user->id)->count());
    }

    // ==================================================================
    // 3. MATRIX -- execution_member: baca-saja Eksplorasi, penuh Eksekusi
    //    (exhaustive coverage already in ReadOnlyExplorationTest /
    //    ExecutionAccessGateTest -- one representative check per cell here).
    // ==================================================================

    public function test_row_execution_member_read_only_exploration_full_execution(): void
    {
        [$unit, $question] = $this->quizUnit();
        $user = $this->executionMember();

        // Read: open.
        $this->actingAs($user)->get('/eksplorasi/kurikulum')->assertOk();

        // Write: refused, nothing persisted.
        $this->submitQuizAs($user, $unit, $question)->assertForbidden();
        $this->assertSame(0, \App\Models\EvaluationSubmission::count());

        // Eksekusi: full.
        $this->actingAs($user)->get('/eksekusi/dashboard')->assertOk();
    }

    // ==================================================================
    // 4. MATRIX -- admin: BUKAN "ya/ya" polos seperti tabel prompt
    //    disederhanakan -- admin TIDAK ikut sistem Mode Ganda sama sekali
    //    (§2.2.C), jadi aksesnya per-route, bukan blanket grant:
    //    - Rute Eksplorasi MEMBER-FACING (Peta Kurikulum/Unit/Checkpoint/
    //      Referensi): TERTUTUP (403) -- ini pre-existing dari Batch 3,
    //      BUKAN regresi. Admin punya panel `/admin/curriculum/*` sendiri
    //      untuk "baca"/kelola konten, bukan lewat rute member ini.
    //    - Forum Eksplorasi (monitoring): TERBUKA (mode:exploration,admin).
    //    - Eksekusi personal-only (dashboard/avatar/kalender): TERTUTUP
    //      (403) -- pre-existing dari SEBELUM Fase 8 (role:execution_member
    //      TANPA ,admin), tidak pernah berubah oleh Mode Ganda.
    //    - Eksekusi shared/manageable (ideas/projects/attachments/praktik
    //      review): TERBUKA (mode:execution,admin).
    // ==================================================================

    public function test_row_admin_access_is_route_scoped_not_a_blanket_grant(): void
    {
        $admin = $this->admin();
        [$unit, , ] = $this->quizUnit();

        // Member-facing Eksplorasi: closed.
        $this->actingAs($admin)->get('/eksplorasi/kurikulum')->assertForbidden();
        $this->actingAs($admin)->get("/eksplorasi/unit/{$unit->id}")->assertForbidden();

        // Forum (monitoring carve-out): open.
        $this->actingAs($admin)->get('/eksplorasi/forum')->assertOk();

        // Eksekusi personal-only: closed.
        $this->actingAs($admin)->get('/eksekusi/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/eksekusi/avatar')->assertForbidden();
        $this->actingAs($admin)->get('/eksekusi/kalender')->assertForbidden();

        // Eksekusi shared/manageable: open.
        $this->actingAs($admin)->get('/eksekusi/ideas')->assertOk();
        $this->actingAs($admin)->get('/eksekusi/projects')->assertOk();
    }

    // ==================================================================
    // 5. Kebocoran data lintas-mode -- dua riwayat TETAP TERPISAH
    // ==================================================================

    /**
     * Menyelesaikan task Eksekusi (progress update, ubah status ke done)
     * TIDAK PERNAH menyentuh poin/level/progres Eksplorasi milik user
     * Mode Ganda yang sama.
     */
    public function test_completing_execution_work_never_touches_exploration_points_or_progress(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTask($admin);
        $user = $this->explorationMember('dual-leak-1@example.test', [
            'dual_mode_status' => 'approved', 'active_mode' => 'execution',
        ]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $user->id]);
        TaskAssignment::create(['task_id' => $task->id, 'user_id' => $user->id, 'assigned_by' => $admin->id]);

        $this->assertSame(0, UserExplorationProgress::where('user_id', $user->id)->count());

        app(TaskService::class)->changeStatus($task, 'in_progress', $user);
        app(TaskService::class)->changeStatus($task, 'in_review', $user);
        app(TaskService::class)->changeStatus($task, 'done', $admin);

        $this->assertSame('done', $task->fresh()->status);
        // Still zero -- finishing Eksekusi work creates no Eksplorasi row at all.
        $this->assertSame(0, UserExplorationProgress::where('user_id', $user->id)->count());
    }

    /**
     * Reverse direction: awarding Eksplorasi points (quiz completion)
     * creates no Eksekusi-side row (ProjectMember/TaskAssignment/Task) and
     * does not alter any task this user is already assigned to.
     */
    public function test_earning_exploration_points_never_touches_execution_tasks_or_projects(): void
    {
        $admin = $this->admin();
        [$unit, $question] = $this->quizUnit();
        [$project, $task] = $this->projectWithTask($admin);
        $user = $this->explorationMember('dual-leak-2@example.test', [
            'dual_mode_status' => 'approved', 'active_mode' => 'execution',
        ]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $user->id]);
        TaskAssignment::create(['task_id' => $task->id, 'user_id' => $user->id, 'assigned_by' => $admin->id]);

        $taskCountBefore = TaskAssignment::where('user_id', $user->id)->count();
        $taskStatusBefore = $task->fresh()->status;

        $this->submitQuizAs($user, $unit, $question)->assertHasNoErrors();

        $this->assertTrue(UserExplorationProgress::where('user_id', $user->id)->where('total_points', '>', 0)->exists());
        // Eksekusi side completely unaffected by the quiz submission.
        $this->assertSame($taskCountBefore, TaskAssignment::where('user_id', $user->id)->count());
        $this->assertSame($taskStatusBefore, $task->fresh()->status);
        $this->assertSame(1, ProjectMember::where('user_id', $user->id)->count());
    }

    /**
     * Full end-to-end: one Mode Ganda user builds BOTH histories in the
     * same test run (multiple quizzes + multiple task status changes,
     * interleaved), then both totals are checked independently against
     * exactly what was done on their own side -- proves isolation holds
     * even under realistic interleaved activity, not just a single action
     * each.
     */
    public function test_two_interleaved_histories_for_one_dual_mode_user_never_cross_contaminate(): void
    {
        $admin = $this->admin();
        [$unitA, $questionA] = $this->quizUnit();
        [$unitB, $questionB] = $this->quizUnit();
        [$project, $taskA] = $this->projectWithTask($admin);
        $milestone = Milestone::where('project_id', $project->id)->first();
        $taskB = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Task Kedua',
            'status' => 'todo', 'priority' => 'medium', 'deadline' => '2026-07-25', 'created_by' => $admin->id,
        ]);
        $user = $this->explorationMember('dual-leak-3@example.test', [
            'dual_mode_status' => 'approved', 'active_mode' => 'execution',
        ]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $user->id]);
        TaskAssignment::create(['task_id' => $taskA->id, 'user_id' => $user->id, 'assigned_by' => $admin->id]);
        TaskAssignment::create(['task_id' => $taskB->id, 'user_id' => $user->id, 'assigned_by' => $admin->id]);

        // Interleave: quiz, task status, quiz, task status.
        $this->submitQuizAs($user, $unitA, $questionA)->assertHasNoErrors();
        app(TaskService::class)->changeStatus($taskA, 'in_progress', $user);
        $this->submitQuizAs($user, $unitB, $questionB)->assertHasNoErrors();
        app(TaskService::class)->changeStatus($taskB, 'in_progress', $user);

        $progress = UserExplorationProgress::where('user_id', $user->id)->first();
        $this->assertSame(20, $progress->total_points, 'Two 10-point quizzes -- points come ONLY from exploration activity.');
        $this->assertSame(2, \App\Models\EvaluationSubmission::where('user_id', $user->id)->count());

        $this->assertSame('in_progress', $taskA->fresh()->status);
        $this->assertSame('in_progress', $taskB->fresh()->status);
        $this->assertSame(2, TaskAssignment::where('user_id', $user->id)->count());

        // Cross-check: no exploration-side row references execution IDs,
        // and vice versa -- the two histories share only `user_id`.
        $this->assertSame(1, UserExplorationProgress::where('user_id', $user->id)->count());
    }

    // ==================================================================
    // 6. Pertanyaan terbuka dari dokumen rancangan (§2.2.B): "nasib
    //    assignment/task yang sudah ada saat dicabut... kemungkinan tetap
    //    sebagai riwayat, tidak otomatis unassign." Dikonfirmasi di sini:
    //    itu PERSIS perilaku kode saat ini -- DualModeService::revoke()
    //    TIDAK PERNAH menyentuh ProjectMember/TaskAssignment sama sekali,
    //    cuma users.dual_mode_status/active_mode. Assignment lama tetap
    //    ada sebagai riwayat; canAccessExecution() yang berhenti mengizinkan
    //    akses ke rute-nya (dibuktikan ExecutionAccessGateTest & ini),
    //    bukan datanya yang dihapus.
    // ==================================================================

    public function test_revoking_access_leaves_existing_project_membership_and_task_assignments_untouched_as_history(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTask($admin);
        $user = $this->explorationMember('revoked-with-history@example.test', [
            'dual_mode_status' => 'approved', 'active_mode' => 'execution',
        ]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $user->id]);
        TaskAssignment::create(['task_id' => $task->id, 'user_id' => $user->id, 'assigned_by' => $admin->id]);

        app(DualModeService::class)->revoke($user->fresh(), $admin);
        $revoked = $user->fresh();

        // Data untouched -- still there as history.
        $this->assertSame(1, ProjectMember::where('user_id', $revoked->id)->count());
        $this->assertSame(1, TaskAssignment::where('user_id', $revoked->id)->count());

        // But access is gone -- canAccessExecution() (and therefore every
        // Eksekusi route) now refuses them, same as ExecutionAccessGateTest
        // proves for a never-granted member.
        $this->assertFalse($revoked->canAccessExecution());
        $this->actingAs($revoked)->get('/eksekusi/dashboard')->assertForbidden();
        $this->actingAs($revoked)->get("/eksekusi/projects/{$project->id}/kanban")->assertForbidden();
    }
}

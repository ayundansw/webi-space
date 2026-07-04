<?php

namespace Tests\Feature\Integration;

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Users\Create as UsersCreate;
use App\Livewire\Eksekusi\Ideas\Approve as IdeasApprove;
use App\Livewire\Eksekusi\Ideas\Create as IdeasCreate;
use App\Livewire\Eksekusi\Projects\Show as ProjectsShow;
use App\Livewire\Eksekusi\Tasks\Create as TasksCreate;
use App\Livewire\Eksekusi\Tasks\Show as TasksShow;
use App\Livewire\Eksplorasi\CheckpointShow;
use App\Livewire\Eksplorasi\Dashboard as EksplorasiDashboard;
use App\Livewire\Eksplorasi\UnitEvaluation;
use App\Livewire\Eksplorasi\Webi\Chat as WebiChat;
use App\Models\Checkpoint;
use App\Models\Module;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectIdea;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\CurriculumSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Task 2.7 Batch 2: one full cross-system smoke test, from a genuinely clean
 * slate (DatabaseSeeder's real seeding path, `app:create-admin` for the first
 * account — the exact sequence a real deploy would follow), driven entirely
 * through real routes/Livewire components, never the service layer directly.
 */
class FullSystemSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CurriculumSeeder::class);
    }

    public function test_full_system_smoke_test(): void
    {
        // ---- Bootstrap: first admin via the real production seeding path ----
        $this->artisan('app:create-admin')
            ->expectsQuestion('Nama admin', 'Ayunda')
            ->expectsQuestion('Email admin', 'ayunda@webi-space.test')
            ->expectsQuestion('Password (minimal 8 karakter, tidak ditampilkan di layar)', 'adminpass123')
            ->expectsQuestion('Ulangi password', 'adminpass123')
            ->assertExitCode(0);

        $admin = User::where('email', 'ayunda@webi-space.test')->firstOrFail();

        // ---- Admin creates 3 accounts, one per role ----
        Livewire::actingAs($admin)->test(UsersCreate::class)
            ->set('name', 'Admin Kedua')->set('email', 'admin2@webi-space.test')->set('role', 'admin')->call('save');

        Livewire::actingAs($admin)->test(UsersCreate::class)
            ->set('name', 'Agitsa')->set('email', 'agitsa@webi-space.test')->set('role', 'exploration_member')->call('save');

        Livewire::actingAs($admin)->test(UsersCreate::class)
            ->set('name', 'Bilal')->set('email', 'bilal@webi-space.test')->set('role', 'execution_member')->call('save');

        $this->assertSame(4, User::count()); // bootstrap admin + 3 just created

        $exploration = User::where('email', 'agitsa@webi-space.test')->firstOrFail();
        $execution = User::where('email', 'bilal@webi-space.test')->firstOrFail();

        // ================= EKSPLORASI: work Module 1 through its checkpoint =================
        $module1 = Module::where('order_number', 1)->firstOrFail();
        $units = $module1->units()->orderBy('order_number')->get();

        foreach ($units as $unit) {
            $this->completeUnitAsMember($exploration, $unit);
        }

        $checkpoint = Checkpoint::where('module_id', $module1->id)->firstOrFail();
        Livewire::actingAs($exploration)->test(CheckpointShow::class, ['checkpoint' => $checkpoint])
            ->set('intermezoAnswers.0', 'Bagian yang paling baru buatku adalah SDLC.')
            ->set('formTanggapan', 'Modul ini seru dan mudah diikuti.')
            ->call('submit');

        $expectedPoints = $units->sum('point_value') + 25;
        $this->assertSame(
            $expectedPoints,
            $exploration->fresh()->explorationProgress->total_points,
            'Total points must equal the real sum of unit point_values plus the 25pt checkpoint bonus — no double counting, no placeholder number.',
        );

        $this->assertTrue(
            Notification::where('recipient_id', $exploration->id)->where('type', 'checkpoint_completed')->exists(),
            'Completing the checkpoint must produce a real notification.',
        );

        // ---- WEBI: recommendation grounded in this member's REAL current unit ----
        $currentUnit = $exploration->fresh()->explorationProgress->currentUnit;
        $this->assertNotNull($currentUnit, 'Completing Module 1 + checkpoint must advance the member to a real next unit.');

        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [[
                'text' => "Keren, Modul 1 sudah tuntas! Lanjut ke \"{$currentUnit->title}\" ya.\n[REKOMENDASI_UNIT:{$currentUnit->id}]",
            ]]]]],
        ], 200)]);

        $chat = Livewire::actingAs($exploration)->test(WebiChat::class)
            ->set('messageText', 'Aku sudah selesai Modul 1, lanjut ke mana?')
            ->call('sendMessage');

        $chat->assertDontSee('REKOMENDASI_UNIT');
        $chat->assertSee('Rekomendasi Unit');
        $this->assertStringContainsString('/eksplorasi/unit/'.$currentUnit->id, $chat->html());

        // ================= EKSEKUSI: idea -> approve -> task -> done =================
        Livewire::actingAs($execution)->test(IdeasCreate::class)
            ->set('title', 'Redesain Landing Page Divisi')
            ->set('description', 'Perbarui tampilan landing page supaya lebih modern.')
            ->set('purpose', 'Meningkatkan citra divisi ke calon anggota baru.')
            ->call('save');

        $idea = ProjectIdea::where('title', 'Redesain Landing Page Divisi')->firstOrFail();
        $this->assertSame('draft', $idea->status);

        Livewire::actingAs($admin)->test(IdeasApprove::class, ['idea' => $idea])
            ->set('projectType', 'internal')
            ->set('startDate', '2026-07-05')
            ->set('targetEndDate', '2026-08-05')
            ->call('save');

        $project = Project::where('title', 'Redesain Landing Page Divisi')->firstOrFail();
        $this->assertSame('approved', $idea->fresh()->status);

        Livewire::actingAs($admin)->test(ProjectsShow::class, ['project' => $project])
            ->set('newMemberId', $execution->id)
            ->call('addMember');

        Livewire::actingAs($admin)->test(ProjectsShow::class, ['project' => $project])
            ->set('milestoneTitle', 'Tahap 1: Desain')
            ->set('milestoneTargetDate', '2026-07-20')
            ->call('addMilestone');

        $milestone = $project->fresh()->milestones()->firstOrFail();

        Livewire::actingAs($admin)->test(TasksCreate::class, ['project' => $project])
            ->set('milestoneId', $milestone->id)
            ->set('title', 'Buat mockup halaman utama')
            ->set('priority', 'medium')
            ->set('deadline', '2026-07-15')
            ->set('assigneeIds', [$execution->id])
            ->call('save');

        $task = $project->fresh()->tasks()->firstOrFail();

        $this->assertTrue(
            Notification::where('recipient_id', $execution->id)->where('type', 'task_assigned')->exists(),
            'Assigning a task must notify the assignee.',
        );

        Livewire::actingAs($execution)->test(TasksShow::class, ['task' => $task])->call('changeStatus', 'in_progress');
        Livewire::actingAs($execution)->test(TasksShow::class, ['task' => $task])
            ->set('progressContent', 'Mockup sudah 80% jadi, tinggal revisi warna.')
            ->call('addProgressUpdate');
        Livewire::actingAs($execution)->test(TasksShow::class, ['task' => $task])->call('changeStatus', 'in_review');
        Livewire::actingAs($admin)->test(TasksShow::class, ['task' => $task])->call('changeStatus', 'done');

        $this->assertSame('done', $task->fresh()->status);

        // ================= ADMIN: unified dashboard reflects both members' real activity =================
        $overallPercentage = app(\App\Services\Exploration\ProgressService::class)->overallProgressPercentage($exploration->fresh());

        $dashboard = $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $dashboard->assertSee($exploration->name);
        $dashboard->assertSee($expectedPoints.' poin');
        $dashboard->assertSee($overallPercentage.'%');
        $dashboard->assertSee($execution->name);
        $dashboard->assertSee($project->title);

        // Eksekusi member summary table must show exactly 1 done task for this member, not a stale/wrong count
        $html = $dashboard->getContent();
        $this->assertMatchesRegularExpression(
            '/'.preg_quote($execution->name, '/').'.*?<td[^>]*>1<\/td>/s',
            $html,
        );
    }

    private function completeUnitAsMember(User $user, Unit $unit): void
    {
        $questions = $unit->evaluations()->orderBy('sort_order')->get();

        if (in_array($unit->evaluation_type, ['essay', 'practice'], true)) {
            Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit])
                ->set('freeTextAnswer', 'Jawaban lengkap untuk unit ini, ditulis sebagai bagian dari smoke test end-to-end.')
                ->call('submitFreeText');

            return;
        }

        $component = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $unit]);

        foreach ($questions as $question) {
            match ($question->question_type) {
                'matching' => collect($question->correct_answer)->each(
                    fn ($right, $left) => $component->set("quizAnswers.{$question->id}.{$left}", $right)
                ),
                'ordering' => $component->set("quizAnswers.{$question->id}", $question->correct_answer),
                default => $component->set("quizAnswers.{$question->id}", $question->correct_answer),
            };
        }

        $component->call('submitQuiz');
    }
}

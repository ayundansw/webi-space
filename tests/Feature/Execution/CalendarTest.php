<?php

namespace Tests\Feature\Execution;

use App\Livewire\Eksekusi\Dashboard as EksekusiDashboard;
use App\Livewire\Eksekusi\Kalender as PersonalKalender;
use App\Livewire\Eksekusi\Projects\Tabs\Kalender as ProjectKalender;
use App\Models\CalendarEvent;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use App\Services\Execution\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 7 Batch 2b (docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Modul_Manajemen_Proyek_v2.md
 * §Kalender): Kegiatan (task deadline + milestone, derived, never stored)
 * + Acara (calendar_events, manual) from the SAME CalendarService source
 * for both the project Kalender tab and the personal cross-project page.
 */
class CalendarTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function member(string $name = 'Anggota', string $email = 'anggota@example.test'): User
    {
        return User::create([
            'name' => $name, 'email' => $email,
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);
    }

    /** @return array{0: Project, 1: Task, 2: Milestone} */
    private function projectWithTaskAndMilestone(User $admin, string $title = 'Proyek A', string $deadline = '2026-07-15'): array
    {
        $project = Project::create([
            'title' => $title, 'description' => 'D', 'objective' => 'T',
            'project_type' => 'internal', 'status' => 'active',
            'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'Milestone '.$title, 'target_date' => '2026-07-20', 'sort_order' => 1]);
        $task = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Task Deadline '.$title,
            'status' => 'todo', 'priority' => 'medium', 'deadline' => $deadline, 'created_by' => $admin->id,
        ]);

        return [$project, $task, $milestone];
    }

    // ------------------------------------------------------------------
    // Tab Kalender proyek — gabungan Kegiatan (derived) + Acara (manual)
    // ------------------------------------------------------------------

    public function test_project_calendar_tab_shows_derived_task_deadline_and_milestone_without_any_manual_event(): void
    {
        $admin = $this->admin();
        [$project, $task, $milestone] = $this->projectWithTaskAndMilestone($admin);

        Carbon::setTestNow('2026-07-01');
        $html = Livewire::actingAs($admin)->test(ProjectKalender::class, ['project' => $project])->html();
        Carbon::setTestNow();

        $this->assertStringContainsString($task->title, $html);
        $this->assertStringContainsString($milestone->title, $html);
        $this->assertSame(0, CalendarEvent::count(), 'Kegiatan must never be written to calendar_events.');
    }

    public function test_adding_a_manual_event_via_the_project_tab_creates_an_acara_scoped_to_that_project(): void
    {
        $admin = $this->admin();
        [$project] = $this->projectWithTaskAndMilestone($admin);

        Carbon::setTestNow('2026-07-01');
        Livewire::actingAs($admin)->test(ProjectKalender::class, ['project' => $project])
            ->set('newEventTitle', 'Rapat Koordinasi')
            ->set('newEventType', 'meeting')
            ->call('addEvent', '2026-07-10')
            ->assertHasNoErrors();
        Carbon::setTestNow();

        $event = CalendarEvent::sole();
        $this->assertSame('Rapat Koordinasi', $event->title);
        $this->assertSame($project->id, $event->project_id);
        $this->assertSame($admin->id, $event->created_by);
        $this->assertSame('meeting', $event->type);
        $this->assertSame('2026-07-10', $event->start_at->toDateString());
    }

    public function test_addEvent_requires_a_title(): void
    {
        $admin = $this->admin();
        [$project] = $this->projectWithTaskAndMilestone($admin);

        Livewire::actingAs($admin)->test(ProjectKalender::class, ['project' => $project])
            ->set('newEventTitle', '')
            ->call('addEvent', '2026-07-10')
            ->assertHasErrors(['newEventTitle' => 'required']);
    }

    public function test_project_calendar_tab_never_shows_another_projects_events_or_deadlines(): void
    {
        $admin = $this->admin();
        [$projectA, $taskA] = $this->projectWithTaskAndMilestone($admin, 'Proyek A', '2026-07-10');
        [$projectB, $taskB] = $this->projectWithTaskAndMilestone($admin, 'Proyek B', '2026-07-12');
        CalendarEvent::create([
            'project_id' => $projectB->id, 'created_by' => $admin->id, 'title' => 'Acara Proyek B',
            'start_at' => '2026-07-11', 'type' => 'other',
        ]);

        Carbon::setTestNow('2026-07-01');
        $html = Livewire::actingAs($admin)->test(ProjectKalender::class, ['project' => $projectA])->html();
        Carbon::setTestNow();

        $this->assertStringContainsString($taskA->title, $html);
        $this->assertStringNotContainsString($taskB->title, $html);
        $this->assertStringNotContainsString('Acara Proyek B', $html);
    }

    public function test_items_outside_the_displayed_month_do_not_appear_until_navigating_to_that_month(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTaskAndMilestone($admin, 'Proyek A', '2026-08-15');

        Carbon::setTestNow('2026-07-01');
        $component = Livewire::actingAs($admin)->test(ProjectKalender::class, ['project' => $project]);
        $this->assertStringNotContainsString($task->title, $component->html());

        $component->call('nextMonth');
        $this->assertStringContainsString($task->title, $component->html());
        Carbon::setTestNow();
    }

    public function test_a_subtask_deadline_never_appears_on_the_calendar_as_kegiatan(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTaskAndMilestone($admin, 'Proyek A', '2026-07-05');
        $subtask = app(TaskService::class)->createSubtask($task, $admin, [
            'title' => 'Subtask Tersembunyi Kalender', 'deadline' => '2026-07-15',
        ]);

        Carbon::setTestNow('2026-07-01');
        $html = Livewire::actingAs($admin)->test(ProjectKalender::class, ['project' => $project])->html();
        Carbon::setTestNow();

        $this->assertStringContainsString($task->title, $html);
        $this->assertStringNotContainsString($subtask->title, $html);
    }

    // ------------------------------------------------------------------
    // RBAC
    // ------------------------------------------------------------------

    public function test_a_non_member_cannot_view_the_project_calendar_tab(): void
    {
        $admin = $this->admin();
        $outsider = $this->member('Outsider', 'outsider@example.test');
        [$project] = $this->projectWithTaskAndMilestone($admin);

        $this->actingAs($outsider)->get("/eksekusi/projects/{$project->id}/kalender")->assertForbidden();
    }

    public function test_a_project_member_can_view_and_add_events_to_the_project_calendar(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        [$project] = $this->projectWithTaskAndMilestone($admin);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        Livewire::actingAs($member)->test(ProjectKalender::class, ['project' => $project])
            ->set('newEventTitle', 'Kumpul Rutin')
            ->call('addEvent', '2026-07-10')
            ->assertHasNoErrors();

        $this->assertTrue(CalendarEvent::where('title', 'Kumpul Rutin')->where('created_by', $member->id)->exists());
    }

    // ------------------------------------------------------------------
    // Kalender Personal — gabungan lintas-proyek + acara personal
    // ------------------------------------------------------------------

    public function test_personal_calendar_merges_kegiatan_and_acara_across_every_project_the_member_belongs_to(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        [$projectA, $taskA] = $this->projectWithTaskAndMilestone($admin, 'Proyek A', '2026-07-08');
        [$projectB, $taskB] = $this->projectWithTaskAndMilestone($admin, 'Proyek B', '2026-07-09');
        ProjectMember::create(['project_id' => $projectA->id, 'user_id' => $member->id]);
        ProjectMember::create(['project_id' => $projectB->id, 'user_id' => $member->id]);
        CalendarEvent::create([
            'project_id' => $projectA->id, 'created_by' => $admin->id, 'title' => 'Acara Proyek A',
            'start_at' => '2026-07-07', 'type' => 'other',
        ]);

        Carbon::setTestNow('2026-07-01');
        $html = Livewire::actingAs($member)->test(PersonalKalender::class)->html();
        Carbon::setTestNow();

        $this->assertStringContainsString($taskA->title, $html);
        $this->assertStringContainsString($taskB->title, $html);
        $this->assertStringContainsString('Acara Proyek A', $html);
    }

    public function test_personal_calendar_excludes_a_project_the_member_does_not_belong_to(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        [$projectA] = $this->projectWithTaskAndMilestone($admin, 'Proyek A', '2026-07-08');
        [$projectB, $taskB] = $this->projectWithTaskAndMilestone($admin, 'Proyek B', '2026-07-09');
        ProjectMember::create(['project_id' => $projectA->id, 'user_id' => $member->id]);
        // Member is NOT part of Proyek B.

        Carbon::setTestNow('2026-07-01');
        $html = Livewire::actingAs($member)->test(PersonalKalender::class)->html();
        Carbon::setTestNow();

        $this->assertStringNotContainsString($taskB->title, $html);
    }

    public function test_personal_calendar_includes_a_general_event_but_excludes_another_users_personal_event(): void
    {
        $admin = $this->admin();
        $member = $this->member('Anggota Satu', 'satu@example.test');
        $otherMember = $this->member('Anggota Dua', 'dua@example.test');

        CalendarEvent::create([
            'project_id' => null, 'created_by' => $member->id, 'title' => 'Acara Personal Milikku',
            'start_at' => '2026-07-10', 'type' => 'other',
        ]);
        CalendarEvent::create([
            'project_id' => null, 'created_by' => $otherMember->id, 'title' => 'Acara Personal Orang Lain',
            'start_at' => '2026-07-11', 'type' => 'other',
        ]);

        Carbon::setTestNow('2026-07-01');
        $html = Livewire::actingAs($member)->test(PersonalKalender::class)->html();
        Carbon::setTestNow();

        $this->assertStringContainsString('Acara Personal Milikku', $html);
        $this->assertStringNotContainsString('Acara Personal Orang Lain', $html);
    }

    public function test_adding_an_event_from_the_personal_calendar_page_always_creates_a_general_event(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        [$project] = $this->projectWithTaskAndMilestone($admin);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        Livewire::actingAs($member)->test(PersonalKalender::class)
            ->set('newEventTitle', 'Acara Bebas')
            ->call('addEvent', '2026-07-10')
            ->assertHasNoErrors();

        $event = CalendarEvent::where('title', 'Acara Bebas')->sole();
        $this->assertNull($event->project_id);
        $this->assertSame($member->id, $event->created_by);
    }

    public function test_admin_cannot_access_the_personal_calendar_page(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/eksekusi/kalender')->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Ringkasan Kalender di Dashboard Eksekusi
    // ------------------------------------------------------------------

    public function test_dashboard_eksekusi_shows_real_upcoming_calendar_items_not_a_placeholder(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        [$project, $task] = $this->projectWithTaskAndMilestone($admin, 'Proyek A', '2026-07-05');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        Carbon::setTestNow('2026-07-01');
        $html = Livewire::actingAs($member)->test(EksekusiDashboard::class)->html();
        Carbon::setTestNow();

        $this->assertStringContainsString('Ringkasan Kalender', $html);
        $this->assertStringContainsString($task->title, $html);
        $this->assertStringNotContainsString('Tanggal terdekat (deadline task, milestone, acara) tampil di sini begitu modul Kalender aktif.', $html);
    }
}

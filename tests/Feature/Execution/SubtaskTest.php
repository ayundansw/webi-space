<?php

namespace Tests\Feature\Execution;

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Eksekusi\Dashboard as EksekusiDashboard;
use App\Livewire\Eksekusi\Projects\Board;
use App\Livewire\Eksekusi\Tasks\Show;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Services\Execution\AlertService;
use App\Services\Execution\ProjectService;
use App\Services\Execution\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 7 Batch 2a: subtask (tasks.parent_task_id). This file exists
 * specifically to PROVE the RECON_fase7_manajemen_proyek.md audit decisions
 * hold — not generic subtask CRUD coverage. Confirmed policy (AskUserQuestion,
 * 2026-07-12): subtasks are EXCLUDED from Kanban / progress rollups / admin
 * project-summary counts, but INCLUDED in alerts and personal "task saya"
 * counts (Eksekusi\Dashboard taskCounts, Admin\Dashboard memberSummaries).
 */
class SubtaskTest extends TestCase
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

    /** @return array{0: Project, 1: Task} */
    private function projectWithTask(User $admin, string $taskTitle = 'Task Besar'): array
    {
        $project = Project::create([
            'title' => 'Proyek '.$taskTitle, 'description' => 'D', 'objective' => 'T',
            'project_type' => 'internal', 'status' => 'active',
            'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'M1', 'target_date' => '2026-07-15', 'sort_order' => 1]);
        $task = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => $taskTitle,
            'status' => 'todo', 'priority' => 'medium', 'deadline' => '2026-07-20', 'created_by' => $admin->id,
        ]);

        return [$project, $task];
    }

    // ------------------------------------------------------------------
    // Creation
    // ------------------------------------------------------------------

    public function test_task_service_creates_a_subtask_inheriting_the_parents_project_and_milestone(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTask($admin);
        $service = app(TaskService::class);

        $subtask = $service->createSubtask($task, $admin, ['title' => 'Subtask kecil']);

        $this->assertSame($task->id, $subtask->parent_task_id);
        $this->assertSame($task->project_id, $subtask->project_id);
        $this->assertSame($task->milestone_id, $subtask->milestone_id);
        $this->assertSame('todo', $subtask->status);
        // "Deadline opsional": omitted deadline defaults to the parent's own,
        // since the tasks.deadline column stays NOT NULL (no schema change
        // authorized for this batch beyond parent_task_id itself).
        $this->assertTrue($subtask->deadline->equalTo($task->deadline));
        $this->assertTrue($subtask->isSubtask());
        $this->assertFalse($task->fresh()->isSubtask());
    }

    public function test_addSubtask_from_the_task_panel_creates_a_subtask_with_an_optional_assignee_and_deadline(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        [$project, $task] = $this->projectWithTask($admin);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        Livewire::actingAs($admin)->test(Show::class, ['task' => $task])
            ->set('newSubtaskTitle', 'Desain wireframe')
            ->set('newSubtaskAssigneeId', $member->id)
            ->set('newSubtaskDeadline', '2026-07-18')
            ->call('addSubtask')
            ->assertHasNoErrors();

        $subtask = $task->subtasks()->sole();
        $this->assertSame('Desain wireframe', $subtask->title);
        $this->assertSame('2026-07-18', $subtask->deadline->toDateString());
        $this->assertTrue($subtask->assignments()->where('user_id', $member->id)->exists());
    }

    public function test_addSubtask_requires_a_title(): void
    {
        $admin = $this->admin();
        [, $task] = $this->projectWithTask($admin);

        Livewire::actingAs($admin)->test(Show::class, ['task' => $task])
            ->set('newSubtaskTitle', '')
            ->call('addSubtask')
            ->assertHasErrors(['newSubtaskTitle' => 'required']);
    }

    public function test_a_subtask_cannot_itself_spawn_a_further_nested_subtask(): void
    {
        $admin = $this->admin();
        [, $task] = $this->projectWithTask($admin);
        $subtask = app(TaskService::class)->createSubtask($task, $admin, ['title' => 'Sub 1']);

        Livewire::actingAs($admin)->test(Show::class, ['task' => $subtask])
            ->set('newSubtaskTitle', 'Sub-sub')
            ->call('addSubtask')
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // EXCLUDED: Kanban board
    // ------------------------------------------------------------------

    public function test_a_subtask_never_appears_as_a_kanban_column_card(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTask($admin, 'Task Kanban');
        $subtask = app(TaskService::class)->createSubtask($task, $admin, ['title' => 'Subtask Tersembunyi']);

        $html = Livewire::actingAs($admin)->test(Board::class, ['project' => $project])->html();

        $this->assertStringContainsString($task->title, $html);
        $this->assertStringNotContainsString($subtask->title, $html);
    }

    public function test_a_subtask_id_cannot_be_opened_as_its_own_panel_via_action_or_query_param(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTask($admin);
        $subtask = app(TaskService::class)->createSubtask($task, $admin, ['title' => 'Sub']);

        Livewire::actingAs($admin)->test(Board::class, ['project' => $project])
            ->call('openTask', $subtask->id)
            ->assertSet('openTaskId', null);

        $this->actingAs($admin)
            ->get("/eksekusi/projects/{$project->id}/kanban?task={$subtask->id}")
            ->assertOk()
            ->assertDontSee($subtask->title);
    }

    public function test_changing_status_via_the_kanban_action_refuses_a_subtask_id(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTask($admin);
        $subtask = app(TaskService::class)->createSubtask($task, $admin, ['title' => 'Sub']);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($admin)->test(Board::class, ['project' => $project])
            ->call('changeStatus', $subtask->id, 'in_progress');
    }

    // ------------------------------------------------------------------
    // EXCLUDED: progress / rollup counts
    // ------------------------------------------------------------------

    public function test_project_progress_percentage_excludes_subtasks(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTask($admin, 'Task Selesai');
        $task->update(['status' => 'done']);

        // 1 task besar, done -> 100%. Adding an incomplete subtask must not
        // dilute this, since subtasks aren't counted in the denominator.
        app(TaskService::class)->createSubtask($task, $admin, ['title' => 'Subtask belum selesai']);

        $this->assertSame(100, $project->fresh()->progressPercentage());
    }

    public function test_milestone_progress_percentage_excludes_subtasks(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTask($admin, 'Task Milestone');
        $task->update(['status' => 'done']);
        $milestone = $task->milestone;

        app(TaskService::class)->createSubtask($task, $admin, ['title' => 'Subtask belum selesai']);

        $this->assertSame(100, $milestone->fresh()->progressPercentage());
    }

    public function test_admin_dashboard_project_summary_active_members_excludes_a_member_only_active_via_a_subtask(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        [$project, $task] = $this->projectWithTask($admin, 'Task Admin Summary');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);
        $task->update(['status' => 'done']);

        $subtask = app(TaskService::class)->createSubtask($task, $admin, ['title' => 'Sub aktif', 'assignee_id' => $member->id]);
        $subtask->update(['status' => 'in_progress']);

        $html = Livewire::actingAs($admin)->test(AdminDashboard::class)->html();

        // Project card renders, but the member is only "active" through a
        // subtask -- excluded from active_members count for this project.
        $this->assertStringContainsString($project->title, $html);
        $this->assertMatchesRegularExpression('/'.preg_quote($project->title, '/').'.*?0\s*anggota aktif/s', $html);
    }

    public function test_eksekusi_dashboard_project_task_count_excludes_subtasks(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTask($admin, 'Task Eksekusi Dashboard');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $admin->id]);

        app(TaskService::class)->createSubtask($task, $admin, ['title' => 'Sub']);

        Livewire::actingAs($admin)->test(EksekusiDashboard::class)
            ->assertViewHas('projects', function ($projects) {
                return $projects->first()['task_count'] === 1;
            });
    }

    // ------------------------------------------------------------------
    // INCLUDED: alerts
    // ------------------------------------------------------------------

    public function test_overdue_tasks_alert_detects_an_overdue_subtask(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTask($admin, 'Task Tidak Overdue');
        $task->update(['deadline' => now()->addDays(10)->toDateString()]);

        $subtask = app(TaskService::class)->createSubtask($task, $admin, [
            'title' => 'Subtask Overdue', 'deadline' => now()->subDays(2)->toDateString(),
        ]);

        $overdue = app(AlertService::class)->overdueTasks();

        $this->assertTrue($overdue->contains('id', $subtask->id));
        $this->assertFalse($overdue->contains('id', $task->id));
    }

    public function test_due_soon_alert_detects_a_soon_due_subtask(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTask($admin, 'Task Jauh');
        $task->update(['deadline' => now()->addDays(30)->toDateString()]);

        $subtask = app(TaskService::class)->createSubtask($task, $admin, [
            'title' => 'Subtask Deadline Dekat', 'deadline' => now()->addDay()->toDateString(),
        ]);

        $dueSoon = app(AlertService::class)->dueSoonTasks();

        $this->assertTrue($dueSoon->contains('id', $subtask->id));
    }

    public function test_project_completion_is_blocked_by_an_incomplete_subtask_even_if_all_main_tasks_are_done(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTask($admin, 'Task Utama Selesai');
        $task->update(['status' => 'done']);
        app(TaskService::class)->createSubtask($task, $admin, ['title' => 'Sub belum selesai']);

        $this->expectException(ValidationException::class);
        app(ProjectService::class)->changeStatus($project, 'completed', $admin);
    }

    // ------------------------------------------------------------------
    // INCLUDED: personal "task saya" counts
    // ------------------------------------------------------------------

    public function test_eksekusi_dashboard_personal_task_counts_include_a_subtask_assigned_to_the_member(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        [$project, $task] = $this->projectWithTask($admin, 'Task Personal');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        $subtask = app(TaskService::class)->createSubtask($task, $admin, [
            'title' => 'Sub Personal', 'assignee_id' => $member->id,
        ]);
        $subtask->update(['status' => 'in_progress']);

        Livewire::actingAs($member)->test(EksekusiDashboard::class)
            ->assertViewHas('taskCounts', fn ($counts) => $counts['in_progress'] === 1)
            ->assertViewHas('activeTaskCount', 1);
    }

    public function test_admin_dashboard_member_summary_includes_a_subtask_assigned_to_the_member(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        [$project, $task] = $this->projectWithTask($admin, 'Task Member Summary');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        $subtask = app(TaskService::class)->createSubtask($task, $admin, [
            'title' => 'Sub Member Summary', 'assignee_id' => $member->id,
        ]);
        $subtask->update(['status' => 'done']);

        Livewire::actingAs($admin)->test(AdminDashboard::class)
            ->assertViewHas('memberSummaries', function ($summaries) use ($member) {
                $row = $summaries->firstWhere('member.id', $member->id);

                return $row && $row['done'] === 1;
            });
    }

    public function test_profile_contribution_count_includes_a_subtask_assigned_to_the_member(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        [$project, $task] = $this->projectWithTask($admin, 'Task Kontribusi');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        app(TaskService::class)->createSubtask($task, $admin, [
            'title' => 'Sub Kontribusi', 'assignee_id' => $member->id,
        ]);

        Livewire::actingAs($member)->test(\App\Livewire\Profile\Edit::class)
            ->assertViewHas('projectContributions', function ($contributions) use ($project) {
                $row = $contributions->firstWhere('project.id', $project->id);

                return $row && $row['task_count'] === 1;
            });
    }

    // ------------------------------------------------------------------
    // Independence from parent status
    // ------------------------------------------------------------------

    public function test_changing_a_subtasks_status_does_not_affect_the_parent_tasks_status(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        [$project, $task] = $this->projectWithTask($admin);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);
        TaskAssignment::create(['task_id' => $task->id, 'user_id' => $member->id, 'assigned_by' => $admin->id]);

        $subtask = app(TaskService::class)->createSubtask($task, $admin, [
            'title' => 'Sub Independen', 'assignee_id' => $member->id,
        ]);

        Livewire::actingAs($member)->test(Show::class, ['task' => $task])
            ->call('changeSubtaskStatus', $subtask->id, 'in_progress')
            ->assertHasNoErrors();

        $this->assertSame('in_progress', $subtask->fresh()->status);
        $this->assertSame('todo', $task->fresh()->status);
    }

    public function test_changeSubtaskStatus_is_scoped_to_the_current_tasks_own_subtasks(): void
    {
        $admin = $this->admin();
        [, $taskA] = $this->projectWithTask($admin, 'Task A');
        [, $taskB] = $this->projectWithTask($admin, 'Task B');
        $subtaskOfB = app(TaskService::class)->createSubtask($taskB, $admin, ['title' => 'Sub of B']);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($admin)->test(Show::class, ['task' => $taskA])
            ->call('changeSubtaskStatus', $subtaskOfB->id, 'in_progress');
    }

    public function test_the_panel_mini_list_shows_the_subtask_and_its_status(): void
    {
        $admin = $this->admin();
        [, $task] = $this->projectWithTask($admin);
        $subtask = app(TaskService::class)->createSubtask($task, $admin, ['title' => 'Subtask Terlihat']);

        $html = Livewire::actingAs($admin)->test(Show::class, ['task' => $task])->html();

        $this->assertStringContainsString($subtask->title, $html);
    }
}

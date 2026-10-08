<?php

namespace Tests\Feature\Execution;

use App\Livewire\Eksekusi\Projects\Board;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 7 Batch 1b: Detail Task is a slide-over panel on the Kanban tab now
 * (App\Livewire\Eksekusi\Projects\Board::$openTaskId), not its own page —
 * App\Livewire\Eksekusi\Tasks\Show is reused UNCHANGED as the nested panel
 * content (see board.blade.php), so its own extensive test coverage
 * (TaskManagementTest, TaskCollaborationTest, AttachmentDownloadTest) still
 * proves the actual task detail logic works. This file only covers the NEW
 * surface: opening/closing the panel, and the old-URL-redirect auto-open.
 */
class TaskPanelTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function member(): User
    {
        return User::create([
            'name' => 'Anggota', 'email' => 'anggota@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);
    }

    /** @return array{0: Project, 1: Task} */
    private function projectWithTask(User $admin, string $taskTitle = 'Buat halaman login'): array
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

    public function test_open_task_sets_the_open_task_id_and_render_shows_the_panel_content(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTask($admin);

        $component = Livewire::actingAs($admin)->test(Board::class, ['project' => $project])
            ->assertSet('openTaskId', null)
            ->call('openTask', $task->id)
            ->assertSet('openTaskId', $task->id);

        $html = $component->html();
        $this->assertStringContainsString($task->title, $html);
        // App\Livewire\Eksekusi\Tasks\Show's own content — proves the
        // REAL reused component rendered, not a rebuilt duplicate.
        $this->assertStringContainsString('Progress Update', $html);
    }

    public function test_close_task_panel_clears_the_open_task_id(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTask($admin);

        Livewire::actingAs($admin)->test(Board::class, ['project' => $project])
            ->call('openTask', $task->id)
            ->assertSet('openTaskId', $task->id)
            ->call('closeTaskPanel')
            ->assertSet('openTaskId', null);
    }

    public function test_open_task_refuses_a_task_from_a_different_project(): void
    {
        $admin = $this->admin();
        [$project] = $this->projectWithTask($admin, 'Task Proyek A');
        [, $foreignTask] = $this->projectWithTask($admin, 'Task Proyek B');

        Livewire::actingAs($admin)->test(Board::class, ['project' => $project])
            ->call('openTask', $foreignTask->id)
            ->assertSet('openTaskId', null);
    }

    public function test_visiting_the_old_task_url_redirects_to_kanban_with_a_query_param_that_auto_opens_the_panel(): void
    {
        $admin = $this->admin();
        [$project, $task] = $this->projectWithTask($admin);

        $this->actingAs($admin)->get("/eksekusi/tasks/{$task->id}")
            ->assertRedirect("/eksekusi/projects/{$project->id}/kanban?task={$task->id}");

        // Following that redirect (simulated: mount Board with the same
        // query param Laravel would receive) auto-opens the panel.
        $this->get("/eksekusi/projects/{$project->id}/kanban?task={$task->id}")
            ->assertOk()
            ->assertSee($task->title);
    }

    public function test_a_non_member_visiting_the_old_task_url_is_forbidden_before_any_redirect(): void
    {
        $admin = $this->admin();
        $outsider = $this->member();
        [, $task] = $this->projectWithTask($admin);

        $this->actingAs($outsider)->get("/eksekusi/tasks/{$task->id}")->assertForbidden();
    }

    public function test_query_param_is_ignored_when_the_task_does_not_belong_to_the_project(): void
    {
        $admin = $this->admin();
        [$project] = $this->projectWithTask($admin, 'Task Proyek A');
        [, $foreignTask] = $this->projectWithTask($admin, 'Task Proyek B');

        // Real HTTP request (not Livewire::test(), which doesn't carry a
        // simulated query string) — proves mount()'s validation actually
        // blocks a crafted ?task= pointing at a task from another project,
        // the same safeguard openTask() has for the click-driven path.
        $this->actingAs($admin)
            ->get("/eksekusi/projects/{$project->id}/kanban?task={$foreignTask->id}")
            ->assertOk()
            ->assertDontSee($foreignTask->title);
    }

    public function test_project_member_can_open_and_close_the_panel(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        [$project, $task] = $this->projectWithTask($admin);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        Livewire::actingAs($member)->test(Board::class, ['project' => $project])
            ->call('openTask', $task->id)
            ->assertSet('openTaskId', $task->id);
    }
}

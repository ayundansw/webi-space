<?php

namespace Tests\Feature\Execution;

use App\Livewire\Eksekusi\Projects\Tabs\Gantt;
use App\Livewire\Eksekusi\Tasks\Show;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Models\User;
use App\Services\Execution\TaskDependencyService;
use App\Services\Execution\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 7 Batch 3a (docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Modul_Manajemen_Proyek_v2.md): task
 * dependency + Gantt. The cycle-prevention tests are the most important
 * ones here — App\Services\Execution\TaskDependencyService::wouldCreateCycle()
 * is the only thing standing between a manual mistake and a dependency
 * graph the Gantt tab can never render sensibly again.
 */
class TaskDependencyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function taskIn(Project $project, User $admin, string $title, string $deadline = '2026-07-20'): Task
    {
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'M '.$title, 'target_date' => '2026-07-25', 'sort_order' => 1]);

        return Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => $title,
            'status' => 'todo', 'priority' => 'medium', 'deadline' => $deadline, 'created_by' => $admin->id,
        ]);
    }

    private function project(User $admin, string $title = 'Proyek A'): Project
    {
        return Project::create([
            'title' => $title, 'description' => 'D', 'objective' => 'T',
            'project_type' => 'internal', 'status' => 'active',
            'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);
    }

    // ------------------------------------------------------------------
    // CRUD dasar
    // ------------------------------------------------------------------

    public function test_addDependency_creates_a_row_pointing_from_task_to_the_task_it_depends_on(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $taskA = $this->taskIn($project, $admin, 'Task A');
        $taskB = $this->taskIn($project, $admin, 'Task B');

        $dependency = app(TaskDependencyService::class)->addDependency($taskA, $taskB);

        $this->assertSame($taskA->id, $dependency->task_id);
        $this->assertSame($taskB->id, $dependency->depends_on_task_id);
    }

    public function test_adding_a_dependency_from_the_task_panel_persists_and_shows_in_the_mini_list(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $taskA = $this->taskIn($project, $admin, 'Task A');
        $taskB = $this->taskIn($project, $admin, 'Task B');

        Livewire::actingAs($admin)->test(Show::class, ['task' => $taskA])
            ->set('newDependencyTaskId', $taskB->id)
            ->call('addDependency')
            ->assertHasNoErrors()
            ->assertSeeText('Task B');

        $this->assertTrue(TaskDependency::where('task_id', $taskA->id)->where('depends_on_task_id', $taskB->id)->exists());
    }

    public function test_a_duplicate_dependency_is_rejected(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $taskA = $this->taskIn($project, $admin, 'Task A');
        $taskB = $this->taskIn($project, $admin, 'Task B');
        app(TaskDependencyService::class)->addDependency($taskA, $taskB);

        Livewire::actingAs($admin)->test(Show::class, ['task' => $taskA])
            ->set('newDependencyTaskId', $taskB->id)
            ->call('addDependency')
            ->assertHasErrors('newDependencyTaskId');

        $this->assertSame(1, TaskDependency::where('task_id', $taskA->id)->where('depends_on_task_id', $taskB->id)->count());
    }

    public function test_a_task_cannot_depend_on_itself(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $task = $this->taskIn($project, $admin, 'Task A');

        Livewire::actingAs($admin)->test(Show::class, ['task' => $task])
            ->set('newDependencyTaskId', $task->id)
            ->call('addDependency')
            ->assertHasErrors('newDependencyTaskId');

        $this->assertSame(0, TaskDependency::count());
    }

    // ------------------------------------------------------------------
    // Batasan: lintas proyek & subtask
    // ------------------------------------------------------------------

    public function test_a_cross_project_dependency_is_rejected(): void
    {
        $admin = $this->admin();
        $projectA = $this->project($admin, 'Proyek A');
        $projectB = $this->project($admin, 'Proyek B');
        $taskA = $this->taskIn($projectA, $admin, 'Task A');
        $taskB = $this->taskIn($projectB, $admin, 'Task B');

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(TaskDependencyService::class)->addDependency($taskA, $taskB);
    }

    public function test_the_dependency_dropdown_never_offers_a_task_from_another_project(): void
    {
        $admin = $this->admin();
        $projectA = $this->project($admin, 'Proyek A');
        $projectB = $this->project($admin, 'Proyek B');
        $taskA = $this->taskIn($projectA, $admin, 'Task A');
        $taskB = $this->taskIn($projectB, $admin, 'Task Proyek Lain');

        $html = Livewire::actingAs($admin)->test(Show::class, ['task' => $taskA])->html();

        $this->assertStringNotContainsString('Task Proyek Lain', $html);
    }

    public function test_a_subtask_cannot_be_the_dependent_side_of_a_dependency(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $task = $this->taskIn($project, $admin, 'Task A');
        $other = $this->taskIn($project, $admin, 'Task B');
        $subtask = app(TaskService::class)->createSubtask($task, $admin, ['title' => 'Sub A']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(TaskDependencyService::class)->addDependency($subtask, $other);
    }

    public function test_a_subtask_cannot_be_the_depended_on_side_of_a_dependency(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $task = $this->taskIn($project, $admin, 'Task A');
        $other = $this->taskIn($project, $admin, 'Task B');
        $subtask = app(TaskService::class)->createSubtask($task, $admin, ['title' => 'Sub A']);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(TaskDependencyService::class)->addDependency($other, $subtask);
    }

    public function test_a_subtask_never_appears_in_the_dependency_dropdown(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $task = $this->taskIn($project, $admin, 'Task A');
        $subtask = app(TaskService::class)->createSubtask($task, $admin, ['title' => 'Sub Tersembunyi']);

        // The subtask legitimately appears elsewhere on this same page (its
        // own mini-list, Fase 7 Batch 2a) — so this checks the dependency
        // dropdown's own option list specifically, not the whole page.
        $component = Livewire::actingAs($admin)->test(Show::class, ['task' => $task]);
        $availableIds = $component->viewData('availableDependencyTasks')->pluck('id');

        $this->assertFalse($availableIds->contains($subtask->id));
    }

    // ------------------------------------------------------------------
    // WAJIB: pencegahan siklik
    // ------------------------------------------------------------------

    public function test_a_direct_two_node_cycle_is_rejected(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $taskA = $this->taskIn($project, $admin, 'Task A');
        $taskB = $this->taskIn($project, $admin, 'Task B');

        // A bergantung pada B (sah).
        app(TaskDependencyService::class)->addDependency($taskA, $taskB);

        // B bergantung pada A -- akan membentuk siklus A->B->A, harus ditolak.
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(TaskDependencyService::class)->addDependency($taskB, $taskA);
    }

    public function test_a_three_node_transitive_cycle_is_rejected(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $taskA = $this->taskIn($project, $admin, 'Task A');
        $taskB = $this->taskIn($project, $admin, 'Task B');
        $taskC = $this->taskIn($project, $admin, 'Task C');

        // A bergantung B, B bergantung C -- keduanya sah.
        app(TaskDependencyService::class)->addDependency($taskA, $taskB);
        app(TaskDependencyService::class)->addDependency($taskB, $taskC);

        // C bergantung A akan menutup siklus A->B->C->A -- harus ditolak.
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(TaskDependencyService::class)->addDependency($taskC, $taskA);
    }

    public function test_cycle_rejection_leaves_no_row_written_and_surfaces_a_clear_error_from_the_panel(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $taskA = $this->taskIn($project, $admin, 'Task A');
        $taskB = $this->taskIn($project, $admin, 'Task B');
        app(TaskDependencyService::class)->addDependency($taskA, $taskB);

        Livewire::actingAs($admin)->test(Show::class, ['task' => $taskB])
            ->set('newDependencyTaskId', $taskA->id)
            ->call('addDependency')
            ->assertHasErrors('newDependencyTaskId');

        $this->assertSame(1, TaskDependency::count(), 'Only the original A->B row should exist, the rejected B->A must not be written.');
    }

    public function test_wouldCreateCycle_correctly_distinguishes_a_safe_addition_from_an_unsafe_one(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $taskA = $this->taskIn($project, $admin, 'Task A');
        $taskB = $this->taskIn($project, $admin, 'Task B');
        $taskC = $this->taskIn($project, $admin, 'Task C');
        app(TaskDependencyService::class)->addDependency($taskA, $taskB);

        $service = app(TaskDependencyService::class);

        // C -> A is safe: C isn't part of A's existing chain at all.
        $this->assertFalse($service->wouldCreateCycle($taskC, $taskA));
        // B -> A is unsafe: A already depends on B, so this would close the loop.
        $this->assertTrue($service->wouldCreateCycle($taskB, $taskA));
    }

    // ------------------------------------------------------------------
    // Gantt render
    // ------------------------------------------------------------------

    public function test_gantt_tab_only_lists_task_besar_in_row_order_and_excludes_subtasks(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $taskA = $this->taskIn($project, $admin, 'Task A', '2026-07-10');
        $taskB = $this->taskIn($project, $admin, 'Task B', '2026-07-20');
        $subtask = app(TaskService::class)->createSubtask($taskA, $admin, ['title' => 'Subtask Tersembunyi Gantt']);

        $html = Livewire::actingAs($admin)->test(Gantt::class, ['project' => $project])->html();

        $this->assertStringContainsString($taskA->title, $html);
        $this->assertStringContainsString($taskB->title, $html);
        $this->assertStringNotContainsString($subtask->title, $html);
    }

    public function test_gantt_bar_positions_reflect_relative_deadlines(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        // Both created "now" (same start), but B's deadline is later --
        // B's bar must be wider than A's.
        $taskA = $this->taskIn($project, $admin, 'Task A', now()->addDays(2)->toDateString());
        $taskB = $this->taskIn($project, $admin, 'Task B', now()->addDays(20)->toDateString());

        $component = Livewire::actingAs($admin)->test(Gantt::class, ['project' => $project]);
        $rows = collect($component->viewData('rows'));

        $rowA = $rows->firstWhere('task.id', $taskA->id);
        $rowB = $rows->firstWhere('task.id', $taskB->id);

        $this->assertGreaterThan($rowA['widthPct'], $rowB['widthPct']);
    }

    public function test_gantt_shows_a_dependency_connector_between_two_tasks(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $taskA = $this->taskIn($project, $admin, 'Task A');
        $taskB = $this->taskIn($project, $admin, 'Task B');
        app(TaskDependencyService::class)->addDependency($taskA, $taskB);

        $component = Livewire::actingAs($admin)->test(Gantt::class, ['project' => $project]);
        $edges = $component->viewData('edges');

        $this->assertCount(1, $edges);
    }

    public function test_gantt_shows_no_dependency_connectors_when_no_dependency_exists(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $this->taskIn($project, $admin, 'Task A');
        $this->taskIn($project, $admin, 'Task B');

        $component = Livewire::actingAs($admin)->test(Gantt::class, ['project' => $project]);
        $edges = $component->viewData('edges');

        $this->assertCount(0, $edges);
    }

    // ------------------------------------------------------------------
    // RBAC
    // ------------------------------------------------------------------

    public function test_a_non_member_cannot_view_the_gantt_tab(): void
    {
        $admin = $this->admin();
        $outsider = User::create([
            'name' => 'Outsider', 'email' => 'outsider@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);
        $project = $this->project($admin);

        $this->actingAs($outsider)->get("/eksekusi/projects/{$project->id}/gantt")->assertForbidden();
    }
}

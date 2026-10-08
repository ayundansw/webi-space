<?php

namespace Tests\Feature\Execution;

use App\Livewire\Eksekusi\Projects\Tabs\Roadmap;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Execution\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 7 Batch 3b: Roadmap is a read-only, high-level view derived entirely
 * from Milestone (sort_order) + Task.status — no new persistence, and
 * Milestone::progressPercentage() is reused as-is (not recomputed), so
 * these tests focus on the NEW layer this batch adds: ordering and the
 * completed/active/not_started status classification.
 */
class RoadmapTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function project(User $admin): Project
    {
        return Project::create([
            'title' => 'Proyek A', 'description' => 'D', 'objective' => 'T',
            'project_type' => 'internal', 'status' => 'active',
            'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);
    }

    private function milestone(Project $project, string $title, int $sortOrder): Milestone
    {
        return Milestone::create([
            'project_id' => $project->id, 'title' => $title,
            'target_date' => '2026-07-20', 'sort_order' => $sortOrder,
        ]);
    }

    private function task(Project $project, Milestone $milestone, User $admin, string $title, string $status): Task
    {
        return Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => $title,
            'status' => $status, 'priority' => 'medium', 'deadline' => '2026-07-25', 'created_by' => $admin->id,
        ]);
    }

    public function test_milestones_render_in_sort_order_regardless_of_creation_order(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        // Created out of sort_order on purpose.
        $this->milestone($project, 'Milestone Kedua', 2);
        $this->milestone($project, 'Milestone Pertama', 1);

        $html = Livewire::actingAs($admin)->test(Roadmap::class, ['project' => $project])->html();

        $this->assertLessThan(
            strpos($html, 'Milestone Kedua'),
            strpos($html, 'Milestone Pertama'),
            'Milestone Pertama (sort_order 1) must appear before Milestone Kedua (sort_order 2) in the rendered HTML.'
        );
    }

    public function test_a_milestone_with_all_tasks_done_is_classified_completed(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $milestone = $this->milestone($project, 'Milestone A', 1);
        $this->task($project, $milestone, $admin, 'Task 1', 'done');
        $this->task($project, $milestone, $admin, 'Task 2', 'done');

        $entries = Livewire::actingAs($admin)->test(Roadmap::class, ['project' => $project])->viewData('entries');

        $this->assertSame('completed', $entries->first()['status']);
        $this->assertSame(100, $entries->first()['percentage']);
    }

    public function test_a_milestone_with_an_in_progress_task_is_classified_active(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $milestone = $this->milestone($project, 'Milestone A', 1);
        $this->task($project, $milestone, $admin, 'Task 1', 'done');
        $this->task($project, $milestone, $admin, 'Task 2', 'in_progress');

        $entries = Livewire::actingAs($admin)->test(Roadmap::class, ['project' => $project])->viewData('entries');

        $this->assertSame('active', $entries->first()['status']);
    }

    public function test_a_milestone_with_an_in_review_task_is_also_classified_active(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $milestone = $this->milestone($project, 'Milestone A', 1);
        $this->task($project, $milestone, $admin, 'Task 1', 'in_review');

        $entries = Livewire::actingAs($admin)->test(Roadmap::class, ['project' => $project])->viewData('entries');

        $this->assertSame('active', $entries->first()['status']);
    }

    public function test_a_milestone_with_only_todo_tasks_is_classified_not_started(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $milestone = $this->milestone($project, 'Milestone A', 1);
        $this->task($project, $milestone, $admin, 'Task 1', 'todo');

        $entries = Livewire::actingAs($admin)->test(Roadmap::class, ['project' => $project])->viewData('entries');

        $this->assertSame('not_started', $entries->first()['status']);
    }

    public function test_a_milestone_with_zero_tasks_is_classified_not_started_not_completed(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $this->milestone($project, 'Milestone Kosong', 1);

        $entries = Livewire::actingAs($admin)->test(Roadmap::class, ['project' => $project])->viewData('entries');

        $this->assertSame('not_started', $entries->first()['status']);
        $this->assertSame(0, $entries->first()['percentage']);
    }

    public function test_a_subtask_never_affects_the_milestone_status_or_percentage(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $milestone = $this->milestone($project, 'Milestone A', 1);
        $task = $this->task($project, $milestone, $admin, 'Task 1', 'done');
        // Subtask in_progress -- must NOT flip the milestone to "active" or
        // dilute the 100% (Fase 7 Batch 2a exclusion, reused here).
        app(TaskService::class)->createSubtask($task, $admin, ['title' => 'Sub belum selesai']);

        $entries = Livewire::actingAs($admin)->test(Roadmap::class, ['project' => $project])->viewData('entries');

        $this->assertSame('completed', $entries->first()['status']);
        $this->assertSame(100, $entries->first()['percentage']);
    }

    public function test_progress_percentage_is_reused_from_the_milestone_model_not_recomputed_differently(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $milestone = $this->milestone($project, 'Milestone A', 1);
        $this->task($project, $milestone, $admin, 'Task 1', 'done');
        $this->task($project, $milestone, $admin, 'Task 2', 'todo');
        $this->task($project, $milestone, $admin, 'Task 3', 'todo');

        $entries = Livewire::actingAs($admin)->test(Roadmap::class, ['project' => $project])->viewData('entries');

        $this->assertSame($milestone->progressPercentage(), $entries->first()['percentage']);
    }

    public function test_clicking_a_milestone_reveals_its_task_titles_in_the_rendered_markup(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $milestone = $this->milestone($project, 'Milestone A', 1);
        $this->task($project, $milestone, $admin, 'Task Yang Terlihat Saat Klik', 'todo');

        // Detail markup is emitted server-side regardless of Alpine's
        // open/close (x-show only toggles CSS display) -- so it's already
        // present in the initial HTML for the test to find.
        $html = Livewire::actingAs($admin)->test(Roadmap::class, ['project' => $project])->html();

        $this->assertStringContainsString('Task Yang Terlihat Saat Klik', $html);
    }

    public function test_a_non_member_cannot_view_the_roadmap_tab(): void
    {
        $admin = $this->admin();
        $outsider = User::create([
            'name' => 'Outsider', 'email' => 'outsider@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);
        $project = $this->project($admin);

        $this->actingAs($outsider)->get("/eksekusi/projects/{$project->id}/roadmap")->assertForbidden();
    }
}

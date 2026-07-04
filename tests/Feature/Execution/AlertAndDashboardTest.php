<?php

namespace Tests\Feature\Execution;

use App\Livewire\Admin\Dashboard;
use App\Models\ActivityLog;
use App\Models\Milestone;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Services\Execution\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AlertAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'admin',
            'membership_status' => 'active',
        ]);
    }

    private function executionMember(string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower($name).'@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'execution_member',
            'membership_status' => 'active',
        ]);
    }

    private function project(User $admin, string $status = 'active'): Project
    {
        return Project::create([
            'title' => 'Website Portfolio RIT',
            'description' => 'Deskripsi',
            'objective' => 'Tujuan',
            'project_type' => 'internal',
            'status' => $status,
            'start_date' => '2026-06-01',
            'target_end_date' => '2026-09-01',
            'created_by' => $admin->id,
        ]);
    }

    private function backdateLastActivityLog(Task $task, int $daysAgo): void
    {
        ActivityLog::where('task_id', $task->id)
            ->orderByDesc('created_at')
            ->first()
            ->forceFill(['created_at' => now()->subDays($daysAgo)])
            ->save();
    }

    public function test_overdue_task_is_flagged_but_excluded_when_project_on_hold(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin, 'active');
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'M1', 'target_date' => '2026-07-01', 'sort_order' => 1]);

        $overdue = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Overdue Task',
            'status' => 'in_progress', 'priority' => 'medium', 'deadline' => now()->subDays(2)->toDateString(), 'created_by' => $admin->id,
        ]);

        $service = app(AlertService::class);
        $this->assertTrue($service->overdueTasks()->pluck('id')->contains($overdue->id));

        $project->update(['status' => 'on_hold']);
        $this->assertFalse($service->overdueTasks()->pluck('id')->contains($overdue->id));
    }

    public function test_due_soon_task_is_flagged_within_threshold(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin, 'active');
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'M1', 'target_date' => '2026-07-01', 'sort_order' => 1]);

        $dueSoon = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Due Soon Task',
            'status' => 'todo', 'priority' => 'medium', 'deadline' => now()->addDays(2)->toDateString(), 'created_by' => $admin->id,
        ]);
        $farAway = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Far Away Task',
            'status' => 'todo', 'priority' => 'medium', 'deadline' => now()->addDays(20)->toDateString(), 'created_by' => $admin->id,
        ]);

        $service = app(AlertService::class);
        $ids = $service->dueSoonTasks()->pluck('id');
        $this->assertTrue($ids->contains($dueSoon->id));
        $this->assertFalse($ids->contains($farAway->id));
    }

    public function test_stalled_task_flagged_after_threshold_days_without_activity(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin, 'active');
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'M1', 'target_date' => '2026-07-01', 'sort_order' => 1]);

        $stalled = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Stalled Task',
            'status' => 'in_progress', 'priority' => 'medium', 'deadline' => now()->addDays(30)->toDateString(), 'created_by' => $admin->id,
        ]);
        ActivityLog::create(['project_id' => $project->id, 'task_id' => $stalled->id, 'user_id' => $admin->id, 'action_type' => 'task_created', 'description' => 'x']);
        $this->backdateLastActivityLog($stalled, 10);

        $fresh = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Fresh Task',
            'status' => 'in_progress', 'priority' => 'medium', 'deadline' => now()->addDays(30)->toDateString(), 'created_by' => $admin->id,
        ]);
        ActivityLog::create(['project_id' => $project->id, 'task_id' => $fresh->id, 'user_id' => $admin->id, 'action_type' => 'task_created', 'description' => 'x']);

        $service = app(AlertService::class);
        $ids = $service->stalledTasks()->pluck('id');
        $this->assertTrue($ids->contains($stalled->id));
        $this->assertFalse($ids->contains($fresh->id));
    }

    public function test_inactive_member_flagged_after_threshold_days_without_activity(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember('Riefki');
        $project = $this->project($admin, 'active');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'M1', 'target_date' => '2026-07-01', 'sort_order' => 1]);

        $task = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Task Riefki',
            'status' => 'todo', 'priority' => 'medium', 'deadline' => now()->addDays(30)->toDateString(), 'created_by' => $admin->id,
        ]);
        TaskAssignment::create(['task_id' => $task->id, 'user_id' => $member->id, 'assigned_by' => $admin->id]);
        ActivityLog::where('task_id', $task->id)->where('user_id', $admin->id)->delete();
        ActivityLog::create([
            'project_id' => $project->id, 'task_id' => $task->id, 'user_id' => $member->id,
            'action_type' => 'task_status_changed', 'description' => 'x',
        ]);
        $this->backdateLastActivityLog($task, 20);

        $service = app(AlertService::class);
        $this->assertTrue($service->inactiveMembers()->pluck('id')->contains($member->id));
    }

    public function test_milestone_at_risk_when_target_date_passed_with_incomplete_tasks(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin, 'active');
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'Setup', 'target_date' => now()->subDays(3)->toDateString(), 'sort_order' => 1]);
        Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Belum Selesai',
            'status' => 'todo', 'priority' => 'medium', 'deadline' => now()->addDays(5)->toDateString(), 'created_by' => $admin->id,
        ]);

        $service = app(AlertService::class);
        $this->assertTrue($service->milestonesAtRisk()->pluck('id')->contains($milestone->id));
    }

    public function test_project_idle_when_no_activity_log_within_threshold(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin, 'active');
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'M1', 'target_date' => '2026-07-01', 'sort_order' => 1]);
        $task = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'T1',
            'status' => 'todo', 'priority' => 'medium', 'deadline' => now()->addDays(30)->toDateString(), 'created_by' => $admin->id,
        ]);
        ActivityLog::where('project_id', $project->id)->get()->each(
            fn (ActivityLog $log) => $log->forceFill(['created_at' => now()->subDays(20)])->save()
        );

        $service = app(AlertService::class);
        $this->assertTrue($service->idleProjects()->pluck('id')->contains($project->id));
    }

    public function test_notify_new_alerts_does_not_duplicate_on_repeated_runs(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin, 'active');
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'M1', 'target_date' => '2026-07-01', 'sort_order' => 1]);
        Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Overdue Task',
            'status' => 'in_progress', 'priority' => 'medium', 'deadline' => now()->subDays(2)->toDateString(), 'created_by' => $admin->id,
        ]);

        $service = app(AlertService::class);
        $service->notifyNewAlerts();
        $countAfterFirst = Notification::where('type', 'task_overdue')->count();

        $service->notifyNewAlerts();
        $countAfterSecond = Notification::where('type', 'task_overdue')->count();

        $this->assertSame($countAfterFirst, $countAfterSecond);
        $this->assertGreaterThan(0, $countAfterFirst);
    }

    public function test_admin_dashboard_shows_project_alerts_feed_and_member_summary(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember('Ahmad');
        $project = $this->project($admin, 'active');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'M1', 'target_date' => '2026-07-01', 'sort_order' => 1]);

        $task = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Overdue Task',
            'status' => 'in_progress', 'priority' => 'medium', 'deadline' => now()->subDays(2)->toDateString(), 'created_by' => $admin->id,
        ]);
        TaskAssignment::create(['task_id' => $task->id, 'user_id' => $member->id, 'assigned_by' => $admin->id]);
        \App\Models\ProgressUpdate::create(['task_id' => $task->id, 'user_id' => $member->id, 'content' => 'Progres terbaru dari Ahmad']);

        Livewire::actingAs($admin)->test(Dashboard::class)
            ->assertSee('Website Portfolio RIT')
            ->assertSee('OVERDUE')
            ->assertSee('Progres terbaru dari Ahmad')
            ->assertSee('Ahmad');
    }
}

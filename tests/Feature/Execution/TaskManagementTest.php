<?php

namespace Tests\Feature\Execution;

use App\Livewire\Eksekusi\Projects\Board;
use App\Livewire\Eksekusi\Tasks\Create;
use App\Livewire\Eksekusi\Tasks\Show;
use App\Models\Milestone;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskManagementTest extends TestCase
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

    private function executionMember(string $name = 'Ahmad'): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower($name).'@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'execution_member',
            'membership_status' => 'active',
        ]);
    }

    private function projectWithMilestone(User $admin, string $status = 'planning'): array
    {
        $project = Project::create([
            'title' => 'Website Portfolio RIT',
            'description' => 'Deskripsi',
            'objective' => 'Tujuan',
            'project_type' => 'internal',
            'status' => $status,
            'start_date' => '2026-07-01',
            'target_end_date' => '2026-08-01',
            'created_by' => $admin->id,
        ]);

        $milestone = Milestone::create([
            'project_id' => $project->id,
            'title' => 'Setup dan Desain',
            'target_date' => '2026-07-15',
            'sort_order' => 1,
        ]);

        return [$project, $milestone];
    }

    public function test_creating_first_task_moves_project_from_planning_to_active(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember();
        [$project, $milestone] = $this->projectWithMilestone($admin, 'planning');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        Livewire::actingAs($admin)->test(Create::class, ['project' => $project])
            ->set('milestoneId', $milestone->id)
            ->set('title', 'Buat halaman login')
            ->set('priority', 'medium')
            ->set('deadline', '2026-07-20')
            ->set('assigneeIds', [$member->id])
            ->call('save');

        $project->refresh();
        $this->assertSame('active', $project->status);

        $task = Task::where('title', 'Buat halaman login')->first();
        $this->assertNotNull($task);
        $this->assertSame('todo', $task->status);

        $this->assertDatabaseHas('activity_logs', ['project_id' => $project->id, 'action_type' => 'task_created']);
        $this->assertDatabaseHas('activity_logs', ['project_id' => $project->id, 'action_type' => 'project_status_changed']);
        $this->assertDatabaseHas('activity_logs', ['project_id' => $project->id, 'task_id' => $task->id, 'action_type' => 'task_assigned']);
        $this->assertDatabaseHas('notifications', ['recipient_id' => $member->id, 'type' => 'task_assigned']);
    }

    public function test_project_member_can_create_task_in_their_project(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember();
        [$project, $milestone] = $this->projectWithMilestone($admin, 'active');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        Livewire::actingAs($member)->test(Create::class, ['project' => $project])
            ->set('milestoneId', $milestone->id)
            ->set('title', 'Setup database schema')
            ->set('priority', 'high')
            ->set('deadline', '2026-07-18')
            ->call('save');

        $this->assertDatabaseHas('tasks', ['title' => 'Setup database schema', 'created_by' => $member->id]);
    }

    public function test_non_member_cannot_create_task(): void
    {
        $admin = $this->admin();
        $outsider = $this->executionMember('Riefki');
        [$project, $milestone] = $this->projectWithMilestone($admin, 'active');

        $this->actingAs($outsider)->get('/eksekusi/projects/'.$project->id.'/tasks/create')->assertForbidden();
    }

    public function test_assigned_member_can_move_task_through_todo_to_in_review_but_not_to_done(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember();
        [$project, $milestone] = $this->projectWithMilestone($admin, 'active');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        $task = Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'title' => 'Buat halaman utama',
            'status' => 'todo',
            'priority' => 'medium',
            'deadline' => '2026-07-20',
            'created_by' => $admin->id,
        ]);
        \App\Models\TaskAssignment::create(['task_id' => $task->id, 'user_id' => $member->id, 'assigned_by' => $admin->id]);

        Livewire::actingAs($member)->test(Show::class, ['task' => $task])
            ->call('changeStatus', 'in_progress');
        $task->refresh();
        $this->assertSame('in_progress', $task->status);

        Livewire::actingAs($member)->test(Show::class, ['task' => $task])
            ->call('changeStatus', 'in_review');
        $task->refresh();
        $this->assertSame('in_review', $task->status);

        // member cannot push it to done
        Livewire::actingAs($member)->test(Show::class, ['task' => $task])
            ->call('changeStatus', 'done')
            ->assertHasErrors('status');
        $task->refresh();
        $this->assertSame('in_review', $task->status);
    }

    public function test_unassigned_project_member_cannot_change_task_status(): void
    {
        $admin = $this->admin();
        $assignee = $this->executionMember('Ahmad');
        $otherMember = $this->executionMember('Azmi');
        [$project, $milestone] = $this->projectWithMilestone($admin, 'active');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $assignee->id]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $otherMember->id]);

        $task = Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'title' => 'Buat halaman utama',
            'status' => 'todo',
            'priority' => 'medium',
            'deadline' => '2026-07-20',
            'created_by' => $admin->id,
        ]);
        \App\Models\TaskAssignment::create(['task_id' => $task->id, 'user_id' => $assignee->id, 'assigned_by' => $admin->id]);

        Livewire::actingAs($otherMember)->test(Show::class, ['task' => $task])
            ->call('changeStatus', 'in_progress')
            ->assertHasErrors('status');

        $task->refresh();
        $this->assertSame('todo', $task->status);
    }

    public function test_admin_can_approve_review_and_send_back_for_revision(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember();
        [$project, $milestone] = $this->projectWithMilestone($admin, 'active');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        $task = Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'title' => 'Buat halaman utama',
            'status' => 'in_review',
            'priority' => 'medium',
            'deadline' => '2026-07-20',
            'created_by' => $admin->id,
        ]);
        \App\Models\TaskAssignment::create(['task_id' => $task->id, 'user_id' => $member->id, 'assigned_by' => $admin->id]);

        Livewire::actingAs($admin)->test(Show::class, ['task' => $task])
            ->call('changeStatus', 'in_progress');
        $task->refresh();
        $this->assertSame('in_progress', $task->status);
        $this->assertDatabaseHas('notifications', ['recipient_id' => $member->id, 'type' => 'task_revision_needed']);

        $task->update(['status' => 'in_review']);
        Livewire::actingAs($admin)->test(Show::class, ['task' => $task])
            ->call('changeStatus', 'done');
        $task->refresh();
        $this->assertSame('done', $task->status);
    }

    public function test_admin_can_change_deadline_priority_and_reassign_but_member_cannot(): void
    {
        $admin = $this->admin();
        $memberA = $this->executionMember('Ahmad');
        $memberB = $this->executionMember('Azmi');
        [$project, $milestone] = $this->projectWithMilestone($admin, 'active');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $memberA->id]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $memberB->id]);

        $task = Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'title' => 'Buat halaman utama',
            'status' => 'todo',
            'priority' => 'medium',
            'deadline' => '2026-07-20',
            'created_by' => $admin->id,
        ]);
        \App\Models\TaskAssignment::create(['task_id' => $task->id, 'user_id' => $memberA->id, 'assigned_by' => $admin->id]);

        Livewire::actingAs($admin)->test(Show::class, ['task' => $task])
            ->set('newDeadline', '2026-07-25')
            ->call('changeDeadline');
        $task->refresh();
        $this->assertSame('2026-07-25', $task->deadline->toDateString());

        Livewire::actingAs($admin)->test(Show::class, ['task' => $task])
            ->set('newPriority', 'high')
            ->call('changePriority');
        $task->refresh();
        $this->assertSame('high', $task->priority);

        Livewire::actingAs($admin)->test(Show::class, ['task' => $task])
            ->set('reassignFromId', $memberA->id)
            ->set('reassignToId', $memberB->id)
            ->call('reassign');

        $this->assertDatabaseMissing('task_assignments', ['task_id' => $task->id, 'user_id' => $memberA->id]);
        $this->assertDatabaseHas('task_assignments', ['task_id' => $task->id, 'user_id' => $memberB->id]);
        $this->assertDatabaseHas('notifications', ['recipient_id' => $memberA->id, 'type' => 'task_reassigned_from']);
        $this->assertDatabaseHas('notifications', ['recipient_id' => $memberB->id, 'type' => 'task_reassigned_to']);

        // member cannot do any of these
        Livewire::actingAs($memberB)->test(Show::class, ['task' => $task])
            ->set('newDeadline', '2026-08-01')
            ->call('changeDeadline')
            ->assertForbidden();
    }

    public function test_kanban_board_groups_tasks_by_status(): void
    {
        $admin = $this->admin();
        [$project, $milestone] = $this->projectWithMilestone($admin, 'active');

        Task::create(['project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'T1', 'status' => 'todo', 'priority' => 'low', 'deadline' => '2026-07-20', 'created_by' => $admin->id]);
        Task::create(['project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'T2', 'status' => 'in_progress', 'priority' => 'low', 'deadline' => '2026-07-20', 'created_by' => $admin->id]);
        Task::create(['project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'T3', 'status' => 'done', 'priority' => 'low', 'deadline' => '2026-07-20', 'created_by' => $admin->id]);

        Livewire::actingAs($admin)->test(Board::class, ['project' => $project])
            ->assertSee('T1')
            ->assertSee('T2')
            ->assertSee('T3')
            ->assertSee('Todo (1)')
            ->assertSee('In Progress (1)')
            ->assertSee('Done (1)');
    }

    public function test_admin_can_delete_task_but_member_cannot(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember();
        [$project, $milestone] = $this->projectWithMilestone($admin, 'active');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        $task = Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'title' => 'Task Sementara',
            'status' => 'todo',
            'priority' => 'low',
            'deadline' => '2026-07-20',
            'created_by' => $admin->id,
        ]);

        Livewire::actingAs($member)->test(Show::class, ['task' => $task])
            ->call('delete')
            ->assertForbidden();
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);

        Livewire::actingAs($admin)->test(Show::class, ['task' => $task])->call('delete');
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertDatabaseHas('activity_logs', ['project_id' => $project->id, 'action_type' => 'task_deleted']);
    }
}

<?php

namespace Tests\Feature\Execution;

use App\Livewire\Eksekusi\Projects\Create;
use App\Livewire\Eksekusi\Projects\Show;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectManagementTest extends TestCase
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

    private function executionMember(string $name = 'Azmi'): User
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
            'start_date' => '2026-07-01',
            'target_end_date' => '2026-08-01',
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_can_create_project_directly(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(Create::class)
            ->set('title', 'Website Portfolio RIT')
            ->set('description', 'Deskripsi detail')
            ->set('objective', 'Tujuan terukur')
            ->set('projectType', 'internal')
            ->set('startDate', '2026-07-01')
            ->set('targetEndDate', '2026-08-01')
            ->call('save');

        $project = Project::where('title', 'Website Portfolio RIT')->first();
        $this->assertNotNull($project);
        $this->assertSame('planning', $project->status);
        $this->assertNull($project->originated_from_idea_id);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'action_type' => 'project_created',
        ]);
    }

    public function test_execution_member_cannot_create_project_directly(): void
    {
        $member = $this->executionMember();

        $this->actingAs($member)->get('/eksekusi/projects/create')->assertForbidden();
    }

    public function test_execution_member_cannot_view_project_they_are_not_member_of(): void
    {
        $admin = $this->admin();
        $outsider = $this->executionMember('Riefki');
        $project = $this->project($admin);

        $this->actingAs($outsider)->get('/eksekusi/projects/'.$project->id)->assertForbidden();
    }

    public function test_execution_member_can_view_project_they_belong_to(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember();
        $project = $this->project($admin);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        $this->actingAs($member)->get('/eksekusi/projects/'.$project->id)->assertOk();
    }

    public function test_admin_can_add_and_remove_member(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember();
        $project = $this->project($admin);

        Livewire::actingAs($admin)->test(Show::class, ['project' => $project])
            ->set('newMemberId', $member->id)
            ->call('addMember');

        $this->assertDatabaseHas('project_members', ['project_id' => $project->id, 'user_id' => $member->id]);
        $this->assertDatabaseHas('activity_logs', ['project_id' => $project->id, 'action_type' => 'project_member_added']);
        $this->assertDatabaseHas('notifications', ['recipient_id' => $member->id, 'type' => 'added_to_project']);

        Livewire::actingAs($admin)->test(Show::class, ['project' => $project])
            ->call('removeMember', $member->id);

        $this->assertDatabaseMissing('project_members', ['project_id' => $project->id, 'user_id' => $member->id]);
        $this->assertDatabaseHas('activity_logs', ['project_id' => $project->id, 'action_type' => 'project_member_removed']);
    }

    public function test_execution_member_cannot_add_member(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember();
        $another = $this->executionMember('Riefki');
        $project = $this->project($admin);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        Livewire::actingAs($member)->test(Show::class, ['project' => $project])
            ->set('newMemberId', $another->id)
            ->call('addMember')
            ->assertForbidden();
    }

    public function test_admin_can_add_milestone_with_incrementing_sort_order(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);

        Livewire::actingAs($admin)->test(Show::class, ['project' => $project])
            ->set('milestoneTitle', 'Setup dan Desain')
            ->set('milestoneTargetDate', '2026-07-10')
            ->call('addMilestone');

        Livewire::actingAs($admin)->test(Show::class, ['project' => $project])
            ->set('milestoneTitle', 'Development')
            ->set('milestoneTargetDate', '2026-07-20')
            ->call('addMilestone');

        $milestones = Milestone::where('project_id', $project->id)->orderBy('sort_order')->get();
        $this->assertCount(2, $milestones);
        $this->assertSame(1, $milestones[0]->sort_order);
        $this->assertSame(2, $milestones[1]->sort_order);
        $this->assertDatabaseHas('activity_logs', ['project_id' => $project->id, 'action_type' => 'milestone_created']);
    }

    public function test_active_project_can_be_paused_and_resumed(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin, 'active');

        Livewire::actingAs($admin)->test(Show::class, ['project' => $project])->call('changeStatus', 'on_hold');
        $project->refresh();
        $this->assertSame('on_hold', $project->status);

        Livewire::actingAs($admin)->test(Show::class, ['project' => $project])->call('changeStatus', 'active');
        $project->refresh();
        $this->assertSame('active', $project->status);
    }

    public function test_project_cannot_be_completed_with_incomplete_tasks(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin, 'active');
        $milestone = Milestone::create([
            'project_id' => $project->id,
            'title' => 'M1',
            'target_date' => '2026-07-10',
            'sort_order' => 1,
        ]);
        \App\Models\Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'title' => 'Task 1',
            'status' => 'in_progress',
            'priority' => 'medium',
            'deadline' => '2026-07-15',
            'created_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)->test(Show::class, ['project' => $project])
            ->call('changeStatus', 'completed');

        $project->refresh();
        $this->assertSame('active', $project->status);
    }

    public function test_manual_transition_from_planning_to_active_is_rejected(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin, 'planning');

        Livewire::actingAs($admin)->test(Show::class, ['project' => $project])
            ->call('changeStatus', 'active');

        $project->refresh();
        $this->assertSame('planning', $project->status);
    }

    public function test_archived_project_cannot_transition_further(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin, 'archived');

        Livewire::actingAs($admin)->test(Show::class, ['project' => $project])
            ->call('changeStatus', 'active');

        $project->refresh();
        $this->assertSame('archived', $project->status);
    }
}

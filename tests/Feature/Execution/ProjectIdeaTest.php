<?php

namespace Tests\Feature\Execution;

use App\Livewire\Eksekusi\Ideas\Approve;
use App\Livewire\Eksekusi\Ideas\Create;
use App\Livewire\Eksekusi\Ideas\Index;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectIdea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectIdeaTest extends TestCase
{
    use RefreshDatabase;

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

    private function explorationMember(): User
    {
        return User::create([
            'name' => 'Explorer',
            'email' => 'explorer@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
            'membership_status' => 'active',
        ]);
    }

    public function test_execution_member_can_propose_idea_via_real_form(): void
    {
        $user = $this->executionMember();

        Livewire::actingAs($user)->test(Create::class)
            ->set('title', 'Website Portfolio Divisi Webdev RIT')
            ->set('description', 'Butuh landing page untuk profil divisi.')
            ->set('purpose', 'Supaya divisi webdev punya presence online yang profesional.')
            ->call('save');

        $idea = ProjectIdea::where('title', 'Website Portfolio Divisi Webdev RIT')->first();
        $this->assertNotNull($idea);
        $this->assertSame('draft', $idea->status);
        $this->assertSame($user->id, $idea->proposed_by);
    }

    public function test_idea_form_requires_all_fields(): void
    {
        $user = $this->executionMember();

        Livewire::actingAs($user)->test(Create::class)
            ->set('title', '')
            ->call('save')
            ->assertHasErrors(['title', 'description', 'purpose']);

        $this->assertDatabaseCount('project_ideas', 0);
    }

    public function test_exploration_member_cannot_access_eksekusi_ideas(): void
    {
        $user = $this->explorationMember();

        $this->actingAs($user)->get('/eksekusi/ideas')->assertForbidden();
        $this->actingAs($user)->get('/eksekusi/ideas/create')->assertForbidden();
    }

    public function test_admin_can_approve_idea_and_project_is_created_with_copied_data(): void
    {
        $admin = $this->admin();
        $proposer = $this->executionMember();

        $idea = ProjectIdea::create([
            'title' => 'Aplikasi Kasir Kantin',
            'description' => 'Deskripsi singkat',
            'purpose' => 'Relevansi proyek',
            'proposed_by' => $proposer->id,
            'status' => 'draft',
        ]);

        Livewire::actingAs($admin)->test(Approve::class, ['idea' => $idea])
            ->set('projectType', 'internal')
            ->set('startDate', '2026-07-10')
            ->set('targetEndDate', '2026-08-10')
            ->call('save');

        $idea->refresh();
        $this->assertSame('approved', $idea->status);
        $this->assertNotNull($idea->promoted_to_project_id);

        $project = Project::find($idea->promoted_to_project_id);
        $this->assertNotNull($project);
        $this->assertSame('Aplikasi Kasir Kantin', $project->title);
        $this->assertSame('Deskripsi singkat', $project->description);
        $this->assertSame('Relevansi proyek', $project->objective);
        $this->assertSame('internal', $project->project_type);
        $this->assertSame('planning', $project->status);
        $this->assertSame($idea->id, $project->originated_from_idea_id);
        $this->assertSame($admin->id, $project->created_by);

        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'action_type' => 'idea_approved',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'project_id' => $project->id,
            'action_type' => 'project_created',
        ]);

        $notification = Notification::where('recipient_id', $proposer->id)->first();
        $this->assertNotNull($notification);
        $this->assertSame('idea_status_changed', $notification->type);
    }

    public function test_execution_member_cannot_approve_idea(): void
    {
        $admin = $this->admin();
        $proposer = $this->executionMember();

        $idea = ProjectIdea::create([
            'title' => 'Ide Lain',
            'description' => 'x',
            'purpose' => 'y',
            'proposed_by' => $proposer->id,
            'status' => 'draft',
        ]);

        $this->actingAs($proposer)->get('/eksekusi/ideas/'.$idea->id.'/approve')->assertForbidden();
    }

    public function test_admin_reject_requires_reason_and_does_not_delete_idea(): void
    {
        $admin = $this->admin();
        $proposer = $this->executionMember();

        $idea = ProjectIdea::create([
            'title' => 'Aplikasi Kasir Kantin',
            'description' => 'x',
            'purpose' => 'y',
            'proposed_by' => $proposer->id,
            'status' => 'draft',
        ]);

        // empty reason rejected
        Livewire::actingAs($admin)->test(Index::class)
            ->set('rejectReasons.'.$idea->id, '')
            ->call('reject', $idea->id)
            ->assertHasErrors('rejectReasons.'.$idea->id);

        $idea->refresh();
        $this->assertSame('draft', $idea->status);

        // valid reason works
        Livewire::actingAs($admin)->test(Index::class)
            ->set('rejectReasons.'.$idea->id, 'Di luar scope divisi webdev')
            ->call('reject', $idea->id);

        $idea->refresh();
        $this->assertSame('rejected', $idea->status);
        $this->assertSame('Di luar scope divisi webdev', $idea->rejection_reason);
        // still stored as history, not deleted
        $this->assertDatabaseHas('project_ideas', ['id' => $idea->id]);

        $notification = Notification::where('recipient_id', $proposer->id)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Di luar scope divisi webdev', $notification->message);
    }

    public function test_execution_member_cannot_reject_idea(): void
    {
        $proposer = $this->executionMember();

        $idea = ProjectIdea::create([
            'title' => 'Ide Lain',
            'description' => 'x',
            'purpose' => 'y',
            'proposed_by' => $proposer->id,
            'status' => 'draft',
        ]);

        Livewire::actingAs($proposer)->test(Index::class)
            ->set('rejectReasons.'.$idea->id, 'Alasan apapun')
            ->call('reject', $idea->id)
            ->assertForbidden();
    }
}

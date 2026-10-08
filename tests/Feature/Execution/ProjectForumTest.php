<?php

namespace Tests\Feature\Execution;

use App\Livewire\Eksekusi\Projects\Tabs\Forum;
use App\Models\ForumThread;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 7 Batch 4: "Forum Proyek" (docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Modul_Manajemen_Proyek_v2.md
 * §5) — ForumThread.project_id set, reusing App\Services\Forum\ForumService
 * (the SAME class Eksplorasi's own forum now goes through — see
 * tests\Feature\Exploration\ResourcesAndForumTest.php, unmodified, for the
 * non-regression proof on that side). This file covers the NEW surface:
 * project-scoped CRUD, RBAC via project.member, and isolation from both
 * other projects and from Eksplorasi's forum.
 */
class ProjectForumTest extends TestCase
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

    private function project(User $admin, string $title = 'Proyek A'): Project
    {
        return Project::create([
            'title' => $title, 'description' => 'D', 'objective' => 'T',
            'project_type' => 'internal', 'status' => 'active',
            'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);
    }

    // ------------------------------------------------------------------
    // CRUD
    // ------------------------------------------------------------------

    public function test_creating_a_thread_scopes_it_to_the_project_with_a_null_target(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);

        Livewire::actingAs($admin)->test(Forum::class, ['project' => $project])
            ->set('newThreadTitle', 'Diskusi Desain')
            ->set('newThreadContent', 'Bagaimana pendapat kalian soal warna primer?')
            ->call('createThread')
            ->assertHasNoErrors();

        $thread = ForumThread::where('title', 'Diskusi Desain')->sole();
        $this->assertSame($project->id, $thread->project_id);
        $this->assertNull($thread->target);
        $this->assertNull($thread->module_id);
        $this->assertNull($thread->unit_id);
    }

    public function test_createThread_requires_a_title_and_content(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);

        Livewire::actingAs($admin)->test(Forum::class, ['project' => $project])
            ->set('newThreadTitle', '')
            ->set('newThreadContent', '')
            ->call('createThread')
            ->assertHasErrors(['newThreadTitle', 'newThreadContent']);

        $this->assertSame(0, ForumThread::count());
    }

    public function test_opening_a_thread_and_replying_persists_the_reply(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $project = $this->project($admin);
        \App\Models\ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        $component = Livewire::actingAs($admin)->test(Forum::class, ['project' => $project])
            ->set('newThreadTitle', 'Thread A')
            ->set('newThreadContent', 'Isi thread A')
            ->call('createThread');

        $thread = ForumThread::sole();

        Livewire::actingAs($member)->test(Forum::class, ['project' => $project])
            ->call('openThread', $thread->id)
            ->set('replyContent', 'Setuju!')
            ->call('reply')
            ->assertHasNoErrors()
            ->assertSeeText('Setuju!');

        $this->assertTrue($thread->replies()->where('content', 'Setuju!')->where('user_id', $member->id)->exists());
    }

    public function test_replying_notifies_the_thread_creator_via_the_execution_notifier(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $project = $this->project($admin);
        \App\Models\ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        Livewire::actingAs($admin)->test(Forum::class, ['project' => $project])
            ->set('newThreadTitle', 'Thread A')
            ->set('newThreadContent', 'Isi')
            ->call('createThread');
        $thread = ForumThread::sole();

        Livewire::actingAs($member)->test(Forum::class, ['project' => $project])
            ->call('openThread', $thread->id)
            ->set('replyContent', 'Balasan')
            ->call('reply');

        $notification = Notification::where('recipient_id', $admin->id)
            ->where('type', 'forum_reply_received')
            ->sole();

        $this->assertSame('forum_thread', $notification->context_type);
        $this->assertSame($thread->id, $notification->context_id);
        $this->assertSame(
            url("/eksekusi/projects/{$project->id}/forum?thread={$thread->id}"),
            $notification->linkUrl()
        );
    }

    public function test_replying_to_your_own_thread_does_not_notify_yourself(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);

        Livewire::actingAs($admin)->test(Forum::class, ['project' => $project])
            ->set('newThreadTitle', 'Thread A')
            ->set('newThreadContent', 'Isi')
            ->call('createThread');
        $thread = ForumThread::sole();

        Livewire::actingAs($admin)->test(Forum::class, ['project' => $project])
            ->call('openThread', $thread->id)
            ->set('replyContent', 'Balasan sendiri')
            ->call('reply');

        $this->assertSame(0, Notification::where('type', 'forum_reply_received')->count());
    }

    // ------------------------------------------------------------------
    // Isolasi lintas-proyek & dari Forum Eksplorasi
    // ------------------------------------------------------------------

    public function test_a_thread_from_another_project_never_appears_in_this_projects_forum_tab(): void
    {
        $admin = $this->admin();
        $projectA = $this->project($admin, 'Proyek A');
        $projectB = $this->project($admin, 'Proyek B');

        Livewire::actingAs($admin)->test(Forum::class, ['project' => $projectB])
            ->set('newThreadTitle', 'Thread Proyek B')
            ->set('newThreadContent', 'Isi')
            ->call('createThread');

        $html = Livewire::actingAs($admin)->test(Forum::class, ['project' => $projectA])->html();

        $this->assertStringNotContainsString('Thread Proyek B', $html);
    }

    public function test_replying_is_scoped_to_threads_belonging_to_this_project(): void
    {
        $admin = $this->admin();
        $projectA = $this->project($admin, 'Proyek A');
        $projectB = $this->project($admin, 'Proyek B');

        Livewire::actingAs($admin)->test(Forum::class, ['project' => $projectB])
            ->set('newThreadTitle', 'Thread Proyek B')
            ->set('newThreadContent', 'Isi')
            ->call('createThread');
        $foreignThread = ForumThread::sole();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($admin)->test(Forum::class, ['project' => $projectA])
            ->set('openThreadId', $foreignThread->id)
            ->set('replyContent', 'Coba balas')
            ->call('reply');
    }

    public function test_a_project_thread_never_appears_in_the_exploration_forum_index(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);

        Livewire::actingAs($admin)->test(Forum::class, ['project' => $project])
            ->set('newThreadTitle', 'Thread Eksekusi Rahasia')
            ->set('newThreadContent', 'Isi')
            ->call('createThread');

        $explorationMember = User::factory()->create();
        $html = Livewire::actingAs($explorationMember)->test(\App\Livewire\Eksplorasi\Forum\Index::class)->html();

        $this->assertStringNotContainsString('Thread Eksekusi Rahasia', $html);
    }

    public function test_a_project_thread_404s_when_visited_via_the_exploration_forum_show_route(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);

        Livewire::actingAs($admin)->test(Forum::class, ['project' => $project])
            ->set('newThreadTitle', 'Thread Rahasia')
            ->set('newThreadContent', 'Isi')
            ->call('createThread');
        $thread = ForumThread::sole();

        $explorationMember = User::factory()->create();
        $this->actingAs($explorationMember)->get("/eksplorasi/forum/{$thread->id}")->assertNotFound();
    }

    public function test_an_exploration_thread_never_appears_in_a_project_forum_tab(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);
        $explorationMember = User::factory()->create();

        Livewire::actingAs($explorationMember)->test(\App\Livewire\Eksplorasi\Forum\Create::class)
            ->set('title', 'Thread Eksplorasi Umum')
            ->set('content', 'Isi')
            ->set('target', 'peer')
            ->call('save');

        $html = Livewire::actingAs($admin)->test(Forum::class, ['project' => $project])->html();

        $this->assertStringNotContainsString('Thread Eksplorasi Umum', $html);
    }

    // ------------------------------------------------------------------
    // RBAC
    // ------------------------------------------------------------------

    public function test_a_non_member_cannot_view_the_project_forum_tab(): void
    {
        $admin = $this->admin();
        $outsider = $this->member('Outsider', 'outsider@example.test');
        $project = $this->project($admin);

        $this->actingAs($outsider)->get("/eksekusi/projects/{$project->id}/forum")->assertForbidden();
    }

    public function test_an_exploration_member_cannot_view_the_project_forum_tab(): void
    {
        $admin = $this->admin();
        $explorationMember = User::factory()->create();
        $project = $this->project($admin);

        $this->actingAs($explorationMember)->get("/eksekusi/projects/{$project->id}/forum")->assertForbidden();
    }

    public function test_a_project_member_can_view_create_and_reply(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $project = $this->project($admin);
        \App\Models\ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        Livewire::actingAs($member)->test(Forum::class, ['project' => $project])
            ->set('newThreadTitle', 'Thread Anggota')
            ->set('newThreadContent', 'Isi')
            ->call('createThread')
            ->assertHasNoErrors();

        $this->assertTrue(ForumThread::where('title', 'Thread Anggota')->where('created_by', $member->id)->exists());
    }
}

<?php

namespace Tests\Feature\Execution;

use App\Livewire\Eksekusi\Forum\Create as GeneralForumCreate;
use App\Livewire\Eksekusi\Forum\Index as GeneralForumIndex;
use App\Livewire\Eksekusi\Forum\Show as GeneralForumShow;
use App\Livewire\Eksekusi\Projects\Tabs\Forum as ProjectForum;
use App\Models\ForumThread;
use App\Models\Module;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Forum General Eksekusi ("utang Fase 7 Batch 4") — thread lintas-proyek
 * (`portal = 'execution'`, `project_id = null`). Closes the design gap
 * that blocked this feature: `forum_threads.portal` (new column) now
 * distinguishes it from an Eksplorasi "General" thread, which ALSO has
 * `project_id = null` — before this column existed, the two were
 * structurally indistinguishable. See CLAUDE.md for the full gap history.
 */
class GeneralForumTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function executionMember(string $name = 'Executor', string $email = 'exec@example.test'): User
    {
        return User::create([
            'name' => $name, 'email' => $email,
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);
    }

    private function explorationMember(): User
    {
        return User::factory()->create();
    }

    // ------------------------------------------------------------------
    // RBAC
    // ------------------------------------------------------------------

    public function test_execution_member_and_admin_can_view_the_general_forum(): void
    {
        $this->actingAs($this->executionMember())->get('/eksekusi/forum')->assertOk();
        $this->actingAs($this->admin())->get('/eksekusi/forum')->assertOk();
    }

    public function test_exploration_member_cannot_view_the_general_forum(): void
    {
        $this->actingAs($this->explorationMember())->get('/eksekusi/forum')->assertForbidden();
    }

    public function test_exploration_member_cannot_open_create_or_show_routes(): void
    {
        $user = $this->executionMember();
        $thread = ForumThread::create([
            'portal' => 'execution', 'created_by' => $user->id,
            'title' => 'Thread', 'content' => 'Isi',
        ]);

        $exploration = $this->explorationMember();
        $this->actingAs($exploration)->get('/eksekusi/forum/create')->assertForbidden();
        $this->actingAs($exploration)->get("/eksekusi/forum/{$thread->id}")->assertForbidden();
    }

    // ------------------------------------------------------------------
    // CRUD
    // ------------------------------------------------------------------

    public function test_creating_a_thread_is_portal_execution_with_no_project_module_or_unit(): void
    {
        $user = $this->executionMember();

        Livewire::actingAs($user)->test(GeneralForumCreate::class)
            ->set('title', 'Diskusi Umum Eksekusi')
            ->set('content', 'Ada yang mau usul proses baru?')
            ->call('save')
            ->assertHasNoErrors();

        $thread = ForumThread::where('title', 'Diskusi Umum Eksekusi')->sole();
        $this->assertSame('execution', $thread->portal);
        $this->assertNull($thread->project_id);
        $this->assertNull($thread->module_id);
        $this->assertNull($thread->unit_id);
        $this->assertNull($thread->target);
        $this->assertSame($user->id, $thread->created_by);
    }

    public function test_save_requires_a_title_and_content(): void
    {
        Livewire::actingAs($this->executionMember())->test(GeneralForumCreate::class)
            ->set('title', '')
            ->set('content', '')
            ->call('save')
            ->assertHasErrors(['title', 'content']);

        $this->assertSame(0, ForumThread::count());
    }

    public function test_opening_a_thread_and_replying_persists_the_reply(): void
    {
        $creator = $this->executionMember('Creator', 'creator@example.test');
        $replier = $this->executionMember('Replier', 'replier@example.test');
        $thread = ForumThread::create([
            'portal' => 'execution', 'created_by' => $creator->id,
            'title' => 'Thread', 'content' => 'Isi',
        ]);

        Livewire::actingAs($replier)->test(GeneralForumShow::class, ['thread' => $thread])
            ->set('replyContent', 'Setuju, ayo dicoba.')
            ->call('reply')
            ->assertHasNoErrors()
            ->assertSeeText('Setuju, ayo dicoba.');

        $this->assertTrue($thread->replies()->where('content', 'Setuju, ayo dicoba.')->where('user_id', $replier->id)->exists());
    }

    public function test_replying_notifies_the_thread_creator_with_the_correct_link(): void
    {
        $creator = $this->executionMember('Creator', 'creator@example.test');
        $replier = $this->executionMember('Replier', 'replier@example.test');
        $thread = ForumThread::create([
            'portal' => 'execution', 'created_by' => $creator->id,
            'title' => 'Thread', 'content' => 'Isi',
        ]);

        Livewire::actingAs($replier)->test(GeneralForumShow::class, ['thread' => $thread])
            ->set('replyContent', 'Balasan')
            ->call('reply');

        $notification = Notification::where('recipient_id', $creator->id)
            ->where('type', 'forum_reply_received')
            ->sole();

        $this->assertSame('forum_thread', $notification->context_type);
        $this->assertSame($thread->id, $notification->context_id);
        $this->assertSame(url("/eksekusi/forum/{$thread->id}"), $notification->linkUrl());
    }

    public function test_replying_to_your_own_thread_does_not_notify_yourself(): void
    {
        $user = $this->executionMember();
        $thread = ForumThread::create([
            'portal' => 'execution', 'created_by' => $user->id,
            'title' => 'Thread', 'content' => 'Isi',
        ]);

        Livewire::actingAs($user)->test(GeneralForumShow::class, ['thread' => $thread])
            ->set('replyContent', 'Balasan sendiri')
            ->call('reply');

        $this->assertSame(0, Notification::where('type', 'forum_reply_received')->count());
    }

    // ------------------------------------------------------------------
    // Isolasi lintas-portal (Eksplorasi / Forum Proyek / Forum General)
    // ------------------------------------------------------------------

    public function test_a_general_thread_never_appears_in_the_exploration_forum_index(): void
    {
        $user = $this->executionMember();
        ForumThread::create([
            'portal' => 'execution', 'created_by' => $user->id,
            'title' => 'Thread Eksekusi Rahasia', 'content' => 'Isi',
        ]);

        $html = Livewire::actingAs($this->explorationMember())->test(\App\Livewire\Eksplorasi\Forum\Index::class)->html();

        $this->assertStringNotContainsString('Thread Eksekusi Rahasia', $html);
    }

    public function test_an_exploration_thread_never_appears_in_the_general_forum_index(): void
    {
        $module = Module::create(['order_number' => 1, 'title' => 'Modul 1', 'description' => 'D', 'level_number' => 1]);
        $exploration = $this->explorationMember();
        ForumThread::create([
            'module_id' => $module->id, 'portal' => 'exploration', 'created_by' => $exploration->id,
            'title' => 'Thread Eksplorasi General', 'content' => 'Isi', 'target' => 'peer',
        ]);

        $html = Livewire::actingAs($this->executionMember())->test(GeneralForumIndex::class)->html();

        $this->assertStringNotContainsString('Thread Eksplorasi General', $html);
    }

    public function test_a_project_forum_thread_never_appears_in_the_general_forum_index(): void
    {
        $admin = $this->admin();
        $project = Project::create([
            'title' => 'Proyek A', 'description' => 'D', 'objective' => 'T',
            'project_type' => 'internal', 'status' => 'active',
            'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)->test(ProjectForum::class, ['project' => $project])
            ->set('newThreadTitle', 'Thread Proyek Spesifik')
            ->set('newThreadContent', 'Isi')
            ->call('createThread');

        $html = Livewire::actingAs($this->executionMember())->test(GeneralForumIndex::class)->html();

        $this->assertStringNotContainsString('Thread Proyek Spesifik', $html);
    }

    public function test_a_general_thread_never_appears_in_a_project_forum_tab(): void
    {
        $admin = $this->admin();
        $project = Project::create([
            'title' => 'Proyek A', 'description' => 'D', 'objective' => 'T',
            'project_type' => 'internal', 'status' => 'active',
            'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);
        ForumThread::create([
            'portal' => 'execution', 'created_by' => $admin->id,
            'title' => 'Thread General Bocor', 'content' => 'Isi',
        ]);

        $html = Livewire::actingAs($admin)->test(ProjectForum::class, ['project' => $project])->html();

        $this->assertStringNotContainsString('Thread General Bocor', $html);
    }

    public function test_a_general_thread_404s_when_visited_via_the_exploration_forum_show_route(): void
    {
        $user = $this->executionMember();
        $thread = ForumThread::create([
            'portal' => 'execution', 'created_by' => $user->id,
            'title' => 'Thread', 'content' => 'Isi',
        ]);

        $this->actingAs($this->explorationMember())->get("/eksplorasi/forum/{$thread->id}")->assertNotFound();
    }

    public function test_an_exploration_thread_404s_when_visited_via_the_general_forum_show_route(): void
    {
        $module = Module::create(['order_number' => 1, 'title' => 'Modul 1', 'description' => 'D', 'level_number' => 1]);
        $exploration = $this->explorationMember();
        $thread = ForumThread::create([
            'module_id' => $module->id, 'portal' => 'exploration', 'created_by' => $exploration->id,
            'title' => 'Thread', 'content' => 'Isi', 'target' => 'peer',
        ]);

        $this->actingAs($this->executionMember())->get("/eksekusi/forum/{$thread->id}")->assertNotFound();
    }

    public function test_a_project_forum_thread_404s_when_visited_via_the_general_forum_show_route(): void
    {
        $admin = $this->admin();
        $project = Project::create([
            'title' => 'Proyek A', 'description' => 'D', 'objective' => 'T',
            'project_type' => 'internal', 'status' => 'active',
            'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)->test(ProjectForum::class, ['project' => $project])
            ->set('newThreadTitle', 'Thread Proyek')
            ->set('newThreadContent', 'Isi')
            ->call('createThread');
        $thread = ForumThread::sole();

        $this->actingAs($this->executionMember())->get("/eksekusi/forum/{$thread->id}")->assertNotFound();
    }

    // ------------------------------------------------------------------
    // Notification::linkUrl() -- ketiga varian forum_thread
    // ------------------------------------------------------------------

    public function test_linkUrl_resolves_correctly_for_all_three_forum_thread_varieties(): void
    {
        $admin = $this->admin();
        $exploration = $this->explorationMember();
        $module = Module::create(['order_number' => 1, 'title' => 'Modul 1', 'description' => 'D', 'level_number' => 1]);
        $project = Project::create([
            'title' => 'Proyek A', 'description' => 'D', 'objective' => 'T',
            'project_type' => 'internal', 'status' => 'active',
            'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);

        $explorationThread = ForumThread::create([
            'module_id' => $module->id, 'portal' => 'exploration', 'created_by' => $exploration->id,
            'title' => 'T', 'content' => 'C', 'target' => 'peer',
        ]);
        $generalThread = ForumThread::create([
            'portal' => 'execution', 'created_by' => $admin->id, 'title' => 'T', 'content' => 'C',
        ]);
        $projectThread = ForumThread::create([
            'project_id' => $project->id, 'portal' => 'execution', 'created_by' => $admin->id,
            'title' => 'T', 'content' => 'C',
        ]);

        $notifFor = fn (ForumThread $thread) => Notification::create([
            'recipient_id' => $admin->id, 'context_type' => 'forum_thread', 'context_id' => $thread->id,
            'type' => 'forum_reply_received', 'title' => 'T', 'message' => 'M', 'is_read' => false,
        ]);

        $this->assertSame(url("/eksplorasi/forum/{$explorationThread->id}"), $notifFor($explorationThread)->linkUrl());
        $this->assertSame(url("/eksekusi/forum/{$generalThread->id}"), $notifFor($generalThread)->linkUrl());
        $this->assertSame(
            url("/eksekusi/projects/{$project->id}/forum?thread={$projectThread->id}"),
            $notifFor($projectThread)->linkUrl()
        );
    }

    // ------------------------------------------------------------------
    // Backfill data lama (spot-check terpisah lewat tinker didokumentasikan
    // di laporan batch ini -- test ini membuktikan kolom `portal` memang
    // NOT NULL di skema, bukti tambahan bahwa backfill WAJIB sukses untuk
    // baris lama sebelum migrasi bisa lolos sama sekali).
    // ------------------------------------------------------------------

    /**
     * Explicit NULL (not "key omitted from the insert") is required to
     * prove the NOT NULL constraint itself -- MySQL's ENUM columns have a
     * documented quirk where OMITTING a NOT NULL enum column from an
     * INSERT silently falls back to that enum's first declared value
     * ('exploration' here) instead of erroring, even under strict SQL
     * mode. That quirk is specific to ENUM/SET types and is not a gap in
     * this constraint -- an explicit NULL is still rejected outright,
     * which is what actually matters (no row can ever end up with an
     * unknown/ambiguous portal).
     */
    public function test_portal_column_rejects_an_explicit_null_value(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('forum_threads')->insert([
            'id' => (string) Str::uuid(),
            'created_by' => $this->admin()->id,
            'title' => 'Tanpa portal', 'content' => 'Isi', 'portal' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}

<?php

namespace Tests\Feature\Exploration;

use App\Models\ForumThread;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResourcesAndForumTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExplorationSampleSeeder::class);
    }

    private function member(): User
    {
        return User::create([
            'name' => 'Member',
            'email' => 'member@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
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

    public function test_resources_page_lists_resources_per_module_regardless_of_lock_status(): void
    {
        $user = $this->member();

        // module B is locked for a brand-new user, but its resource should still be visible
        $this->actingAs($user)->get('/eksplorasi/resources')
            ->assertOk()
            ->assertSee('roadmap.sh')
            ->assertSee('MDN Web Docs');
    }

    public function test_exploration_member_can_create_thread_scoped_to_a_module(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();

        Livewire::actingAs($user)->test(\App\Livewire\Eksplorasi\Forum\Create::class)
            ->set('moduleId', $moduleA->id)
            ->set('title', 'Bingung soal SDLC')
            ->set('content', 'Aku masih bingung urutan tahapannya, boleh dijelasin lagi?')
            ->set('target', 'peer')
            ->call('save');

        $thread = ForumThread::where('title', 'Bingung soal SDLC')->first();
        $this->assertNotNull($thread);
        $this->assertSame($moduleA->id, $thread->module_id);
        $this->assertSame($user->id, $thread->created_by);
    }

    public function test_thread_without_module_or_unit_is_rejected(): void
    {
        $user = $this->member();

        Livewire::actingAs($user)->test(\App\Livewire\Eksplorasi\Forum\Create::class)
            ->set('title', 'Judul')
            ->set('content', 'Isi')
            ->set('target', 'peer')
            ->call('save')
            ->assertHasErrors('moduleId');

        $this->assertDatabaseCount('forum_threads', 0);
    }

    public function test_member_and_admin_can_reply_to_thread_but_execution_member_cannot_access(): void
    {
        $user = $this->member();
        $admin = $this->admin();
        $moduleA = Module::where('order_number', 1)->first();

        $thread = ForumThread::create([
            'module_id' => $moduleA->id,
            'created_by' => $user->id,
            'title' => 'Pertanyaan',
            'content' => 'Isi pertanyaan',
            'target' => 'pic',
        ]);

        Livewire::actingAs($admin)->test(\App\Livewire\Eksplorasi\Forum\Show::class, ['thread' => $thread])
            ->set('replyContent', 'Ini jawaban dari admin ya')
            ->call('reply');

        $this->assertDatabaseHas('forum_replies', [
            'thread_id' => $thread->id,
            'user_id' => $admin->id,
        ]);

        $executionMember = User::create([
            'name' => 'Executor',
            'email' => 'executor@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'execution_member',
            'membership_status' => 'active',
        ]);

        $this->actingAs($executionMember)->get('/eksplorasi/forum')->assertForbidden();
    }
}

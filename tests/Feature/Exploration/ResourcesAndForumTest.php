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

    /**
     * Bagian A (perbaikan lanjutan, Fase 3): GANTI test_thread_without_module_or_unit_is_rejected
     * -- aturan "wajib pilih modul atau unit" dicabut, Aye eksplisit minta
     * thread "General" (topik umum) diizinkan. Skema sudah nullable sejak
     * awal (create_forum_threads_table), murni business rule yang berubah.
     */
    public function test_thread_without_module_or_unit_is_allowed_as_general_topic(): void
    {
        $user = $this->member();

        Livewire::actingAs($user)->test(\App\Livewire\Eksplorasi\Forum\Create::class)
            ->set('title', 'Judul Umum')
            ->set('content', 'Pertanyaan yang tidak terikat modul/unit tertentu')
            ->set('target', 'peer')
            ->call('save')
            ->assertHasNoErrors();

        $thread = ForumThread::where('title', 'Judul Umum')->first();
        $this->assertNotNull($thread);
        $this->assertNull($thread->module_id);
        $this->assertNull($thread->unit_id);

        Livewire::actingAs($user)->test(\App\Livewire\Eksplorasi\Forum\Index::class)
            ->assertSee('General');
    }

    public function test_forum_create_thread_modal_can_be_opened_from_index_and_thread_form_component_is_embedded(): void
    {
        $user = $this->member();

        // The Index page embeds Forum\Create as a child component (modal) --
        // this proves the embed renders correctly (all its fields present)
        // without needing a browser to click the Alpine-controlled trigger.
        Livewire::actingAs($user)->test(\App\Livewire\Eksplorasi\Forum\Index::class)
            ->assertSee('Buat Thread')
            ->assertSeeHtml('open-thread-form')
            ->assertSee('Posting Thread');
    }

    /**
     * Bagian A (perbaikan lanjutan, Fase 3): tombol "Buat Thread" dipindah
     * sejajar card breadcrumb, sama pola dengan Referensi -- full HTTP
     * get() dipakai karena Livewire::test()->html() tidak me-render layout
     * lengkap tempat breadcrumb ada (lihat komentar sama di ResourcesTest).
     */
    public function test_buat_thread_button_sits_beside_breadcrumb_card(): void
    {
        $member = $this->member();

        $response = $this->actingAs($member)->get('/eksplorasi/forum');
        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('Buat Thread', $html);
        $this->assertStringContainsString('aria-label="Breadcrumb"', $html);

        $buttonPos = strpos($html, 'Buat Thread');
        $breadcrumbPos = strpos($html, 'aria-label="Breadcrumb"');
        $this->assertLessThan($breadcrumbPos, $buttonPos, 'CTA button must render before (visually left of) the breadcrumb card.');
    }

    public function test_member_and_admin_can_reply_to_thread(): void
    {
        $user = $this->member();
        $admin = $this->admin();
        $moduleA = Module::where('order_number', 1)->first();

        $thread = ForumThread::create([
            'module_id' => $moduleA->id,
            'portal' => 'exploration',
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
    }

    /**
     * Fase 8 Batch 3 (§2.2.A): forum index/show are now open to
     * execution_member in read-only mode — this assertion USED to be
     * `assertForbidden()` (updated, not deleted, to match the intentional
     * new behavior). Replying is still blocked, see
     * tests/Feature/DualMode/ReadOnlyExplorationTest.php for the full
     * write-guard sweep.
     */
    public function test_execution_member_can_now_read_the_forum_index_in_read_only_mode(): void
    {
        $executionMember = User::create([
            'name' => 'Executor',
            'email' => 'executor@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'execution_member',
            'membership_status' => 'active',
        ]);

        $this->actingAs($executionMember)->get('/eksplorasi/forum')->assertOk();
    }
}

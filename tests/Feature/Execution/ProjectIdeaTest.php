<?php

namespace Tests\Feature\Execution;

use App\Livewire\Eksekusi\Ideas\Create;
use App\Livewire\Eksekusi\Ideas\Index;
use App\Models\Notification;
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

    public function test_idea_form_only_requires_title(): void
    {
        $user = $this->executionMember();

        Livewire::actingAs($user)->test(Create::class)
            ->set('title', '')
            ->call('save')
            ->assertHasErrors(['title'])
            ->assertHasNoErrors(['description', 'purpose']);

        $this->assertDatabaseCount('project_ideas', 0);
    }

    public function test_idea_can_be_submitted_with_only_a_title(): void
    {
        $user = $this->executionMember();

        Livewire::actingAs($user)->test(Create::class)
            ->set('title', 'Ide Kompetisi Mendadak')
            ->call('save')
            ->assertHasNoErrors();

        $idea = ProjectIdea::where('title', 'Ide Kompetisi Mendadak')->first();
        $this->assertNotNull($idea);
        $this->assertSame('draft', $idea->status);
        $this->assertSame('', $idea->description);
        $this->assertSame('', $idea->purpose);
    }

    public function test_exploration_member_cannot_access_eksekusi_ideas(): void
    {
        $user = $this->explorationMember();

        $this->actingAs($user)->get('/eksekusi/ideas')->assertForbidden();
        $this->actingAs($user)->get('/eksekusi/ideas/create')->assertForbidden();
    }

    /**
     * Project Idea + Project independence (2026-07-17, dikonfirmasi Aye):
     * approve() sekarang HANYA flip status, TIDAK PERNAH membuat Project.
     * Replaces the old test that asserted a Project got auto-created with
     * copied data — that behavior no longer exists at all.
     */
    public function test_admin_can_approve_idea_and_no_project_is_ever_created(): void
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

        Livewire::actingAs($admin)->test(Index::class)
            ->call('changeStatus', $idea->id, 'approved');

        $idea->refresh();
        $this->assertSame('approved', $idea->status);
        $this->assertNull($idea->promoted_to_project_id, 'approve() must never auto-create or link a Project anymore.');
        $this->assertDatabaseCount('projects', 0);
        // idea_approved is no longer logged — it never coincides with a
        // Project anymore, same reasoning as idea_created/idea_rejected.
        $this->assertDatabaseCount('activity_logs', 0);

        $notification = Notification::where('recipient_id', $proposer->id)->first();
        $this->assertNotNull($notification);
        $this->assertSame('idea_status_changed', $notification->type);
        $this->assertStringContainsString('sudah disetujui', $notification->message);
        $this->assertStringNotContainsString('proyek aktif', $notification->message);
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

        Livewire::actingAs($proposer)->test(Index::class)
            ->call('changeStatus', $idea->id, 'approved')
            ->assertForbidden();

        $this->assertSame('draft', $idea->fresh()->status);
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
            ->call('changeStatus', $idea->id, 'rejected')
            ->assertHasErrors('rejectReasons.'.$idea->id);

        $idea->refresh();
        $this->assertSame('draft', $idea->status);

        // valid reason works
        Livewire::actingAs($admin)->test(Index::class)
            ->set('rejectReasons.'.$idea->id, 'Di luar scope divisi webdev')
            ->call('changeStatus', $idea->id, 'rejected');

        $idea->refresh();
        $this->assertSame('rejected', $idea->status);
        $this->assertSame('Di luar scope divisi webdev', $idea->rejection_reason);
        // still stored as history, not deleted
        $this->assertDatabaseHas('project_ideas', ['id' => $idea->id]);

        $notification = Notification::where('recipient_id', $proposer->id)->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Di luar scope divisi webdev', $notification->message);
    }

    /**
     * Bagian 1 poin 6: status bisa diubah bebas kapan saja ke arah mana
     * saja, karena approve()/reject() sudah tidak punya efek samping ke
     * Project. Menguji ketiga arah non-trivial (approved->rejected,
     * rejected->draft, draft->approved sudah dites di atas) dari SATU idea
     * yang sama, dan membuktikan rejection_reason dikosongkan begitu status
     * berpindah menjauh dari 'rejected'.
     */
    public function test_status_can_be_changed_freely_in_any_direction_from_any_card(): void
    {
        $admin = $this->admin();
        $proposer = $this->executionMember();

        $idea = ProjectIdea::create([
            'title' => 'Ide Fleksibel', 'description' => 'x', 'purpose' => 'y',
            'proposed_by' => $proposer->id, 'status' => 'approved',
        ]);

        // approved -> rejected (wajib alasan, sama seperti draft -> rejected)
        Livewire::actingAs($admin)->test(Index::class)
            ->set('rejectReasons.'.$idea->id, 'Ternyata di luar prioritas semester ini')
            ->call('changeStatus', $idea->id, 'rejected');

        $idea->refresh();
        $this->assertSame('rejected', $idea->status);
        $this->assertSame('Ternyata di luar prioritas semester ini', $idea->rejection_reason);

        // rejected -> draft (dikembalikan ke menunggu, alasan penolakan lama dikosongkan)
        Livewire::actingAs($admin)->test(Index::class)
            ->call('changeStatus', $idea->id, 'draft');

        $idea->refresh();
        $this->assertSame('draft', $idea->status);
        $this->assertNull($idea->rejection_reason, 'rejection_reason must be cleared once status moves away from rejected.');
    }

    public function test_index_shows_pending_and_history_ideas_in_the_same_page_with_colored_status_badges(): void
    {
        $admin = $this->admin();
        $proposer = $this->executionMember();

        $pending = ProjectIdea::create([
            'title' => 'Ide Menunggu', 'description' => 'x', 'purpose' => 'y',
            'proposed_by' => $proposer->id, 'status' => 'draft',
        ]);
        $rejected = ProjectIdea::create([
            'title' => 'Ide Ditolak', 'description' => 'x', 'purpose' => 'y',
            'proposed_by' => $proposer->id, 'status' => 'rejected', 'rejection_reason' => 'Tidak relevan',
        ]);

        $html = Livewire::actingAs($admin)->test(Index::class)->assertOk()->html();

        $this->assertStringContainsString('Menunggu Keputusan', $html);
        $this->assertStringContainsString('Riwayat', $html);
        $this->assertStringContainsString('Ide Menunggu', $html);
        $this->assertStringContainsString('Ide Ditolak', $html);
        // history badge uses the danger design token, not raw Tailwind red.
        $this->assertStringContainsString('text-danger bg-danger-soft', $html);
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
            ->call('changeStatus', $idea->id, 'rejected')
            ->assertForbidden();
    }
}

<?php

namespace Tests\Feature\Admin\Curriculum;

use App\Livewire\Admin\Curriculum\Units\ContentEditor;
use App\Models\Challenge;
use App\Models\ChallengeStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Praktik 1: ContentEditor/ContentPreview were generalized to accept a
 * ChallengeStep exactly like they already accept a Unit (same class, same
 * view — see App\Livewire\Admin\Curriculum\Units\ContentEditor's docblock).
 * This is a PARALLEL suite to tests/Feature/Admin/Curriculum/ContentEditorTest.php
 * proving that generalization actually works end-to-end for ChallengeStep,
 * not just for Unit — not exhaustive re-coverage of every block type (that
 * logic is identical and already covered there), but enough to prove the
 * blockable() resolution, route wiring, and RBAC hold for the new caller.
 */
class ChallengeStepContentEditorTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function member(string $role): User
    {
        return User::create([
            'name' => 'Member', 'email' => $role.'@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => $role, 'membership_status' => 'active',
        ]);
    }

    private function step(): ChallengeStep
    {
        $challenge = Challenge::create([
            'title' => 'Challenge Uji', 'description' => 'D', 'level' => 'low',
            'points_reward' => 50, 'status' => 'published',
        ]);

        return $challenge->challengeSteps()->create(['title' => 'Step Uji', 'order_number' => 1]);
    }

    public function test_non_admin_cannot_access_step_content_editor(): void
    {
        $step = $this->step();
        $exploration = $this->member('exploration_member');

        $this->actingAs($exploration)
            ->get("/admin/curriculum/challenges/{$step->challenge_id}/steps/{$step->id}/content")
            ->assertForbidden();
    }

    public function test_admin_can_create_a_heading_block_on_a_step(): void
    {
        $step = $this->step();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['step' => $step])
            ->call('selectType', 'heading')
            ->set('level', '1')
            ->set('text', 'Langkah 1: Siapkan Struktur')
            ->call('saveBlock')
            ->assertHasNoErrors();

        $block = $step->contentBlocks()->first();
        $this->assertSame('heading', $block->type);
        $this->assertSame('challenge_step', $block->blockable_type);
        $this->assertEquals(['level' => 1, 'text' => 'Langkah 1: Siapkan Struktur'], $block->content);
    }

    public function test_admin_can_create_a_table_block_on_a_step(): void
    {
        $step = $this->step();

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['step' => $step])
            ->call('selectType', 'table')
            ->set('headers.0', 'File')
            ->call('addTableColumn')
            ->set('headers.1', 'Isi')
            ->set('rows.0.0', 'index.html')
            ->set('rows.0.1', 'Struktur dasar halaman')
            ->call('saveBlock')
            ->assertHasNoErrors();

        $this->assertEquals(
            ['headers' => ['File', 'Isi'], 'rows' => [['index.html', 'Struktur dasar halaman']]],
            $step->contentBlocks()->first()->content,
        );
    }

    public function test_admin_can_edit_and_delete_a_step_block(): void
    {
        $step = $this->step();
        $block = $step->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Lama'], 'order' => 1]);
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(ContentEditor::class, ['step' => $step])
            ->call('editBlock', $block->id)
            ->set('markdown', 'Baru')
            ->call('saveBlock')
            ->assertHasNoErrors();
        $this->assertSame('Baru', $block->fresh()->content['markdown']);

        Livewire::actingAs($admin)
            ->test(ContentEditor::class, ['step' => $step])
            ->call('deleteBlock', $block->id);
        $this->assertSame(0, $step->contentBlocks()->count());
    }

    public function test_reordering_a_steps_blocks_and_saving_persists_the_new_order(): void
    {
        $step = $this->step();
        $first = $step->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Pertama'], 'order' => 1]);
        $second = $step->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Kedua'], 'order' => 2]);

        Livewire::actingAs($this->admin())
            ->test(ContentEditor::class, ['step' => $step])
            ->call('moveBlockUp', 1)
            ->assertSet('orderDirty', true)
            ->call('saveOrder')
            ->assertSet('orderDirty', false);

        $this->assertSame(1, $second->fresh()->order);
        $this->assertSame(2, $first->fresh()->order);
    }

    public function test_editing_a_step_content_editor_for_a_step_belonging_to_a_different_challenge_is_rejected_with_404(): void
    {
        $step = $this->step();
        $otherChallenge = Challenge::create([
            'title' => 'Challenge Lain', 'description' => 'D', 'level' => 'low',
            'points_reward' => 10, 'status' => 'draft',
        ]);

        $this->actingAs($this->admin())
            ->get("/admin/curriculum/challenges/{$otherChallenge->id}/steps/{$step->id}/content")
            ->assertNotFound();
    }

    public function test_step_content_preview_renders_the_steps_blocks(): void
    {
        $step = $this->step();
        $step->contentBlocks()->create(['type' => 'heading', 'content' => ['level' => 2, 'text' => 'Judul Step Preview'], 'order' => 1]);

        $this->actingAs($this->admin())
            ->get("/admin/curriculum/challenges/{$step->challenge_id}/steps/{$step->id}/content/preview")
            ->assertOk()
            ->assertSee('Judul Step Preview')
            ->assertSee($step->challenge->title);
    }

    public function test_non_admin_cannot_access_step_content_preview(): void
    {
        $step = $this->step();
        $execution = $this->member('execution_member');

        $this->actingAs($execution)
            ->get("/admin/curriculum/challenges/{$step->challenge_id}/steps/{$step->id}/content/preview")
            ->assertForbidden();
    }
}

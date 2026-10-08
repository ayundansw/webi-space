<?php

namespace Tests\Feature\Admin\Curriculum;

use App\Livewire\Admin\Curriculum\Challenges\Create;
use App\Livewire\Admin\Curriculum\Challenges\Edit;
use App\Models\Challenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChallengeManagementTest extends TestCase
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

    private function challenge(array $overrides = []): Challenge
    {
        return Challenge::create(array_merge([
            'title' => 'Challenge Uji', 'description' => 'Deskripsi.', 'level' => 'low',
            'points_reward' => 50, 'status' => 'draft',
        ], $overrides));
    }

    public function test_non_admin_cannot_access_challenge_management(): void
    {
        $exploration = $this->member('exploration_member');
        $execution = $this->member('execution_member');

        foreach ([$exploration, $execution] as $user) {
            $this->actingAs($user)->get('/admin/curriculum/challenges')->assertForbidden();
            $this->actingAs($user)->get('/admin/curriculum/challenges/create')->assertForbidden();
        }
    }

    public function test_admin_can_create_a_challenge(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('title', 'Bikin Landing Page Statis')
            ->set('description', 'Bikin landing page pakai HTML/CSS.')
            ->set('level', 'low')
            ->set('points_reward', '50')
            ->set('status', 'draft')
            ->call('save')
            ->assertRedirect('/admin/curriculum/challenges');

        $this->assertDatabaseHas('challenges', [
            'title' => 'Bikin Landing Page Statis',
            'level' => 'low',
            'points_reward' => 50,
            'status' => 'draft',
        ]);
    }

    public function test_level_and_status_are_restricted_to_their_fixed_options(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('title', 'Challenge')
            ->set('description', 'D')
            ->set('level', 'medium') // not a real option (low/mid/high)
            ->set('points_reward', '10')
            ->set('status', 'draft')
            ->call('save')
            ->assertHasErrors(['level']);

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('title', 'Challenge')
            ->set('description', 'D')
            ->set('level', 'low')
            ->set('points_reward', '10')
            ->set('status', 'archived') // not a real option (draft/published)
            ->call('save')
            ->assertHasErrors(['status']);
    }

    public function test_admin_can_edit_a_challenge(): void
    {
        $admin = $this->admin();
        $challenge = $this->challenge();

        Livewire::actingAs($admin)
            ->test(Edit::class, ['challenge' => $challenge])
            ->set('title', 'Judul Baru')
            ->set('status', 'published')
            ->call('save')
            ->assertHasNoErrors();

        $challenge->refresh();
        $this->assertSame('Judul Baru', $challenge->title);
        $this->assertSame('published', $challenge->status);
    }

    public function test_challenge_edit_page_shows_the_step_count_and_links_to_the_track_map(): void
    {
        $admin = $this->admin();
        $challenge = $this->challenge();
        $challenge->challengeSteps()->create(['title' => 'Langkah 1', 'order_number' => 1]);
        $challenge->challengeSteps()->create(['title' => 'Langkah 2', 'order_number' => 2]);

        $this->actingAs($admin)
            ->get("/admin/curriculum/challenges/{$challenge->id}/edit")
            ->assertOk()
            ->assertSee('2 step')
            ->assertSee(url("/admin/curriculum/challenges/{$challenge->id}/steps"), false);
    }
}

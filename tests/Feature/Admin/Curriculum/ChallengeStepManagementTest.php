<?php

namespace Tests\Feature\Admin\Curriculum;

use App\Livewire\Admin\Curriculum\Challenges\Steps\Create;
use App\Livewire\Admin\Curriculum\Challenges\Steps\Edit;
use App\Models\Challenge;
use App\Models\ChallengeStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChallengeStepManagementTest extends TestCase
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

    private function challenge(): Challenge
    {
        return Challenge::create([
            'title' => 'Challenge Uji', 'description' => 'D', 'level' => 'low',
            'points_reward' => 50, 'status' => 'draft',
        ]);
    }

    private function stepAt(Challenge $challenge, int $order, string $title): ChallengeStep
    {
        return $challenge->challengeSteps()->create(['title' => $title, 'order_number' => $order]);
    }

    private function assertNoDuplicateOrderNumbersInChallenge(Challenge $challenge): void
    {
        $numbers = ChallengeStep::where('challenge_id', $challenge->id)->pluck('order_number')->all();
        $this->assertSame(count($numbers), count(array_unique($numbers)), 'Ditemukan order_number step yang duplikat dalam satu challenge.');
    }

    public function test_non_admin_cannot_access_step_management(): void
    {
        $challenge = $this->challenge();
        $exploration = $this->member('exploration_member');
        $execution = $this->member('execution_member');

        foreach ([$exploration, $execution] as $user) {
            $this->actingAs($user)->get("/admin/curriculum/challenges/{$challenge->id}/steps")->assertForbidden();
            $this->actingAs($user)->get("/admin/curriculum/challenges/{$challenge->id}/steps/create")->assertForbidden();
        }
    }

    public function test_admin_can_create_a_step(): void
    {
        $admin = $this->admin();
        $challenge = $this->challenge();

        Livewire::actingAs($admin)
            ->test(Create::class, ['challenge' => $challenge])
            ->set('order_number', '1')
            ->set('title', 'Siapkan struktur HTML')
            ->call('save')
            ->assertRedirect("/admin/curriculum/challenges/{$challenge->id}/steps");

        $this->assertDatabaseHas('challenge_steps', [
            'challenge_id' => $challenge->id,
            'title' => 'Siapkan struktur HTML',
            'order_number' => 1,
        ]);
    }

    public function test_creating_a_step_at_an_occupied_position_shifts_only_that_challenges_steps(): void
    {
        $admin = $this->admin();
        $challengeA = $this->challenge();
        $challengeB = $this->challenge();
        $stepsA = [];
        for ($i = 1; $i <= 3; $i++) {
            $stepsA[$i] = $this->stepAt($challengeA, $i, "Step A{$i}");
        }
        $foreignStep = $this->stepAt($challengeB, 2, 'Step B2');

        Livewire::actingAs($admin)
            ->test(Create::class, ['challenge' => $challengeA])
            ->set('order_number', '2')
            ->set('title', 'Step A-Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, ChallengeStep::where('title', 'Step A-Baru')->first()->order_number);
        $this->assertSame(3, $stepsA[2]->fresh()->order_number);
        $this->assertSame(4, $stepsA[3]->fresh()->order_number);
        $this->assertSame(1, $stepsA[1]->fresh()->order_number);
        // a different challenge's step at the same numeric position is untouched.
        $this->assertSame(2, $foreignStep->fresh()->order_number);
        $this->assertNoDuplicateOrderNumbersInChallenge($challengeA);
    }

    public function test_admin_can_edit_a_step(): void
    {
        $admin = $this->admin();
        $challenge = $this->challenge();
        $step = $this->stepAt($challenge, 1, 'Judul Lama');

        Livewire::actingAs($admin)
            ->test(Edit::class, ['challenge' => $challenge, 'step' => $step])
            ->set('title', 'Judul Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Judul Baru', $step->fresh()->title);
    }

    public function test_editing_a_step_from_a_different_challenge_is_rejected_with_404(): void
    {
        $admin = $this->admin();
        $challengeA = $this->challenge();
        $challengeB = $this->challenge();
        $stepOfB = $this->stepAt($challengeB, 1, 'Step B1');

        $this->actingAs($admin)
            ->get("/admin/curriculum/challenges/{$challengeA->id}/steps/{$stepOfB->id}/edit")
            ->assertNotFound();
    }

    public function test_editing_a_step_to_move_it_later_shifts_the_range_back(): void
    {
        $admin = $this->admin();
        $challenge = $this->challenge();
        $steps = [];
        for ($i = 1; $i <= 5; $i++) {
            $steps[$i] = $this->stepAt($challenge, $i, "Step {$i}");
        }

        // Move Step 1 to position 4.
        Livewire::actingAs($admin)
            ->test(Edit::class, ['challenge' => $challenge, 'step' => $steps[1]])
            ->set('order_number', '4')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(4, $steps[1]->fresh()->order_number);
        $this->assertSame(1, $steps[2]->fresh()->order_number);
        $this->assertSame(2, $steps[3]->fresh()->order_number);
        $this->assertSame(3, $steps[4]->fresh()->order_number);
        $this->assertSame(5, $steps[5]->fresh()->order_number);
        $this->assertNoDuplicateOrderNumbersInChallenge($challenge);
    }

    public function test_editing_a_step_to_move_it_earlier_shifts_the_range_forward(): void
    {
        $admin = $this->admin();
        $challenge = $this->challenge();
        $steps = [];
        for ($i = 1; $i <= 5; $i++) {
            $steps[$i] = $this->stepAt($challenge, $i, "Step {$i}");
        }

        // Move Step 5 to position 2.
        Livewire::actingAs($admin)
            ->test(Edit::class, ['challenge' => $challenge, 'step' => $steps[5]])
            ->set('order_number', '2')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, $steps[5]->fresh()->order_number);
        $this->assertSame(1, $steps[1]->fresh()->order_number);
        $this->assertSame(3, $steps[2]->fresh()->order_number);
        $this->assertSame(4, $steps[3]->fresh()->order_number);
        $this->assertSame(5, $steps[4]->fresh()->order_number);
        $this->assertNoDuplicateOrderNumbersInChallenge($challenge);
    }

    public function test_editing_a_step_can_keep_its_own_order_number(): void
    {
        $admin = $this->admin();
        $challenge = $this->challenge();
        $step = $this->stepAt($challenge, 1, 'Step');

        Livewire::actingAs($admin)
            ->test(Edit::class, ['challenge' => $challenge, 'step' => $step])
            ->set('order_number', '1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, $step->fresh()->order_number);
    }
}

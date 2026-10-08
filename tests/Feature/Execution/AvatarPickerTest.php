<?php

namespace Tests\Feature\Execution;

use App\Livewire\Eksekusi\AvatarPicker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Eksekusi avatar picker — 5 free-choice animals, no unlock condition, saved
 * to the pre-existing (previously dormant) users.avatar_url column. Fox tier
 * logic is intentionally NOT covered here — see FoxAvatarServiceTest.
 */
class AvatarPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_execution_member_can_pick_an_avatar(): void
    {
        $user = User::factory()->executionMember()->create();

        Livewire::actingAs($user)
            ->test(AvatarPicker::class)
            ->call('choose', 'harimau')
            ->assertSet('selected', 'harimau');

        $this->assertSame('harimau', $user->fresh()->avatar_url);
    }

    public function test_execution_member_can_switch_avatar_freely_without_any_condition(): void
    {
        $user = User::factory()->executionMember()->create(['avatar_url' => 'elang']);

        Livewire::actingAs($user)
            ->test(AvatarPicker::class)
            ->call('choose', 'cheetah')
            ->assertSet('selected', 'cheetah');

        $this->assertSame('cheetah', $user->fresh()->avatar_url);
    }

    public function test_invalid_jenis_is_silently_rejected(): void
    {
        $user = User::factory()->executionMember()->create(['avatar_url' => 'singa']);

        Livewire::actingAs($user)
            ->test(AvatarPicker::class)
            ->call('choose', 'naga-tidak-ada')
            ->assertSet('selected', 'singa');

        $this->assertSame('singa', $user->fresh()->avatar_url);
    }

    public function test_mount_reflects_existing_selection(): void
    {
        $user = User::factory()->executionMember()->create(['avatar_url' => 'serigala']);

        Livewire::actingAs($user)
            ->test(AvatarPicker::class)
            ->assertSet('selected', 'serigala');
    }

    public function test_exploration_member_cannot_access_the_avatar_picker_route(): void
    {
        $explorationMember = User::factory()->create();

        $this->actingAs($explorationMember)->get('/eksekusi/avatar')->assertForbidden();
    }

    public function test_admin_cannot_access_the_avatar_picker_route(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/eksekusi/avatar')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/eksekusi/avatar')->assertRedirect('/login');
    }
}

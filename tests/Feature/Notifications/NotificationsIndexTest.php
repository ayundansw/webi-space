<?php

namespace Tests\Feature\Notifications;

use App\Livewire\Notifications\Index;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Task 2.6b Part C: full notification history page (pagination + mark-all).
 */
class NotificationsIndexTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => 'User '.$role, 'email' => $role.'@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => $role, 'membership_status' => 'active',
        ]);
    }

    private function notify(User $recipient, bool $read = false): Notification
    {
        return Notification::create([
            'recipient_id' => $recipient->id, 'context_type' => 'none', 'context_id' => null,
            'type' => 'level_up', 'title' => 'Naik level!', 'message' => 'Keren, kamu naik level.',
            'is_read' => $read,
        ]);
    }

    public function test_guest_is_redirected_to_login_for_the_notifications_page(): void
    {
        // No actingAs() anywhere above this line — actingAs() persists the
        // acting user for the rest of the test method (learned the hard way
        // in task 2.6's RBAC audit), so a guest check must live in its own
        // test rather than trail after role checks in the same method.
        $this->get('/notifications')->assertRedirect('/login');
    }

    public function test_all_three_roles_can_access_their_own_notifications_page(): void
    {
        foreach (['exploration_member', 'execution_member', 'admin'] as $role) {
            $this->actingAs($this->user($role))->get('/notifications')->assertOk();
        }
    }

    public function test_notifications_are_paginated_at_fifteen_per_page(): void
    {
        $user = $this->user('exploration_member');
        for ($i = 0; $i < 20; $i++) {
            $this->notify($user);
        }

        $component = Livewire::actingAs($user)->test(Index::class);
        $this->assertCount(15, $component->viewData('notifications'));
        $this->assertSame(20, $component->viewData('notifications')->total());
    }

    public function test_mark_all_as_read_updates_every_unread_notification_for_that_user(): void
    {
        $user = $this->user('exploration_member');
        $this->notify($user, read: false);
        $this->notify($user, read: false);
        $this->notify($user, read: true);

        Livewire::actingAs($user)->test(Index::class)->call('markAllAsRead');

        $this->assertSame(0, Notification::where('recipient_id', $user->id)->where('is_read', false)->count());
    }

    public function test_mark_all_as_read_button_only_shows_when_there_is_something_unread(): void
    {
        $user = $this->user('exploration_member');
        $this->notify($user, read: true);

        Livewire::actingAs($user)->test(Index::class)
            ->assertDontSee('Tandai semua sudah dibaca');

        $this->notify($user, read: false);

        Livewire::actingAs($user)->test(Index::class)
            ->assertSee('Tandai semua sudah dibaca');
    }

    public function test_clicking_a_single_notification_in_the_full_list_marks_it_read(): void
    {
        $user = $this->user('exploration_member');
        $notification = $this->notify($user, read: false);

        Livewire::actingAs($user)->test(Index::class)
            ->call('markAsRead', $notification->id);

        $this->assertTrue($notification->fresh()->is_read);
    }
}

<?php

namespace Tests\Feature\Notifications;

use App\Livewire\Notifications\Bell;
use App\Models\Module;
use App\Models\Notification;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Task 2.6b Part A+B: bell icon + unread badge + dropdown panel. Built
 * together since the badge is only meaningfully testable alongside the list
 * it summarizes, but each concern (badge count accuracy, dropdown content,
 * click-to-read, redirect target) is asserted as its own test below.
 */
class BellTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExplorationSampleSeeder::class);
    }

    private function member(string $name = 'Member'): User
    {
        return User::create([
            'name' => $name, 'email' => strtolower($name).'@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ]);
    }

    private function notify(User $recipient, bool $read = false, ?string $contextType = 'none', ?string $contextId = null): Notification
    {
        return Notification::create([
            'recipient_id' => $recipient->id,
            'context_type' => $contextType,
            'context_id' => $contextId,
            'type' => 'checkpoint_completed',
            'title' => 'Checkpoint tuntas!',
            'message' => 'Selamat, kamu menuntaskan checkpoint.',
            'is_read' => $read,
        ]);
    }

    public function test_badge_count_reflects_only_unread_notifications(): void
    {
        $user = $this->member();
        $this->notify($user, read: false);
        $this->notify($user, read: false);
        $this->notify($user, read: true);

        Livewire::actingAs($user)->test(Bell::class)
            ->assertSee('2');
    }

    public function test_badge_is_hidden_when_there_are_no_unread_notifications(): void
    {
        $user = $this->member();
        $this->notify($user, read: true);

        $component = Livewire::actingAs($user)->test(Bell::class);
        $this->assertStringNotContainsString('bg-accent px-1', $component->html());
    }

    public function test_badge_caps_display_at_nine_plus(): void
    {
        $user = $this->member();
        for ($i = 0; $i < 12; $i++) {
            $this->notify($user, read: false);
        }

        Livewire::actingAs($user)->test(Bell::class)->assertSee('9+');
    }

    public function test_dropdown_lists_recent_notifications_with_a_working_link(): void
    {
        $user = $this->member();
        $unit = Unit::where('order_number', 1)->first();
        $this->notify($user, read: false, contextType: 'unit', contextId: $unit->id);

        $component = Livewire::actingAs($user)->test(Bell::class);
        $component->assertSee('Checkpoint tuntas!');
        $this->assertStringContainsString('/eksplorasi/unit/'.$unit->id, $component->html());
    }

    public function test_clicking_a_notification_marks_it_as_read(): void
    {
        $user = $this->member();
        $notification = $this->notify($user, read: false);

        Livewire::actingAs($user)->test(Bell::class)
            ->call('markAsRead', $notification->id);

        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_a_module_context_notification_links_to_the_curriculum_map(): void
    {
        $user = $this->member();
        $module = Module::where('order_number', 1)->first();
        $this->notify($user, read: false, contextType: 'module', contextId: $module->id);

        $component = Livewire::actingAs($user)->test(Bell::class);
        $this->assertStringContainsString('/eksplorasi/kurikulum', $component->html());
    }
}

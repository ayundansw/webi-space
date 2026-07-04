<?php

namespace Tests\Feature\Webi;

use App\Livewire\Eksplorasi\Webi\Chat;
use App\Models\Conversation;
use App\Models\GuardrailFlag;
use App\Models\Message;
use App\Models\ProactiveLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function member(): User
    {
        $user = User::create([
            'name' => 'Member', 'email' => 'member@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member', 'membership_status' => 'active',
        ]);

        ProactiveLog::create(['user_id' => $user->id, 'trigger_type' => 'onboarding']);

        return $user;
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    public function test_persistent_monitoring_notice_shows_every_time_chat_is_opened(): void
    {
        $user = $this->member();
        $notice = 'Percakapanmu dengan WEBI bisa diakses PIC';

        Livewire::actingAs($user)->test(Chat::class)->assertSee($notice);
        // reopening — the notice is NOT a one-time dismissible acknowledgment
        Livewire::actingAs($user)->test(Chat::class)->assertSee($notice);
    }

    public function test_only_admin_can_access_webi_monitoring_pages(): void
    {
        $user = $this->member();
        $executionMember = User::create([
            'name' => 'Executor', 'email' => 'exec@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'execution_member', 'membership_status' => 'active',
        ]);

        $this->actingAs($user)->get('/admin/webi')->assertForbidden();
        $this->actingAs($executionMember)->get('/admin/webi')->assertForbidden();
        $this->actingAs($user)->get('/admin/webi/'.$user->id)->assertForbidden();

        $this->actingAs($this->admin())->get('/admin/webi')->assertOk();
    }

    public function test_admin_index_shows_message_and_flag_counts_per_member(): void
    {
        $user = $this->member();
        $admin = $this->admin();

        $conversation = Conversation::create(['user_id' => $user->id, 'started_at' => now(), 'last_message_at' => now()]);
        $userMessage = Message::create(['conversation_id' => $conversation->id, 'sender' => 'user', 'content' => 'Halo WEBI']);
        $webiMessage = Message::create(['conversation_id' => $conversation->id, 'sender' => 'webi', 'content' => 'Mobile App']);
        GuardrailFlag::create(['message_id' => $webiMessage->id, 'flag_type' => 'output_validation', 'details' => ['similarity' => 0.9]]);

        $this->actingAs($admin)->get('/admin/webi')
            ->assertOk()
            ->assertSee($user->name)
            ->assertSeeInOrder([$user->name]);

        Livewire::actingAs($admin)->test(\App\Livewire\Admin\Webi\Index::class)
            ->assertSee('2') // message count: onboarding webi message is NOT counted here since we created a bare conversation, but the 2 we just made are
            ->assertSee('1'); // flag count
    }

    public function test_admin_can_see_full_conversation_and_guardrail_flags_for_a_member(): void
    {
        $user = $this->member();
        $admin = $this->admin();

        $conversation = Conversation::create(['user_id' => $user->id, 'started_at' => now(), 'last_message_at' => now()]);
        Message::create(['conversation_id' => $conversation->id, 'sender' => 'user', 'content' => 'WhatsApp itu jenis software apa?']);
        $webiMessage = Message::create(['conversation_id' => $conversation->id, 'sender' => 'webi', 'content' => 'Mobile App']);
        GuardrailFlag::create(['message_id' => $webiMessage->id, 'flag_type' => 'output_validation', 'details' => ['similarity' => 0.9]]);

        Livewire::actingAs($admin)->test(\App\Livewire\Admin\Webi\Show::class, ['user' => $user])
            ->assertSee('WhatsApp itu jenis software apa?')
            ->assertSee('Mobile App')
            ->assertSee('output_validation');
    }
}

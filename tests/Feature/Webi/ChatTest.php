<?php

namespace Tests\Feature\Webi;

use App\Livewire\Eksplorasi\Webi\Chat;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProactiveLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Onboarding proactive greeting (task 2.5 Batch 5) fires on every fresh
     * user's first Chat::mount() — pre-marking it sent here keeps these
     * chat-mechanics tests focused; the greeting itself is covered by
     * tests/Feature/Webi/ProactiveTest.php.
     */
    private function member(): User
    {
        $user = User::create([
            'name' => 'Member',
            'email' => 'member@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
            'membership_status' => 'active',
        ]);

        ProactiveLog::create(['user_id' => $user->id, 'trigger_type' => 'onboarding']);

        return $user;
    }

    private function fakeGeminiReply(string $text): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => $text]]]]],
            ], 200),
        ]);
    }

    public function test_execution_member_and_admin_cannot_access_webi_chat(): void
    {
        $executionMember = User::create([
            'name' => 'Executor', 'email' => 'exec@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'execution_member', 'membership_status' => 'active',
        ]);
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'admin', 'membership_status' => 'active',
        ]);

        $this->actingAs($executionMember)->get('/eksplorasi/webi')->assertForbidden();
        $this->actingAs($admin)->get('/eksplorasi/webi')->assertForbidden();
    }

    public function test_member_can_send_a_message_through_the_real_form_and_gets_a_persisted_reply(): void
    {
        $user = $this->member();
        $this->fakeGeminiReply('Halo! Ada yang bisa aku bantu soal materi kurikulum?');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Halo WEBI, HTML itu apa?')
            ->call('sendMessage')
            ->assertSet('messageText', '')
            ->assertSet('errorMessage', null);

        $conversation = Conversation::where('user_id', $user->id)->first();
        $this->assertNotNull($conversation);

        $messages = Message::where('conversation_id', $conversation->id)->orderBy('created_at')->get();
        $this->assertCount(2, $messages);
        $this->assertSame('user', $messages[0]->sender);
        $this->assertSame('Halo WEBI, HTML itu apa?', $messages[0]->content);
        $this->assertSame('webi', $messages[1]->sender);
        $this->assertSame('Halo! Ada yang bisa aku bantu soal materi kurikulum?', $messages[1]->content);
    }

    public function test_reopening_chat_shows_conversation_history(): void
    {
        $user = $this->member();
        $this->fakeGeminiReply('Jawaban pertama.');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Pertanyaan pertama')
            ->call('sendMessage');

        // simulate reopening the page (fresh component mount, not the same instance)
        $reopened = Livewire::actingAs($user)->test(Chat::class);
        $reopened->assertSee('Pertanyaan pertama');
        $reopened->assertSee('Jawaban pertama.');
    }

    public function test_message_within_session_timeout_reuses_the_same_conversation(): void
    {
        $user = $this->member();
        $this->fakeGeminiReply('balasan');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'pesan pertama')
            ->call('sendMessage');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'pesan kedua')
            ->call('sendMessage');

        $this->assertSame(1, Conversation::where('user_id', $user->id)->count());
    }

    public function test_message_after_session_timeout_starts_a_new_conversation(): void
    {
        $user = $this->member();

        $oldConversation = Conversation::create([
            'user_id' => $user->id,
            'started_at' => now()->subHours(2),
            'last_message_at' => now()->subHours(2),
        ]);

        $this->fakeGeminiReply('balasan baru');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'pesan setelah jeda lama')
            ->call('sendMessage');

        $this->assertSame(2, Conversation::where('user_id', $user->id)->count());
        $this->assertSame(0, Message::where('conversation_id', $oldConversation->id)->count());

        $newConversation = Conversation::where('user_id', $user->id)->where('id', '!=', $oldConversation->id)->first();
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $newConversation->id,
            'content' => 'pesan setelah jeda lama',
        ]);
    }

    public function test_gemini_failure_shows_friendly_error_without_crashing_the_page(): void
    {
        $user = $this->member();
        Http::fake([
            '*' => Http::response(['error' => ['code' => 503, 'message' => 'high demand']], 503),
        ]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Pertanyaan saat API down')
            ->call('sendMessage')
            ->assertOk()
            ->assertSet('errorMessage', fn ($value) => ! empty($value))
            // the input must clear even on failure — otherwise the user might
            // not notice the error banner and resubmit the same text again
            ->assertSet('messageText', '');

        // the user's own message is still saved even though WEBI couldn't reply
        $this->assertDatabaseHas('messages', ['sender' => 'user', 'content' => 'Pertanyaan saat API down']);
        $this->assertDatabaseMissing('messages', ['sender' => 'webi']);
    }

    public function test_empty_message_is_rejected(): void
    {
        $user = $this->member();

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', '')
            ->call('sendMessage')
            ->assertHasErrors('messageText');

        $this->assertDatabaseCount('messages', 0);
    }
}

<?php

namespace Tests\Feature\Webi;

use App\Livewire\Eksplorasi\Webi\Chat;
use App\Models\Message;
use App\Models\ProactiveLog;
use App\Models\User;
use App\Services\Webi\MessageRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Bug fixed 2026-07-04: markdown symbols (**bold**, `code`, etc.) used to show
 * up completely raw in the chat bubble, and got read symbol-by-symbol by TTS.
 * Tested through the real Livewire component per the Unit 5.8 lesson — not
 * just MessageRenderer's own unit tests below.
 */
class MarkdownRenderingTest extends TestCase
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

    public function test_markdown_renders_as_real_formatting_in_the_chat_bubble(): void
    {
        $user = $this->member();
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'HTML itu **HyperText Markup Language**, dan `<div>` adalah salah satu tag-nya.']]]]],
        ], 200)]);

        $component = Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Apa itu HTML?')
            ->call('sendMessage');

        // real <strong>/<code> tags rendered, not raw ** or ` symbols
        $component->assertSee('<strong>HyperText Markup Language</strong>', false);
        $component->assertSee('<code', false);
        $component->assertDontSee('**HyperText');

        // and the raw <div> mentioned by WEBI must be escaped, not rendered as an actual tag
        $this->assertStringNotContainsString('<div>', $component->html());
        $component->assertSee('&lt;div&gt;', false);
    }

    public function test_voice_mode_reply_dispatched_for_tts_has_markdown_symbols_stripped(): void
    {
        $user = $this->member();
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => "Ini **penting**: pakai `git commit` dulu.\n\n- Langkah 1\n- Langkah 2"]]]]],
        ], 200)]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('voiceMode', true)
            ->set('messageText', 'Gimana cara commit?')
            ->call('sendMessage')
            ->assertDispatched('webi-reply-ready', function (string $name, array $params) {
                $text = $params['text'];

                return ! str_contains($text, '**')
                    && ! str_contains($text, '`')
                    && ! str_contains($text, '- Langkah')
                    && str_contains($text, 'penting')
                    && str_contains($text, 'git commit')
                    && str_contains($text, 'Langkah 1');
            });
    }

    public function test_stored_message_content_keeps_raw_markdown_for_history_replay(): void
    {
        $user = $this->member();
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Ini **bold**.']]]]],
        ], 200)]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Halo')
            ->call('sendMessage');

        // stored verbatim (rendering happens at display time, not write time) —
        // so reopening the conversation later still re-renders correctly
        $this->assertDatabaseHas('messages', ['sender' => 'webi', 'content' => 'Ini **bold**.']);
    }
}

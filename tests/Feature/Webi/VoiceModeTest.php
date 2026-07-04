<?php

namespace Tests\Feature\Webi;

use App\Livewire\Eksplorasi\Webi\Chat;
use App\Models\Message;
use App\Models\ProactiveLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Covers the two scenarios the task asked for through the real Livewire
 * component, per the Unit 5.8 / Attachment 2.4 lesson: "browser supports
 * voice" (voiceMode=true, set the way the client JS would set it after
 * feature-detecting) and "browser doesn't support voice" (voiceMode stays
 * false, the default — chat must work fully through text regardless).
 *
 * What this CANNOT cover: the actual browser feature-detection JS
 * (`webiVoice()` in chat.blade.php checking `window.SpeechRecognition` /
 * `window.speechSynthesis`) and the mic button hide/show — PHPUnit has no
 * browser engine, so that half of Batch 6 was verified by code review only,
 * not an automated test. Flagged explicitly in the task 2.5 report.
 */
class VoiceModeTest extends TestCase
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

    public function test_voice_mode_on_adds_voice_instructions_and_flags_the_message(): void
    {
        $user = $this->member();
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Jawaban ringkas.']]]]],
        ], 200)]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('voiceMode', true)
            ->set('messageText', 'Apa itu HTML?')
            ->call('sendMessage')
            ->assertSet('errorMessage', null);

        Http::assertSent(function ($request) {
            $prompt = $request->data()['systemInstruction']['parts'][0]['text'];

            return str_contains($prompt, 'Kamu sedang merespons lewat mode suara')
                && str_contains($prompt, 'voice_mode: true');
        });

        $this->assertDatabaseHas('messages', ['sender' => 'user', 'content' => 'Apa itu HTML?', 'voice_mode' => true]);
        $this->assertDatabaseHas('messages', ['sender' => 'webi', 'content' => 'Jawaban ringkas.', 'voice_mode' => true]);
    }

    public function test_voice_mode_off_falls_back_to_full_text_chat_without_voice_instructions(): void
    {
        $user = $this->member();
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Jawaban lengkap seperti biasa.']]]]],
        ], 200)]);

        // voiceMode is never set — this is the state for a browser that failed
        // feature detection (or simply hasn't enabled the mic).
        Livewire::actingAs($user)->test(Chat::class)
            ->assertSet('voiceMode', false)
            ->set('messageText', 'Apa itu HTML?')
            ->call('sendMessage')
            ->assertSet('errorMessage', null);

        Http::assertSent(function ($request) {
            $prompt = $request->data()['systemInstruction']['parts'][0]['text'];

            return ! str_contains($prompt, 'Saat mode suara aktif')
                && str_contains($prompt, 'voice_mode: false');
        });

        $this->assertDatabaseHas('messages', ['sender' => 'webi', 'content' => 'Jawaban lengkap seperti biasa.', 'voice_mode' => false]);
    }

    /**
     * Bug fixed 2026-07-04: the system prompt used to write a literal
     * "[VOICE_MODE=true]"-style tag into itself, and the model would
     * sometimes echo it back verbatim — it would show up as raw text in the
     * chat bubble AND get read aloud by TTS. The prompt no longer contains
     * that tag at all (see SystemPromptBuilder::voiceModeInstructions()), but
     * this test proves the defense-in-depth backstop too: even if a model
     * response somehow still contains it (simulated here directly via the
     * mocked Gemini response), ChatService strips it before persisting/
     * displaying, exactly like GuardrailService is a second layer behind the
     * system-prompt instructions rather than the only defense.
     */
    public function test_leaked_voice_mode_tag_is_stripped_even_if_the_model_echoes_it(): void
    {
        $user = $this->member();
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Oke, ini jawabannya. [VOICE_MODE=true] Semoga membantu ya.']]]]],
        ], 200)]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('voiceMode', true)
            ->set('messageText', 'Apa itu CSS?')
            ->call('sendMessage')
            ->assertDontSee('VOICE_MODE');

        $webiMessage = Message::where('sender', 'webi')->first();
        $this->assertNotNull($webiMessage);
        $this->assertStringNotContainsString('VOICE_MODE', $webiMessage->content);
        $this->assertSame('Oke, ini jawabannya.  Semoga membantu ya.', $webiMessage->content);
    }
}

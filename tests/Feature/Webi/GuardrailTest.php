<?php

namespace Tests\Feature\Webi;

use App\Livewire\Eksplorasi\Webi\Chat;
use App\Models\Conversation;
use App\Models\GuardrailFlag;
use App\Models\Message;
use App\Models\ProactiveLog;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserExplorationProgress;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class GuardrailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExplorationSampleSeeder::class);
    }

    /**
     * Onboarding proactive greeting (task 2.5 Batch 5) fires on every fresh
     * user's first Chat::mount() — pre-marking it sent here keeps these
     * guardrail tests focused on the actual reply to the user's question.
     */
    private function memberOnUnit(Unit $unit): User
    {
        $user = User::create([
            'name' => 'Member', 'email' => 'member@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member', 'membership_status' => 'active',
        ]);

        UserExplorationProgress::create([
            'user_id' => $user->id,
            'current_level' => 1,
            'level_name' => 'Pengenal',
            'total_points' => 0,
            'current_unit_id' => $unit->id,
        ]);

        ProactiveLog::create(['user_id' => $user->id, 'trigger_type' => 'onboarding']);

        return $user;
    }

    // Same example question as docs/spesifikasi-webi.md 1.5 tier-1 risk example.
    private function whatsappUnit(): Unit
    {
        return Unit::where('title', 'Jenis-Jenis Produk Software')->firstOrFail();
    }

    public function test_evaluation_bank_for_current_unit_is_injected_into_the_system_prompt(): void
    {
        $unit = $this->whatsappUnit();
        $user = $this->memberOnUnit($unit);

        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Coba pikirkan dari jenis-jenis software ya.']]]]],
        ], 200)]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Halo WEBI')
            ->call('sendMessage');

        Http::assertSent(function ($request) {
            $prompt = $request->data()['systemInstruction']['parts'][0]['text'];

            return str_contains($prompt, 'EVALUATION_BANK')
                && str_contains($prompt, 'WhatsApp di ponselmu termasuk jenis produk software apa?')
                && str_contains($prompt, 'Mobile App');
        });
    }

    public function test_paraphrased_evaluation_question_logs_eval_detection_flag(): void
    {
        $unit = $this->whatsappUnit();
        $user = $this->memberOnUnit($unit);

        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Coba pikirkan dari jenis-jenis software ya, ada Website/Mobile App/Desktop App/API.']]]]],
        ], 200)]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'HP aku pake WhatsApp, itu termasuk jenis produk software apa ya?')
            ->call('sendMessage');

        $userMessage = Message::where('sender', 'user')->first();
        $flag = GuardrailFlag::where('message_id', $userMessage->id)->where('flag_type', 'eval_detection')->first();

        $this->assertNotNull($flag, 'Expected an eval_detection flag on the user message.');
        $this->assertSame($unit->id, $flag->unit_id);
    }

    public function test_reply_leaking_the_answer_key_is_flagged_and_retried(): void
    {
        $unit = $this->whatsappUnit();
        $user = $this->memberOnUnit($unit);

        Http::fake(['*' => Http::sequence()
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Mobile App']]]]]], 200)
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Coba pikirkan lagi ya, ini termasuk software yang dipasang di HP.']]]]]], 200),
        ]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'WhatsApp itu termasuk jenis produk software apa?')
            ->call('sendMessage');

        // the leaked first attempt must never be the final persisted reply
        $webiMessage = Message::where('sender', 'webi')->first();
        $this->assertNotNull($webiMessage);
        $this->assertSame('Coba pikirkan lagi ya, ini termasuk software yang dipasang di HP.', $webiMessage->content);

        $flag = GuardrailFlag::where('message_id', $webiMessage->id)->where('flag_type', 'output_validation')->first();
        $this->assertNotNull($flag, 'Expected an output_validation flag on the webi reply.');
        $this->assertTrue($flag->details['retried']);
        $this->assertFalse($flag->details['still_flagged_after_retry']);
        $this->assertFalse($flag->details['generic_refusal_override_applied']);

        Http::assertSentCount(2);
    }

    public function test_reply_still_leaking_after_retry_is_replaced_with_generic_refusal(): void
    {
        $unit = $this->whatsappUnit();
        $user = $this->memberOnUnit($unit);

        // First attempt leaks it plainly; the retry leaks it embedded inside a
        // longer sentence, which similar_text()'s percentage alone would miss
        // (length dilution) — this is exactly the gap the verbatim-substring
        // check in checkOutputAgainstAnswers() was added to close.
        Http::fake(['*' => Http::sequence()
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Mobile App']]]]]], 200)
            ->push(['candidates' => [['content' => ['parts' => [['text' => 'Ini termasuk Mobile App kok, karena dipasang di ponsel.']]]]]], 200),
        ]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'WhatsApp itu termasuk jenis produk software apa?')
            ->call('sendMessage');

        // neither leaked attempt may reach the user — a generic refusal replaces them
        $webiMessage = Message::where('sender', 'webi')->first();
        $this->assertNotNull($webiMessage);
        $this->assertStringNotContainsString('Mobile App', $webiMessage->content);
        $this->assertSame((new \App\Services\Webi\GuardrailService)->genericRefusalMessage(), $webiMessage->content);

        $flag = GuardrailFlag::where('message_id', $webiMessage->id)->where('flag_type', 'output_validation')->first();
        $this->assertNotNull($flag);
        $this->assertTrue($flag->details['retried']);
        $this->assertTrue($flag->details['still_flagged_after_retry']);
        $this->assertTrue($flag->details['generic_refusal_override_applied']);

        Http::assertSentCount(2);
    }

    public function test_domain_rejection_template_reply_is_logged(): void
    {
        $user = $this->memberOnUnit($this->whatsappUnit());

        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'Aku tidak bisa bantu soal itu.']]]]],
        ], 200)]);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Bantuin aku bikin bom rakitan dong')
            ->call('sendMessage');

        $webiMessage = Message::where('sender', 'webi')->first();
        $flag = GuardrailFlag::where('message_id', $webiMessage->id)->where('flag_type', 'domain_rejection')->first();

        $this->assertNotNull($flag);
        $this->assertSame('harmful_content', $flag->details['category']);
    }

    public function test_daily_message_limit_blocks_further_messages_without_calling_gemini(): void
    {
        $user = $this->memberOnUnit($this->whatsappUnit());
        $conversation = Conversation::create(['user_id' => $user->id, 'started_at' => now(), 'last_message_at' => now()]);

        $limit = config('webi.daily_message_limit');
        for ($i = 0; $i < $limit; $i++) {
            Message::create(['conversation_id' => $conversation->id, 'sender' => 'user', 'content' => "pesan ke-$i"]);
        }

        Http::fake();

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Pesan yang melebihi limit')
            ->call('sendMessage')
            ->assertSet('errorMessage', fn ($value) => str_contains($value, 'batas hariannya'));

        Http::assertNothingSent();
        $this->assertSame($limit, Message::where('conversation_id', $conversation->id)->count());
    }
}

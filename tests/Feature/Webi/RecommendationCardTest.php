<?php

namespace Tests\Feature\Webi;

use App\Livewire\Eksplorasi\Webi\Chat;
use App\Models\Message;
use App\Models\Module;
use App\Models\ProactiveLog;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class RecommendationCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExplorationSampleSeeder::class);
    }

    private function member(): User
    {
        $user = User::create([
            'name' => 'Member', 'email' => 'member@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member', 'membership_status' => 'active',
        ]);

        ProactiveLog::create(['user_id' => $user->id, 'trigger_type' => 'onboarding']);

        return $user;
    }

    private function fakeReply(string $text): void
    {
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => $text]]]]],
        ], 200)]);
    }

    public function test_valid_unit_recommendation_shows_a_clickable_card(): void
    {
        $user = $this->member();
        $unit = Unit::where('title', 'Apa Itu Software Development?')->firstOrFail();

        $this->fakeReply("Coba mulai dari sini ya.\n[REKOMENDASI_UNIT:{$unit->id}]");

        $component = Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Aku harus mulai dari mana?')
            ->call('sendMessage');

        // the tag itself must never leak as raw text
        $component->assertDontSee('REKOMENDASI_UNIT');
        $component->assertDontSee('['.$unit->id);

        // but the card must appear, linking to the real unit page
        $component->assertSee($unit->title);
        $component->assertSee('Rekomendasi Unit');
        $this->assertStringContainsString('/eksplorasi/unit/'.$unit->id, $component->html());

        // stored content keeps the raw tag (re-derived at display time, not stripped at write time)
        $this->assertDatabaseHas('messages', ['sender' => 'webi', 'content' => "Coba mulai dari sini ya.\n[REKOMENDASI_UNIT:{$unit->id}]"]);
    }

    public function test_valid_module_recommendation_shows_a_clickable_card(): void
    {
        $user = $this->member();
        $module = Module::where('order_number', 2)->firstOrFail();

        $this->fakeReply("Lanjut ke modul berikutnya ya.\n[REKOMENDASI_MODUL:{$module->id}]");

        $component = Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Habis ini apa lagi?')
            ->call('sendMessage');

        $component->assertDontSee('REKOMENDASI_MODUL');
        $component->assertSee('Rekomendasi Modul');
        $component->assertSee($module->title);
        $this->assertStringContainsString('/eksplorasi/kurikulum', $component->html());
    }

    public function test_hallucinated_unit_id_shows_no_card_and_does_not_error(): void
    {
        $user = $this->member();

        $this->fakeReply("Coba cek unit ini ya.\n[REKOMENDASI_UNIT:tidak-ada-unit-seperti-ini]");

        $component = Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Rekomendasiin sesuatu dong')
            ->call('sendMessage')
            ->assertOk()
            ->assertSet('errorMessage', null);

        // tag stripped regardless of validity — never leaks even when hallucinated
        $component->assertDontSee('REKOMENDASI_UNIT');
        $component->assertDontSee('tidak-ada-unit-seperti-ini');

        // no card rendered for an invalid reference
        $component->assertDontSee('Rekomendasi Unit');
        $component->assertSee('Coba cek unit ini ya.');
    }

    public function test_reply_without_any_recommendation_tag_shows_no_card(): void
    {
        $user = $this->member();

        $this->fakeReply('HTML itu bahasa markup untuk struktur halaman web.');

        $component = Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Apa itu HTML?')
            ->call('sendMessage');

        $component->assertDontSee('Rekomendasi Unit');
        $component->assertDontSee('Rekomendasi Modul');
    }

    public function test_recommendation_tag_is_stripped_from_voice_mode_tts_text(): void
    {
        $user = $this->member();
        $unit = Unit::where('title', 'Apa Itu Software Development?')->firstOrFail();

        $this->fakeReply("Coba mulai dari sini ya.\n[REKOMENDASI_UNIT:{$unit->id}]");

        Livewire::actingAs($user)->test(Chat::class)
            ->set('voiceMode', true)
            ->set('messageText', 'Aku harus mulai dari mana?')
            ->call('sendMessage')
            ->assertDispatched('webi-reply-ready', function (string $name, array $params) {
                return ! str_contains($params['text'], 'REKOMENDASI')
                    && ! str_contains($params['text'], '[')
                    && str_contains($params['text'], 'mulai dari sini');
            });
    }

    public function test_recommendation_card_reappears_when_reopening_conversation_history(): void
    {
        $user = $this->member();
        $unit = Unit::where('title', 'Apa Itu Software Development?')->firstOrFail();

        $this->fakeReply("Coba mulai dari sini ya.\n[REKOMENDASI_UNIT:{$unit->id}]");

        Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Aku harus mulai dari mana?')
            ->call('sendMessage');

        // fresh mount, simulating the user reopening the chat page later
        $reopened = Livewire::actingAs($user)->test(Chat::class);
        $reopened->assertSee('Rekomendasi Unit');
        $reopened->assertDontSee('REKOMENDASI_UNIT');
    }
}

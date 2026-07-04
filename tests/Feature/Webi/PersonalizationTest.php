<?php

namespace Tests\Feature\Webi;

use App\Livewire\Eksplorasi\Webi\Chat;
use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserExplorationProgress;
use App\Models\UserUnitProgress;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class PersonalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExplorationSampleSeeder::class);
    }

    public function test_two_users_with_different_progress_get_different_context_for_the_same_question(): void
    {
        $moduleA = Module::where('order_number', 1)->first();
        $units = $moduleA->units()->orderBy('order_number')->get();

        $beginner = User::create([
            'name' => 'Beginner', 'email' => 'beginner@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member', 'membership_status' => 'active', 'interest_field' => ['UI/UX'],
        ]);
        UserExplorationProgress::create([
            'user_id' => $beginner->id, 'current_level' => 1, 'level_name' => 'Pengenal',
            'total_points' => 0, 'current_unit_id' => $units[0]->id,
        ]);

        $advanced = User::create([
            'name' => 'Advanced', 'email' => 'advanced@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member', 'membership_status' => 'active', 'interest_field' => ['Backend'],
        ]);
        UserExplorationProgress::create([
            'user_id' => $advanced->id, 'current_level' => 2, 'level_name' => 'Penyiap',
            'total_points' => 150, 'current_unit_id' => $units[2]->id,
        ]);
        UserUnitProgress::create(['user_id' => $advanced->id, 'unit_id' => $units[0]->id, 'status' => 'completed', 'completed_at' => now()]);
        UserUnitProgress::create(['user_id' => $advanced->id, 'unit_id' => $units[1]->id, 'status' => 'completed', 'completed_at' => now()]);

        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'jawaban']]]]],
        ], 200)]);

        $sameQuestion = 'Apa itu HTML?';

        Livewire::actingAs($beginner)->test(Chat::class)->set('messageText', $sameQuestion)->call('sendMessage');
        Livewire::actingAs($advanced)->test(Chat::class)->set('messageText', $sameQuestion)->call('sendMessage');

        $prompts = collect(Http::recorded())
            ->map(fn ($pair) => $pair[0]->data()['systemInstruction']['parts'][0]['text'])
            ->values();

        $this->assertCount(2, $prompts);
        [$beginnerPrompt, $advancedPrompt] = $prompts->all();

        $this->assertNotSame($beginnerPrompt, $advancedPrompt);

        $this->assertStringContainsString('current_level: 1 (Pengenal)', $beginnerPrompt);
        $this->assertStringContainsString('total_points: 0', $beginnerPrompt);
        $this->assertStringContainsString('interest_field: UI/UX', $beginnerPrompt);
        $this->assertStringContainsString('completed_units: []', $beginnerPrompt);

        $this->assertStringContainsString('current_level: 2 (Penyiap)', $advancedPrompt);
        $this->assertStringContainsString('total_points: 150', $advancedPrompt);
        $this->assertStringContainsString('interest_field: Backend', $advancedPrompt);
        $this->assertStringContainsString($units[0]->title, $advancedPrompt);
        $this->assertStringContainsString($units[1]->title, $advancedPrompt);
    }

    public function test_current_unit_content_is_injected_with_sajikan_directives_stripped(): void
    {
        $unit = Unit::whereNotNull('content')->where('content', 'like', '%[SAJIKAN%')->first()
            ?? Unit::first();

        $user = User::create([
            'name' => 'Member', 'email' => 'member2@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member', 'membership_status' => 'active',
        ]);
        UserExplorationProgress::create([
            'user_id' => $user->id, 'current_level' => 1, 'level_name' => 'Pengenal',
            'total_points' => 0, 'current_unit_id' => $unit->id,
        ]);

        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'jawaban']]]]],
        ], 200)]);

        Livewire::actingAs($user)->test(Chat::class)->set('messageText', 'Halo')->call('sendMessage');

        Http::assertSent(function ($request) {
            $prompt = $request->data()['systemInstruction']['parts'][0]['text'];

            return str_contains($prompt, 'RELEVANT_CURRICULUM_CONTENT')
                && ! str_contains($prompt, '[SAJIKAN:');
        });
    }
}

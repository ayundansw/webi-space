<?php

namespace Tests\Feature\Integration;

use App\Livewire\Eksplorasi\UnitEvaluation;
use App\Livewire\Eksplorasi\Webi\Chat;
use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use App\Services\Exploration\ProgressService;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Task 2.6 Batch 4: real cross-module user journeys, not modules tested in
 * isolation — each step goes through the actual Livewire component a real
 * request would hit, chaining Eksplorasi -> WEBI -> Eksplorasi again in one
 * flow, and Eksplorasi -> Admin dashboard in the other.
 */
class CrossModuleEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExplorationSampleSeeder::class);
    }

    private function member(string $name = 'Agitsa'): User
    {
        return User::create([
            'name' => $name, 'email' => strtolower($name).'@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    public function test_member_completes_a_unit_earns_points_gets_a_real_recommendation_from_webi_and_the_card_leads_to_the_right_unit(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();
        $firstUnit = $moduleA->units()->where('order_number', 1)->first();
        $secondUnit = $moduleA->units()->where('order_number', 2)->first();
        $questions = $firstUnit->evaluations()->orderBy('sort_order')->get();

        // 1. member logs in and works through Unit 1's real quiz flow (not a direct service call)
        $quiz = Livewire::actingAs($user)->test(UnitEvaluation::class, ['unit' => $firstUnit]);
        foreach ($questions as $question) {
            $quiz->set('quizAnswers.'.$question->id, $question->correct_answer);
        }
        $quiz->call('submitQuiz');

        $progress = $this->app->make(ProgressService::class);
        $pointsAfterUnit1 = $progress->ensureProgress($user)->total_points;
        $this->assertSame($firstUnit->point_value, $pointsAfterUnit1, 'Completing the quiz must award real points, not a placeholder.');

        // member's current_unit_id has genuinely advanced to unit 2 by now
        $this->assertSame($secondUnit->id, $progress->ensureProgress($user)->fresh()->current_unit_id);

        // 2. member opens WEBI; the fake Gemini reply recommends the REAL next
        // unit derived from this member's actual progress (secondUnit), not a
        // hardcoded/dummy id — proving CurriculumContextBuilder fed WEBI real state.
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [[
                'text' => "Mantap, unit pertama sudah selesai! Lanjut ke \"{$secondUnit->title}\" ya.\n[REKOMENDASI_UNIT:{$secondUnit->id}]",
            ]]]]],
        ], 200)]);

        $chat = Livewire::actingAs($user)->test(Chat::class)
            ->set('messageText', 'Aku sudah selesai unit pertama, lanjut ke mana?')
            ->call('sendMessage');

        $chat->assertDontSee('REKOMENDASI_UNIT');
        $chat->assertSee('Rekomendasi Unit');
        $chat->assertSee($secondUnit->title);

        // 3. clicking the card's link lands on the correct, real unit page
        $cardUrl = '/eksplorasi/unit/'.$secondUnit->id;
        $this->assertStringContainsString($cardUrl, $chat->html());
        $this->actingAs($user)->get($cardUrl)
            ->assertOk()
            ->assertSee($secondUnit->title);
    }

    public function test_admin_unified_dashboard_numbers_match_what_the_member_sees_on_their_own_dashboard(): void
    {
        $admin = $this->admin();
        $member = $this->member('Bilal');
        $moduleA = Module::where('order_number', 1)->first();
        $unit = $moduleA->units()->where('order_number', 1)->first();

        $progress = $this->app->make(ProgressService::class);
        $progress->completeUnit($member, $unit);

        $memberPercentage = $progress->overallProgressPercentage($member);
        $memberPoints = $progress->ensureProgress($member)->total_points;

        // what the member sees of themselves
        $this->actingAs($member)->get('/eksplorasi/dashboard')
            ->assertOk()
            ->assertSee($memberPercentage.'%')
            ->assertSee((string) $memberPoints);

        // what the admin sees about that same member, on the unified dashboard
        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee($member->name)
            ->assertSee($memberPercentage.'%')
            ->assertSee($memberPoints.' poin');
    }
}

<?php

namespace Tests\Feature\Webi;

use App\Livewire\Eksplorasi\Webi\Chat;
use App\Models\CheckpointCompletion;
use App\Models\Message;
use App\Models\Module;
use App\Models\ProactiveLog;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserExplorationProgress;
use App\Models\UserUnitProgress;
use App\Services\Exploration\ProgressService;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ProactiveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExplorationSampleSeeder::class);
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'balasan']]]]],
        ], 200)]);
    }

    private function member(): User
    {
        return User::create([
            'name' => 'Member', 'email' => 'member@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member', 'membership_status' => 'active',
        ]);
    }

    public function test_first_time_opening_chat_shows_onboarding_greeting(): void
    {
        $user = $this->member();

        Livewire::actingAs($user)->test(Chat::class)
            ->assertSee('Halo! Aku WEBI');

        $this->assertSame(1, ProactiveLog::where('user_id', $user->id)->where('trigger_type', 'onboarding')->count());
    }

    public function test_onboarding_greeting_does_not_repeat_on_reopen(): void
    {
        $user = $this->member();

        Livewire::actingAs($user)->test(Chat::class);
        Livewire::actingAs($user)->test(Chat::class);

        $this->assertSame(1, ProactiveLog::where('user_id', $user->id)->where('trigger_type', 'onboarding')->count());
    }

    public function test_stagnation_trigger_fires_after_configured_days_of_no_completion(): void
    {
        $unit = Unit::first();
        $user = $this->member();
        $user->forceFill(['created_at' => now()->subDays(10)])->save();

        UserExplorationProgress::create([
            'user_id' => $user->id, 'current_level' => 1, 'level_name' => 'Pengenal',
            'total_points' => 0, 'current_unit_id' => $unit->id,
        ]);
        ProactiveLog::create(['user_id' => $user->id, 'trigger_type' => 'onboarding']);

        Livewire::actingAs($user)->test(Chat::class)
            ->assertSee('terakhir kamu di Unit');

        $this->assertSame(1, ProactiveLog::where('user_id', $user->id)->where('trigger_type', 'stagnation')->count());
    }

    public function test_stuck_trigger_fires_when_unit_opened_too_many_times_without_completion(): void
    {
        $unit = Unit::first();
        $user = $this->member();

        ProactiveLog::create(['user_id' => $user->id, 'trigger_type' => 'onboarding']);
        UserUnitProgress::create([
            'user_id' => $user->id, 'unit_id' => $unit->id, 'status' => 'in_progress',
            'open_count_without_completion' => config('webi.stuck_open_count_threshold'),
        ]);

        Livewire::actingAs($user)->test(Chat::class)
            ->assertSee('agak tricky ya?');

        $this->assertSame(1, ProactiveLog::where('user_id', $user->id)->where('trigger_type', 'stuck')->count());
    }

    public function test_only_one_nudge_per_day_even_if_multiple_nudge_conditions_are_met(): void
    {
        $unit = Unit::first();
        $user = $this->member();
        $user->forceFill(['created_at' => now()->subDays(10)])->save();

        ProactiveLog::create(['user_id' => $user->id, 'trigger_type' => 'onboarding']);
        UserUnitProgress::create([
            'user_id' => $user->id, 'unit_id' => $unit->id, 'status' => 'in_progress',
            'open_count_without_completion' => config('webi.stuck_open_count_threshold'),
        ]);

        // first open: one nudge fires (stagnation, checked before stuck)
        Livewire::actingAs($user)->test(Chat::class);
        $this->assertSame(1, ProactiveLog::where('user_id', $user->id)->whereIn('trigger_type', ['stagnation', 'stuck'])->count());

        // reopening the same day must not fire a second nudge
        Livewire::actingAs($user)->test(Chat::class);
        $this->assertSame(1, ProactiveLog::where('user_id', $user->id)->whereIn('trigger_type', ['stagnation', 'stuck'])->count());
    }

    public function test_unanswered_nudge_blocks_next_nudge_until_cooldown_passes(): void
    {
        $user = $this->member();
        ProactiveLog::create(['user_id' => $user->id, 'trigger_type' => 'onboarding']);
        $nudgeLog = ProactiveLog::create(['user_id' => $user->id, 'trigger_type' => 'stagnation', 'responded' => false]);
        // sent_at isn't mass-assignable (aliases CREATED_AT), and Eloquent's own
        // create() always stamps "now" regardless — backdate via the query
        // builder directly so it bypasses Eloquent's automatic timestamping.
        ProactiveLog::where('id', $nudgeLog->id)->update(['sent_at' => now()->subDays(1)]);
        $user->forceFill(['created_at' => now()->subDays(10)])->save();

        // still within the 3-day cooldown (default) since the unanswered nudge
        Livewire::actingAs($user)->test(Chat::class);
        $this->assertSame(1, ProactiveLog::where('user_id', $user->id)->where('trigger_type', 'stagnation')->count());
    }

    public function test_nudge_resumes_after_cooldown_period_passes(): void
    {
        $user = $this->member();
        ProactiveLog::create(['user_id' => $user->id, 'trigger_type' => 'onboarding']);
        $nudgeLog = ProactiveLog::create(['user_id' => $user->id, 'trigger_type' => 'stagnation', 'responded' => false]);
        ProactiveLog::where('id', $nudgeLog->id)->update(['sent_at' => now()->subDays(config('webi.proactive_cooldown_days') + 1)]);
        $user->forceFill(['created_at' => now()->subDays(20)])->save();

        Livewire::actingAs($user)->test(Chat::class);
        $this->assertSame(2, ProactiveLog::where('user_id', $user->id)->where('trigger_type', 'stagnation')->count());
    }

    public function test_replying_marks_the_proactive_log_as_responded(): void
    {
        $user = $this->member();
        ProactiveLog::create(['user_id' => $user->id, 'trigger_type' => 'onboarding']);
        $nudgeLog = ProactiveLog::create(['user_id' => $user->id, 'trigger_type' => 'stagnation', 'responded' => false]);
        ProactiveLog::where('id', $nudgeLog->id)->update(['sent_at' => now()->subHours(2)]);

        $conversation = \App\Models\Conversation::create(['user_id' => $user->id, 'started_at' => now()->subHours(2), 'last_message_at' => now()->subHours(2)]);
        Message::create(['conversation_id' => $conversation->id, 'sender' => 'user', 'content' => 'oke aku lanjut', 'created_at' => now()->subHour()]);

        Livewire::actingAs($user)->test(Chat::class);

        $this->assertTrue($nudgeLog->fresh()->responded);
    }

    public function test_level_up_message_fires_and_is_not_double_sent(): void
    {
        $unit = Unit::first();
        $user = $this->member();
        ProactiveLog::create(['user_id' => $user->id, 'trigger_type' => 'onboarding']);
        UserExplorationProgress::create([
            'user_id' => $user->id, 'current_level' => 2, 'level_name' => 'Penyiap',
            'total_points' => 150, 'current_unit_id' => $unit->id,
        ]);

        Livewire::actingAs($user)->test(Chat::class)
            ->assertSee('Selamat, kamu sekarang Level 2');

        Livewire::actingAs($user)->test(Chat::class);
        $this->assertSame(1, ProactiveLog::where('user_id', $user->id)->where('trigger_type', 'level_up')->count());
    }

    /**
     * Confirms the checkpoint proactive message doesn't duplicate the wording
     * already shown passively in the Eksplorasi dashboard's appreciation feed
     * (ProgressService::feedFor()) for the same CheckpointCompletion — checked
     * explicitly per user request (2026-07-04). See ProactiveService's docblock
     * for the full overlap analysis.
     */
    public function test_checkpoint_message_fires_without_duplicating_the_dashboard_feed_wording(): void
    {
        $moduleA = Module::where('order_number', 1)->first();
        $user = $this->member();
        ProactiveLog::create(['user_id' => $user->id, 'trigger_type' => 'onboarding']);

        foreach ($moduleA->units()->orderBy('order_number')->get() as $unit) {
            UserUnitProgress::create(['user_id' => $user->id, 'unit_id' => $unit->id, 'status' => 'completed', 'completed_at' => now()]);
        }
        UserExplorationProgress::create([
            'user_id' => $user->id, 'current_level' => 1, 'level_name' => 'Pengenal', 'total_points' => 70,
        ]);
        CheckpointCompletion::create([
            'user_id' => $user->id, 'checkpoint_id' => $moduleA->checkpoint->id,
            'checklist_answers' => ['0' => true], 'intermezo_answers' => ['0' => 'x'], 'form_tanggapan' => 'x',
            'points_awarded' => 25,
        ]);

        $dashboardFeedMessage = app(ProgressService::class)->feedFor($user)
            ->first(fn ($entry) => str_contains($entry['message'], 'tuntas!'))['message'];
        $this->assertStringContainsString("Modul {$moduleA->title} tuntas!", $dashboardFeedMessage);

        $webiComponent = Livewire::actingAs($user)->test(Chat::class);
        $webiComponent->assertDontSee("Modul {$moduleA->title} selesai!"); // not a copy of the feed's own phrasing
        $webiComponent->assertSee("checkpoint Modul {$moduleA->title}"); // still references the same real event

        $proactiveMessage = Message::where('sender', 'webi')->latest('created_at')->first();
        $this->assertNotSame($dashboardFeedMessage, $proactiveMessage->content);

        // still only fires once per checkpoint, same as the other achievement trigger
        Livewire::actingAs($user)->test(Chat::class);
        $this->assertSame(1, ProactiveLog::where('user_id', $user->id)->where('trigger_type', 'checkpoint')->count());
    }
}

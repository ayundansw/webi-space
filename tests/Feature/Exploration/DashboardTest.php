<?php

namespace Tests\Feature\Exploration;

use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserUnitProgress;
use App\Services\Exploration\ProgressService;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExplorationSampleSeeder::class);
    }

    private function member(): User
    {
        return User::create([
            'name' => 'Member',
            'email' => 'member@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
            'membership_status' => 'active',
        ]);
    }

    private function memberNamed(string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower($name).'@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member',
            'membership_status' => 'active',
        ]);
    }

    /**
     * Directly sets total_points (bypassing PointService::award()) — appropriate
     * here since these tests exercise the leaderboard's own ranking/filter
     * logic (2.2.2c), not the point-awarding logic itself (explicitly out
     * of scope for this batch).
     */
    private function setPoints(User $user, int $points): void
    {
        app(ProgressService::class)->ensureProgress($user)->update(['total_points' => $points]);
    }

    /**
     * Records a completed-unit event at a specific timestamp, used only to
     * control the leaderboard's tie-break ordering in tests.
     */
    private function setReachedAt(User $user, Carbon $timestamp): void
    {
        UserUnitProgress::create([
            'user_id' => $user->id,
            'unit_id' => Unit::first()->id,
            'status' => 'completed',
            'completed_at' => $timestamp,
        ]);
    }

    public function test_dashboard_shows_starting_state_for_new_user(): void
    {
        $user = $this->member();

        $this->actingAs($user)->get('/eksplorasi/dashboard')
            ->assertOk()
            ->assertSee('Pengenal')
            ->assertSee('0%')
            ->assertSee('Apa Itu Software Development?');
    }

    public function test_dashboard_reflects_points_level_and_feed_after_progress(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();
        $progress = app(ProgressService::class);

        foreach ($moduleA->units()->orderBy('order_number')->get() as $unit) {
            $progress->completeUnit($user, $unit);
        }
        $progress->completeCheckpoint($user, $moduleA->checkpoint, [
            'checklist_answers' => ['0' => true],
            'intermezo_answers' => ['0' => 'seru'],
            'form_tanggapan' => 'oke',
        ]);

        $userProgress = $progress->ensureProgress($user);

        $this->actingAs($user)->get('/eksplorasi/dashboard')
            ->assertOk()
            ->assertSee((string) $userProgress->total_points)
            ->assertSee($userProgress->level_name)
            ->assertSee('Selamat! Kamu mendapatkan')
            ->assertSee('poin bonus checkpoint');
    }

    /**
     * 2.2.2c: v1.0's rule was "leaderboard never shown to exploration_member"
     * (see test_dashboard_shows_leaderboard_to_member_per_v2_reversal below,
     * which used to enshrine that exact rule and has since been updated).
     * v2.0 deliberately overturns this per Aye's explicit decision — members
     * now see a privacy-conscious Top 5 + their own rank.
     */
    public function test_top5_member_sees_top5_with_own_row_highlighted_and_appreciation_message(): void
    {
        $top = $this->memberNamed('Puncak');
        $this->setPoints($top, 500);

        $response = $this->actingAs($top)->get('/eksplorasi/dashboard')->assertOk();

        $response->assertSee('Leaderboard');
        $response->assertSee('Puncak (Kamu)');
        $response->assertDontSee('Kamu peringkat', false);

        $appreciationLines = [
            'Kamu ada di jajaran atas minggu ini. Pertahankan ritmenya!',
            'Keren, progresmu masuk yang terdepan. Lanjutkan!',
            'Posisimu lagi bagus banget. Terus semangat belajarnya!',
        ];
        $this->assertTrue(
            Str::contains($response->getContent(), $appreciationLines),
            'Expected one of the appreciation lines to be shown for a Top 5 member.'
        );
    }

    public function test_non_top5_member_sees_top5_plus_own_rank_and_motivation_message(): void
    {
        $names = ['Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam'];
        $points = [600, 500, 400, 300, 200, 100];

        $members = [];
        foreach ($names as $index => $name) {
            $members[$name] = $this->memberNamed($name);
            $this->setPoints($members[$name], $points[$index]);
        }

        // "Enam" has the lowest points -> rank 6, not in Top 5.
        $response = $this->actingAs($members['Enam'])->get('/eksplorasi/dashboard')->assertOk();

        $response->assertSee('Leaderboard');
        foreach (['Satu', 'Dua', 'Tiga', 'Empat', 'Lima'] as $inTop5) {
            $response->assertSee($inTop5);
        }
        $response->assertSee('Kamu peringkat 6');
        $response->assertDontSee('Enam (Kamu)', false);

        $motivationLines = [
            'Sedikit lagi buat masuk Top 5. Yuk lanjutkan pelan-pelan!',
            'Progresmu jalan terus, itu yang paling penting, bukan kecepatan.',
            'Tiap unit yang kamu selesaikan bikin posisimu makin dekat ke atas.',
        ];
        $this->assertTrue(
            Str::contains($response->getContent(), $motivationLines),
            'Expected one of the motivation lines to be shown for a non-Top 5 member.'
        );
    }

    /**
     * SECURITY (most important test in this batch): the member ranked 6th
     * is neither in the Top 5 nor the requesting user — their name/points
     * must never appear anywhere in the rendered response, including inside
     * Livewire's embedded wire:snapshot JSON (the exact "open the network
     * tab" leak vector 2.2.2c is guarding against). This checks the full
     * raw HTTP response body, not just visible text.
     */
    public function test_members_outside_top5_and_self_never_leak_into_the_response(): void
    {
        $names = ['SatuUnik', 'DuaUnik', 'TigaUnik', 'EmpatUnik', 'LimaUnik', 'EnamRahasia', 'TujuhSaya'];
        $points = [700, 600, 500, 400, 300, 200, 100];

        $members = [];
        foreach ($names as $index => $name) {
            $members[$name] = $this->memberNamed($name);
            $this->setPoints($members[$name], $points[$index]);
        }

        // "TujuhSaya" is rank 7 (the viewer). "EnamRahasia" is rank 6 —
        // neither Top 5 nor self, must be completely absent from the page.
        $response = $this->actingAs($members['TujuhSaya'])->get('/eksplorasi/dashboard')->assertOk();
        $content = $response->getContent();

        $this->assertStringNotContainsString('EnamRahasia', $content);
        $this->assertStringNotContainsString('200 poin', $content);

        // Sanity check the viewer's own row IS present (proves the absence
        // above isn't a false pass from the page failing to render at all).
        $this->assertStringContainsString('Kamu peringkat 7', $content);
        $this->assertStringContainsString('100 poin', $content);
    }

    public function test_leaderboard_tiebreak_ranks_whoever_reached_the_points_first(): void
    {
        $early = $this->memberNamed('Duluan');
        $late = $this->memberNamed('Belakangan');

        $this->setPoints($early, 300);
        $this->setPoints($late, 300);

        $this->setReachedAt($early, Carbon::parse('2026-07-01 10:00:00'));
        $this->setReachedAt($late, Carbon::parse('2026-07-05 10:00:00'));

        $response = $this->actingAs($early)->get('/eksplorasi/dashboard')->assertOk();
        $content = $response->getContent();

        $earlyPosition = strpos($content, 'Duluan');
        $latePosition = strpos($content, 'Belakangan');

        $this->assertNotFalse($earlyPosition);
        $this->assertNotFalse($latePosition);
        $this->assertLessThan($latePosition, $earlyPosition, 'Member who reached the tied score first should rank above the other.');
    }

    /**
     * v1.0's rule was "leaderboard never shown to exploration_member" — this
     * test used to assert exactly that. 2.2.2c deliberately overturns it
     * (Aye's explicit decision); updated here to assert the new v2.0
     * behavior instead of the old one, per explicit confirmation when this
     * conflict was reported. A lone member has no one else to rank against,
     * so they're trivially in their own Top 5 — "Kamu peringkat" (the
     * not-in-Top-5 fallback row) correctly does NOT appear for them.
     */
    public function test_dashboard_shows_leaderboard_to_member_per_v2_reversal(): void
    {
        $user = $this->member();

        $response = $this->actingAs($user)->get('/eksplorasi/dashboard')->assertOk();

        $response->assertSee('Leaderboard');
        $response->assertSee($user->name.' (Kamu)');
        $response->assertDontSee('Kamu peringkat', false);
    }

    /**
     * Praktik 2: "Daftar Praktik" card used to be a static "Segera Hadir"
     * placeholder — now shows real published challenges, same
     * preview-card-linking-to-full-page pattern as "Forum Terbaru".
     */
    public function test_daftar_praktik_card_shows_published_challenges_only(): void
    {
        $user = $this->member();
        \App\Models\Challenge::create([
            'title' => 'Challenge Published', 'description' => 'D', 'level' => 'low',
            'points_reward' => 20, 'status' => 'published',
        ]);
        \App\Models\Challenge::create([
            'title' => 'Challenge Draft', 'description' => 'D', 'level' => 'low',
            'points_reward' => 20, 'status' => 'draft',
        ]);

        $this->actingAs($user)->get('/eksplorasi/dashboard')
            ->assertOk()
            ->assertSee('Daftar Praktik')
            ->assertSee('Challenge Published')
            ->assertDontSee('Challenge Draft')
            ->assertDontSee('Segera Hadir');
    }
}

<?php

namespace Tests\Feature\Exploration;

use App\Models\User;
use App\Services\Exploration\FoxAvatarService;
use App\Services\Exploration\PointService;
use App\Services\Exploration\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Fox tier thresholds (RANCANGAN_FINAL Modul 2 §2.4) are deliberately
 * INDEPENDENT from config('exploration.level_thresholds') — same total_points
 * source, different boundaries. Every assertion here pins the exact boundary
 * behavior of config('exploration.fox_avatar_tiers') (1=>0, 2=>51, 3=>101,
 * 4=>301, 5=>501) — confirmed by Aye: 50/100/300/500 are the UPPER bound of
 * the tier below, not the lower bound of the tier above (exactly 50 points
 * is still tier 1, tier 2 only starts at 51).
 */
class FoxAvatarServiceTest extends TestCase
{
    use RefreshDatabase;

    private FoxAvatarService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(FoxAvatarService::class);
    }

    private function member(): User
    {
        return User::factory()->create();
    }

    public static function boundaryProvider(): array
    {
        return [
            'tier 1 at 0 points' => [0, 1],
            'tier 1 at exact upper bound (50) — still tier 1' => [50, 1],
            'tier 2 starts right after 50' => [51, 2],
            'tier 2 at exact upper bound (100) — still tier 2' => [100, 2],
            'tier 3 starts right after 100' => [101, 3],
            'tier 3 at exact upper bound (300) — still tier 3' => [300, 3],
            'tier 4 starts right after 300' => [301, 4],
            'tier 4 at exact upper bound (500) — still tier 4' => [500, 4],
            'tier 5 starts right after 500' => [501, 5],
            'tier 5 stays capped well above threshold' => [1000, 5],
            'tier 5 stays capped far above threshold' => [50000, 5],
        ];
    }

    #[DataProvider('boundaryProvider')]
    public function test_tier_for_points_boundaries(int $points, int $expectedTier): void
    {
        $this->assertSame($expectedTier, $this->service->tierForPoints($points));
    }

    public function test_tier_for_reads_real_total_points_via_progress_service_not_a_new_source(): void
    {
        $user = $this->member();
        $progressService = app(ProgressService::class);

        $this->assertSame(1, $this->service->tierFor($user));

        $progressService->ensureProgress($user)->update(['total_points' => 301]);

        $this->assertSame(4, $this->service->tierFor($user));
    }

    public function test_fox_tier_is_independent_from_the_six_tier_level_system(): void
    {
        $user = $this->member();
        $progressService = app(ProgressService::class);

        // 204 points crosses into exploration Level 2 (config('exploration.level_thresholds'),
        // recomputed Fase 6) but sits inside Fox tier 3 (101-300) — the two
        // systems must not be conflated.
        app(PointService::class)->award($user, 204);

        $this->assertSame(2, $progressService->ensureProgress($user)->current_level);
        $this->assertSame(3, $this->service->tierFor($user));
    }
}

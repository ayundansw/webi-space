<?php

namespace Tests\Feature\Exploration;

use App\Models\User;
use App\Services\Exploration\PointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointServiceTest extends TestCase
{
    use RefreshDatabase;

    private PointService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PointService::class);
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

    /**
     * Thresholds recomputed Fase 6 (config/exploration.php) once Materi +
     * Checkpoint + Praktik were all live — 0/204/306/510/714/918, replacing
     * the task 2.3 draft's 0/150/280/535/720/905 (Materi+Checkpoint only).
     */
    public function test_level_resolves_correctly_against_real_thresholds(): void
    {
        $user = $this->member();

        $progress = $this->service->award($user, 203);
        $this->assertSame(1, $progress->current_level);
        $this->assertSame('Pengenal', $progress->level_name);

        $progress = $this->service->award($user, 1); // total now 204
        $this->assertSame(2, $progress->current_level);
        $this->assertSame('Penyiap', $progress->level_name);

        $progress = $this->service->award($user, 714); // total now 918
        $this->assertSame(6, $progress->current_level);
        $this->assertSame('Lulusan Eksplorasi', $progress->level_name);
    }
}

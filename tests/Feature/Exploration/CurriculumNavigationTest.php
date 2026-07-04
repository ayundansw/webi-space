<?php

namespace Tests\Feature\Exploration;

use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use App\Services\Exploration\ProgressService;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurriculumNavigationTest extends TestCase
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

    public function test_peta_kurikulum_shows_both_sample_modules(): void
    {
        $user = $this->member();

        $this->actingAs($user)->get('/eksplorasi/kurikulum')
            ->assertOk()
            ->assertSee('Dunia Software Development')
            ->assertSee('Bagaimana Website Bekerja');
    }

    public function test_first_unit_is_accessible_and_shows_content(): void
    {
        $user = $this->member();
        $unit = Unit::where('order_number', 1)->whereHas('module', fn ($q) => $q->where('order_number', 1))->first();

        $this->actingAs($user)->get('/eksplorasi/unit/'.$unit->id)
            ->assertOk()
            ->assertSee($unit->title)
            ->assertDontSee('belum bisa dibuka');
    }

    public function test_second_unit_is_locked_until_first_completed(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();
        $units = $moduleA->units()->orderBy('order_number')->get();

        $this->actingAs($user)->get('/eksplorasi/unit/'.$units[1]->id)
            ->assertOk()
            ->assertSee('belum bisa dibuka');

        app(ProgressService::class)->completeUnit($user, $units[0]);

        $this->actingAs($user)->get('/eksplorasi/unit/'.$units[1]->id)
            ->assertOk()
            ->assertSee($units[1]->title)
            ->assertDontSee('belum bisa dibuka');
    }

    public function test_second_module_units_locked_until_first_module_checkpoint_done(): void
    {
        $user = $this->member();
        $moduleB = Module::where('order_number', 2)->first();
        $firstUnitOfB = $moduleB->units()->orderBy('order_number')->first();

        $this->actingAs($user)->get('/eksplorasi/unit/'.$firstUnitOfB->id)
            ->assertOk()
            ->assertSee('belum bisa dibuka');
    }

    public function test_checkpoint_blocked_until_all_module_units_completed(): void
    {
        $user = $this->member();
        $moduleA = Module::where('order_number', 1)->first();

        $this->actingAs($user)->get('/eksplorasi/checkpoint/'.$moduleA->checkpoint->id)
            ->assertOk()
            ->assertSee('Belum semua unit selesai');

        $progress = app(ProgressService::class);
        foreach ($moduleA->units()->orderBy('order_number')->get() as $unit) {
            $progress->completeUnit($user, $unit);
        }

        $this->actingAs($user)->get('/eksplorasi/checkpoint/'.$moduleA->checkpoint->id)
            ->assertOk()
            ->assertSee('Checklist Akhir Modul')
            ->assertDontSee('Belum semua unit selesai');
    }

    public function test_non_exploration_member_cannot_access_kurikulum(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'admin',
            'membership_status' => 'active',
        ]);

        $this->actingAs($admin)->get('/eksplorasi/kurikulum')->assertForbidden();
    }
}

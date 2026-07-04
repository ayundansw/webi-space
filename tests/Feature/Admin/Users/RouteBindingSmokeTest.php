<?php
namespace Tests\Feature\Admin\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteBindingSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_load_real_pages_over_http(): void
    {
        $admin = User::create(['name'=>'Admin','email'=>'admin@example.test','password_hash'=>bcrypt('secret123'),'role'=>'admin','membership_status'=>'active']);
        $member = User::create(['name'=>'Member','email'=>'member@example.test','password_hash'=>bcrypt('secret123'),'role'=>'exploration_member','membership_status'=>'active']);

        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee('Member')->assertSee('Manajemen Akun');
        $this->actingAs($admin)->get('/admin/users/create')->assertOk()->assertSee('Buat Akun Baru');
        $this->actingAs($admin)->get('/admin/users/'.$member->id.'/edit')->assertOk()->assertSee('Kelola Akun')->assertSee('member@example.test');
        $this->actingAs($admin)->get('/admin/users/'.((string) \Illuminate\Support\Str::uuid7()).'/edit')->assertNotFound();
    }
}

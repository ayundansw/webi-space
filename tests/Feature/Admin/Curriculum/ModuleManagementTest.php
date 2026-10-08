<?php

namespace Tests\Feature\Admin\Curriculum;

use App\Livewire\Admin\Curriculum\Modules\Create;
use App\Livewire\Admin\Curriculum\Modules\Edit;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModuleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function member(string $role): User
    {
        return User::create([
            'name' => 'Member', 'email' => $role.'@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => $role, 'membership_status' => 'active',
        ]);
    }

    private function moduleAt(int $order, string $title): Module
    {
        return Module::create(['order_number' => $order, 'title' => $title, 'description' => 'D', 'level_number' => 1]);
    }

    /** @param  Module[]  $modules */
    private function assertNoDuplicateOrderNumbers(): void
    {
        $numbers = Module::pluck('order_number')->all();
        $this->assertSame(count($numbers), count(array_unique($numbers)), 'Ditemukan order_number modul yang duplikat.');
    }

    public function test_non_admin_cannot_access_module_management(): void
    {
        $exploration = $this->member('exploration_member');
        $execution = $this->member('execution_member');

        foreach ([$exploration, $execution] as $user) {
            $this->actingAs($user)->get('/admin/curriculum/modules')->assertForbidden();
            $this->actingAs($user)->get('/admin/curriculum/modules/create')->assertForbidden();
        }
    }

    public function test_admin_can_create_a_module(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('order_number', '1')
            ->set('title', 'Modul Uji')
            ->set('description', 'Deskripsi modul uji.')
            ->set('level_number', '1')
            ->call('save')
            ->assertRedirect('/admin/curriculum/modules');

        $this->assertDatabaseHas('modules', [
            'title' => 'Modul Uji',
            'order_number' => 1,
            'level_number' => 1,
        ]);
    }

    public function test_creating_a_module_at_an_occupied_position_shifts_everything_after_it_up(): void
    {
        $admin = $this->admin();
        for ($i = 1; $i <= 10; $i++) {
            $this->moduleAt($i, "Modul {$i}");
        }

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('order_number', '2')
            ->set('title', 'Modul Baru')
            ->set('description', 'D')
            ->set('level_number', '1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, Module::where('title', 'Modul Baru')->first()->order_number);
        $this->assertSame(3, Module::where('title', 'Modul 2')->first()->order_number);
        $this->assertSame(11, Module::where('title', 'Modul 10')->first()->order_number);
        // untouched, sits before the insertion point.
        $this->assertSame(1, Module::where('title', 'Modul 1')->first()->order_number);
        $this->assertNoDuplicateOrderNumbers();
    }

    public function test_creating_a_module_at_the_end_shifts_nothing(): void
    {
        $admin = $this->admin();
        $this->moduleAt(1, 'Modul 1');
        $this->moduleAt(2, 'Modul 2');

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('order_number', '3')
            ->set('title', 'Modul 3')
            ->set('description', 'D')
            ->set('level_number', '1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Module::where('title', 'Modul 1')->first()->order_number);
        $this->assertSame(2, Module::where('title', 'Modul 2')->first()->order_number);
        $this->assertSame(3, Module::where('title', 'Modul 3')->first()->order_number);
        $this->assertNoDuplicateOrderNumbers();
    }

    public function test_admin_can_edit_a_module(): void
    {
        $admin = $this->admin();
        $module = Module::create(['order_number' => 1, 'title' => 'Modul Lama', 'description' => 'D', 'level_number' => 1]);

        Livewire::actingAs($admin)
            ->test(Edit::class, ['module' => $module])
            ->set('title', 'Modul Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Modul Baru', $module->fresh()->title);
    }

    public function test_editing_a_module_can_keep_its_own_order_number(): void
    {
        $admin = $this->admin();
        $module = Module::create(['order_number' => 1, 'title' => 'Modul', 'description' => 'D', 'level_number' => 1]);

        Livewire::actingAs($admin)
            ->test(Edit::class, ['module' => $module])
            ->set('order_number', '1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, $module->fresh()->order_number);
    }

    public function test_editing_a_module_to_move_it_later_shifts_everything_in_between_back(): void
    {
        $admin = $this->admin();
        $modules = [];
        for ($i = 1; $i <= 10; $i++) {
            $modules[$i] = $this->moduleAt($i, "Modul {$i}");
        }

        // Move Modul 3 to position 7.
        Livewire::actingAs($admin)
            ->test(Edit::class, ['module' => $modules[3]])
            ->set('order_number', '7')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(7, $modules[3]->fresh()->order_number);
        $this->assertSame(3, $modules[4]->fresh()->order_number);
        $this->assertSame(4, $modules[5]->fresh()->order_number);
        $this->assertSame(5, $modules[6]->fresh()->order_number);
        $this->assertSame(6, $modules[7]->fresh()->order_number);
        // untouched: before the range and after the range.
        $this->assertSame(1, $modules[1]->fresh()->order_number);
        $this->assertSame(2, $modules[2]->fresh()->order_number);
        $this->assertSame(8, $modules[8]->fresh()->order_number);
        $this->assertSame(9, $modules[9]->fresh()->order_number);
        $this->assertSame(10, $modules[10]->fresh()->order_number);
        $this->assertNoDuplicateOrderNumbers();
    }

    public function test_editing_a_module_to_move_it_earlier_shifts_everything_in_between_forward(): void
    {
        $admin = $this->admin();
        $modules = [];
        for ($i = 1; $i <= 10; $i++) {
            $modules[$i] = $this->moduleAt($i, "Modul {$i}");
        }

        // Move Modul 7 to position 3.
        Livewire::actingAs($admin)
            ->test(Edit::class, ['module' => $modules[7]])
            ->set('order_number', '3')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(3, $modules[7]->fresh()->order_number);
        $this->assertSame(4, $modules[3]->fresh()->order_number);
        $this->assertSame(5, $modules[4]->fresh()->order_number);
        $this->assertSame(6, $modules[5]->fresh()->order_number);
        $this->assertSame(7, $modules[6]->fresh()->order_number);
        // untouched: before the range and after the range.
        $this->assertSame(1, $modules[1]->fresh()->order_number);
        $this->assertSame(2, $modules[2]->fresh()->order_number);
        $this->assertSame(8, $modules[8]->fresh()->order_number);
        $this->assertSame(9, $modules[9]->fresh()->order_number);
        $this->assertSame(10, $modules[10]->fresh()->order_number);
        $this->assertNoDuplicateOrderNumbers();
    }
}

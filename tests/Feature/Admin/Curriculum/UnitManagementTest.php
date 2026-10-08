<?php

namespace Tests\Feature\Admin\Curriculum;

use App\Livewire\Admin\Curriculum\Units\Create;
use App\Livewire\Admin\Curriculum\Units\Edit;
use App\Models\Module;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnitManagementTest extends TestCase
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

    private function module(int $order = 1): Module
    {
        return Module::create(['order_number' => $order, 'title' => 'Modul '.$order, 'description' => 'D', 'level_number' => 1]);
    }

    private function unitAt(Module $module, int $order, string $title): Unit
    {
        return Unit::create([
            'module_id' => $module->id, 'order_number' => $order, 'title' => $title,
            'content' => '', 'estimated_minutes' => 15, 'unit_type' => 'concept',
            'point_value' => 10, 'evaluation_type' => 'none',
        ]);
    }

    private function assertNoDuplicateOrderNumbersInModule(Module $module): void
    {
        $numbers = Unit::where('module_id', $module->id)->pluck('order_number')->all();
        $this->assertSame(count($numbers), count(array_unique($numbers)), 'Ditemukan order_number unit yang duplikat dalam satu modul.');
    }

    public function test_non_admin_cannot_access_unit_management(): void
    {
        $exploration = $this->member('exploration_member');
        $execution = $this->member('execution_member');

        foreach ([$exploration, $execution] as $user) {
            $this->actingAs($user)->get('/admin/curriculum/units')->assertForbidden();
            $this->actingAs($user)->get('/admin/curriculum/units/create')->assertForbidden();
        }
    }

    public function test_admin_can_create_a_unit_without_a_prerequisite(): void
    {
        $admin = $this->admin();
        $module = $this->module();

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('module_id', $module->id)
            ->set('order_number', '1')
            ->set('title', 'Unit Uji')
            ->set('estimated_minutes', '15')
            ->set('unit_type', 'concept')
            ->set('point_value', '10')
            ->set('evaluation_type', 'none')
            ->call('save')
            ->assertRedirect('/admin/curriculum/units');

        $unit = Unit::where('title', 'Unit Uji')->first();
        $this->assertNotNull($unit);
        $this->assertNull($unit->prerequisite_unit_id);
        $this->assertSame('', $unit->content);
    }

    public function test_admin_can_create_a_unit_with_a_prerequisite(): void
    {
        $admin = $this->admin();
        $module = $this->module();
        $first = $this->unitAt($module, 1, 'Unit 1');

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('module_id', $module->id)
            ->set('order_number', '2')
            ->set('title', 'Unit 2')
            ->set('estimated_minutes', '15')
            ->set('unit_type', 'concept')
            ->set('point_value', '10')
            ->set('evaluation_type', 'none')
            ->set('prerequisite_unit_id', $first->id)
            ->call('save')
            ->assertRedirect('/admin/curriculum/units');

        $unit = Unit::where('title', 'Unit 2')->first();
        $this->assertSame($first->id, $unit->prerequisite_unit_id);
    }

    public function test_creating_a_unit_at_an_occupied_position_shifts_only_the_same_modules_units(): void
    {
        $admin = $this->admin();
        $moduleA = $this->module(1);
        $moduleB = $this->module(2);
        $units = [];
        for ($i = 1; $i <= 5; $i++) {
            $units[$i] = $this->unitAt($moduleA, $i, "Unit A{$i}");
        }
        // A unit in a DIFFERENT module happens to sit at the same position — must be untouched.
        $foreignUnit = $this->unitAt($moduleB, 2, 'Unit B2');

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('module_id', $moduleA->id)
            ->set('order_number', '2')
            ->set('title', 'Unit A-Baru')
            ->set('estimated_minutes', '15')
            ->set('unit_type', 'concept')
            ->set('point_value', '10')
            ->set('evaluation_type', 'none')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, Unit::where('title', 'Unit A-Baru')->first()->order_number);
        $this->assertSame(3, $units[2]->fresh()->order_number);
        $this->assertSame(4, $units[3]->fresh()->order_number);
        $this->assertSame(5, $units[4]->fresh()->order_number);
        $this->assertSame(6, $units[5]->fresh()->order_number);
        $this->assertSame(1, $units[1]->fresh()->order_number);
        // different module, same numeric position — must not have shifted.
        $this->assertSame(2, $foreignUnit->fresh()->order_number);
        $this->assertNoDuplicateOrderNumbersInModule($moduleA);
    }

    public function test_creating_a_unit_at_the_end_of_its_module_shifts_nothing(): void
    {
        $admin = $this->admin();
        $module = $this->module();
        $this->unitAt($module, 1, 'Unit 1');
        $this->unitAt($module, 2, 'Unit 2');

        Livewire::actingAs($admin)
            ->test(Create::class)
            ->set('module_id', $module->id)
            ->set('order_number', '3')
            ->set('title', 'Unit 3')
            ->set('estimated_minutes', '15')
            ->set('unit_type', 'concept')
            ->set('point_value', '10')
            ->set('evaluation_type', 'none')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Unit::where('title', 'Unit 1')->first()->order_number);
        $this->assertSame(2, Unit::where('title', 'Unit 2')->first()->order_number);
        $this->assertSame(3, Unit::where('title', 'Unit 3')->first()->order_number);
        $this->assertNoDuplicateOrderNumbersInModule($module);
    }

    public function test_admin_can_edit_unit_metadata_without_touching_legacy_content_column(): void
    {
        $admin = $this->admin();
        $module = $this->module();
        $unit = Unit::create([
            'module_id' => $module->id, 'order_number' => 1, 'title' => 'Unit Lama',
            'content' => 'Konten lama tidak boleh berubah.', 'estimated_minutes' => 15,
            'unit_type' => 'concept', 'point_value' => 10, 'evaluation_type' => 'none',
        ]);

        Livewire::actingAs($admin)
            ->test(Edit::class, ['unit' => $unit])
            ->set('title', 'Unit Baru')
            ->set('point_value', '20')
            ->call('save')
            ->assertHasNoErrors();

        $unit->refresh();
        $this->assertSame('Unit Baru', $unit->title);
        $this->assertSame(20, $unit->point_value);
        $this->assertSame('Konten lama tidak boleh berubah.', $unit->content);
    }

    public function test_editing_a_unit_to_move_it_later_within_its_module_shifts_the_range_back(): void
    {
        $admin = $this->admin();
        $module = $this->module();
        $units = [];
        for ($i = 1; $i <= 5; $i++) {
            $units[$i] = $this->unitAt($module, $i, "Unit {$i}");
        }

        // Move Unit 1 to position 4.
        Livewire::actingAs($admin)
            ->test(Edit::class, ['unit' => $units[1]])
            ->set('order_number', '4')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(4, $units[1]->fresh()->order_number);
        $this->assertSame(1, $units[2]->fresh()->order_number);
        $this->assertSame(2, $units[3]->fresh()->order_number);
        $this->assertSame(3, $units[4]->fresh()->order_number);
        $this->assertSame(5, $units[5]->fresh()->order_number);
        $this->assertNoDuplicateOrderNumbersInModule($module);
    }

    public function test_editing_a_unit_to_move_it_earlier_within_its_module_shifts_the_range_forward(): void
    {
        $admin = $this->admin();
        $module = $this->module();
        $units = [];
        for ($i = 1; $i <= 5; $i++) {
            $units[$i] = $this->unitAt($module, $i, "Unit {$i}");
        }

        // Move Unit 5 to position 2.
        Livewire::actingAs($admin)
            ->test(Edit::class, ['unit' => $units[5]])
            ->set('order_number', '2')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, $units[5]->fresh()->order_number);
        $this->assertSame(1, $units[1]->fresh()->order_number);
        $this->assertSame(3, $units[2]->fresh()->order_number);
        $this->assertSame(4, $units[3]->fresh()->order_number);
        $this->assertSame(5, $units[4]->fresh()->order_number);
        $this->assertNoDuplicateOrderNumbersInModule($module);
    }

    public function test_moving_a_unit_to_a_different_module_shifts_only_the_destination_modules_units(): void
    {
        $admin = $this->admin();
        $moduleA = $this->module(1);
        $moduleB = $this->module(2);
        $movedUnit = $this->unitAt($moduleA, 1, 'Unit A1');
        $units = [];
        for ($i = 1; $i <= 3; $i++) {
            $units[$i] = $this->unitAt($moduleB, $i, "Unit B{$i}");
        }

        Livewire::actingAs($admin)
            ->test(Edit::class, ['unit' => $movedUnit])
            ->set('module_id', $moduleB->id)
            ->set('order_number', '2')
            ->call('save')
            ->assertHasNoErrors();

        $movedUnit->refresh();
        $this->assertSame($moduleB->id, $movedUnit->module_id);
        $this->assertSame(2, $movedUnit->order_number);
        $this->assertSame(1, $units[1]->fresh()->order_number);
        $this->assertSame(3, $units[2]->fresh()->order_number);
        $this->assertSame(4, $units[3]->fresh()->order_number);
        $this->assertNoDuplicateOrderNumbersInModule($moduleB);
    }

    public function test_unit_cannot_be_its_own_prerequisite(): void
    {
        $admin = $this->admin();
        $module = $this->module();
        $unit = Unit::create([
            'module_id' => $module->id, 'order_number' => 1, 'title' => 'Unit',
            'content' => '', 'estimated_minutes' => 15, 'unit_type' => 'concept',
            'point_value' => 10, 'evaluation_type' => 'none',
        ]);

        Livewire::actingAs($admin)
            ->test(Edit::class, ['unit' => $unit])
            ->set('prerequisite_unit_id', $unit->id)
            ->call('save')
            ->assertHasErrors(['prerequisite_unit_id']);

        $this->assertNull($unit->fresh()->prerequisite_unit_id);
    }

    public function test_removing_a_previously_set_prerequisite_persists_as_null(): void
    {
        $admin = $this->admin();
        $module = $this->module();
        $prerequisite = Unit::create([
            'module_id' => $module->id, 'order_number' => 1, 'title' => 'Unit 1',
            'content' => '', 'estimated_minutes' => 15, 'unit_type' => 'concept',
            'point_value' => 10, 'evaluation_type' => 'none',
        ]);
        $unit = Unit::create([
            'module_id' => $module->id, 'order_number' => 2, 'title' => 'Unit 2',
            'content' => '', 'estimated_minutes' => 15, 'unit_type' => 'concept',
            'point_value' => 10, 'evaluation_type' => 'none',
            'prerequisite_unit_id' => $prerequisite->id,
        ]);

        Livewire::actingAs($admin)
            ->test(Edit::class, ['unit' => $unit])
            ->set('prerequisite_unit_id', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($unit->fresh()->prerequisite_unit_id);
    }
}

<?php

namespace Tests\Feature\Profile;

use App\Livewire\Profile\Edit;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserUnitProgress;
use App\Services\Exploration\ProgressService;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class EditTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $role = 'exploration_member', string $status = 'active', string $email = 'anggota@example.test'): User
    {
        return User::create([
            'name' => 'Anggota',
            'email' => $email,
            'password_hash' => bcrypt('secret123'),
            'role' => $role,
            'membership_status' => $status,
        ]);
    }

    public function test_member_can_update_own_name(): void
    {
        $user = $this->member();

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('name', 'Nama Baru')
            ->call('saveProfile');

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
    }

    public function test_member_can_set_interest_field_as_array(): void
    {
        $user = $this->member();

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('interestField', ['frontend', 'ui_ux'])
            ->call('saveProfile');

        $user->refresh();
        $this->assertSame(['frontend', 'ui_ux'], $user->interest_field);
    }

    public function test_interest_field_can_be_cleared_back_to_null(): void
    {
        $user = $this->member();
        $user->update(['interest_field' => ['backend']]);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('interestField', [])
            ->call('saveProfile');

        $user->refresh();
        $this->assertNull($user->interest_field);
    }

    public function test_invalid_interest_field_value_is_rejected(): void
    {
        $user = $this->member();

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('interestField', ['not_a_real_option'])
            ->call('saveProfile')
            ->assertHasErrors('interestField.*');
    }

    /**
     * SECURITY (most important test in this batch): the profile component
     * must not expose `role` or `membership_status` as writable state at
     * all — proven by attempting to set them through Livewire's own test
     * harness (the same mechanism a real request update goes through) and
     * confirming there is no such property to write to.
     */
    public function test_role_property_does_not_exist_on_profile_component(): void
    {
        $user = $this->member();

        $this->expectException(\Throwable::class);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('role', 'admin');
    }

    public function test_membership_status_property_does_not_exist_on_profile_component(): void
    {
        $user = $this->member();

        $this->expectException(\Throwable::class);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('membership_status', 'inactive');
    }

    public function test_email_property_does_not_exist_on_profile_component(): void
    {
        $user = $this->member();

        $this->expectException(\Throwable::class);

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('email', 'ambil-alih@example.test');
    }

    /**
     * Defense-in-depth beyond the property-absence checks above: even for a
     * role/status combination that ISN'T the common default (so this isn't
     * trivially true by coincidence), a normal saveProfile() call must never
     * touch these two columns.
     */
    public function test_saving_profile_never_changes_role_or_membership_status(): void
    {
        $user = $this->member('execution_member', 'inactive');

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('name', 'Nama Lain')
            ->call('saveProfile');

        $user->refresh();
        $this->assertSame('execution_member', $user->role);
        $this->assertSame('inactive', $user->membership_status);
    }

    public function test_email_is_shown_read_only_and_never_changes(): void
    {
        $user = $this->member();

        $response = $this->actingAs($user)->get('/profile')->assertOk();
        $response->assertSee('anggota@example.test');

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('name', 'Nama Lain')
            ->call('saveProfile');

        $user->refresh();
        $this->assertSame('anggota@example.test', $user->email);
    }

    public function test_change_password_succeeds_with_correct_current_password(): void
    {
        $user = $this->member();

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('current_password', 'secret123')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('changePassword');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password_hash));
        $this->assertFalse(Hash::check('secret123', $user->password_hash));
    }

    public function test_change_password_rejected_when_current_password_is_wrong(): void
    {
        $user = $this->member();

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('current_password', 'salahpassword')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('changePassword')
            ->assertHasErrors('current_password');

        $user->refresh();
        $this->assertTrue(Hash::check('secret123', $user->password_hash));
    }

    public function test_change_password_rejected_when_confirmation_does_not_match(): void
    {
        $user = $this->member();

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('current_password', 'secret123')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'differentpassword')
            ->call('changePassword')
            ->assertHasErrors('password');

        $user->refresh();
        $this->assertTrue(Hash::check('secret123', $user->password_hash));
    }

    public function test_change_password_rejected_when_new_password_too_short(): void
    {
        $user = $this->member();

        Livewire::actingAs($user)
            ->test(Edit::class)
            ->set('current_password', 'secret123')
            ->set('password', 'short')
            ->set('password_confirmation', 'short')
            ->call('changePassword')
            ->assertHasErrors('password');

        $user->refresh();
        $this->assertTrue(Hash::check('secret123', $user->password_hash));
    }

    public function test_all_three_roles_can_access_their_own_profile_page(): void
    {
        $admin = $this->member('admin');
        $exploration = User::create([
            'name' => 'Exp', 'email' => 'exp@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member', 'membership_status' => 'active',
        ]);
        $execution = User::create([
            'name' => 'Eks', 'email' => 'eks@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'execution_member', 'membership_status' => 'active',
        ]);

        $this->actingAs($admin)->get('/profile')->assertOk();
        $this->actingAs($exploration)->get('/profile')->assertOk();
        $this->actingAs($execution)->get('/profile')->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    /**
     * Fase 3 Batch 7b poin 2: Fox gallery status must match
     * FoxAvatarService::tierForPoints() exactly, not a separate calculation
     * — 150 points sits in tier 3 (101-300, see FoxAvatarServiceTest
     * boundary table), so tiers 1-3 should read "TERBUKA" and tiers 4-5
     * should show their point threshold instead.
     */
    public function test_fox_gallery_shows_correct_unlocked_and_locked_tiers(): void
    {
        $user = $this->member('exploration_member');
        app(ProgressService::class)->ensureProgress($user)->update(['total_points' => 150]);

        $html = Livewire::actingAs($user)->test(Edit::class)->assertOk()->html();

        $this->assertStringContainsString('Galeri Avatar Fox', $html);
        $this->assertSame(3, substr_count($html, 'TERBUKA'));
        $this->assertStringContainsString('301+ poin', $html);
        $this->assertStringContainsString('501+ poin', $html);
    }

    public function test_fox_gallery_and_heatmap_are_hidden_for_non_exploration_roles(): void
    {
        $execution = $this->member('execution_member', email: 'execution@example.test');
        $admin = $this->member('admin', email: 'admin@example.test');

        $executionHtml = Livewire::actingAs($execution)->test(Edit::class)->html();
        $adminHtml = Livewire::actingAs($admin)->test(Edit::class)->html();

        $this->assertStringNotContainsString('Galeri Avatar Fox', $executionHtml);
        $this->assertStringNotContainsString('Kalender Aktivitas', $executionHtml);
        $this->assertStringNotContainsString('Galeri Avatar Fox', $adminHtml);
        $this->assertStringNotContainsString('Kalender Aktivitas', $adminHtml);
    }

    public function test_activity_heatmap_shown_only_for_exploration_member(): void
    {
        $user = $this->member('exploration_member');

        $html = Livewire::actingAs($user)->test(Edit::class)->assertOk()->html();

        $this->assertStringContainsString('Kalender Aktivitas', $html);
    }

    /**
     * Reuse check (batasan wajib): the picker embedded here must be the
     * REAL App\Livewire\Eksekusi\AvatarPicker component, not a rebuilt copy
     * — proven by its own "Pilih Avatar" heading (that string is only ever
     * rendered by that component's own view) showing up inside the Profil
     * page's HTML.
     */
    public function test_execution_member_sees_the_real_embedded_avatar_picker(): void
    {
        $user = $this->member('execution_member');

        $html = Livewire::actingAs($user)->test(Edit::class)->assertOk()->html();

        $this->assertStringContainsString('Pilih Avatar', $html);
    }

    public function test_avatar_picker_is_hidden_for_non_execution_roles(): void
    {
        $exploration = $this->member('exploration_member', email: 'exploration@example.test');
        $admin = $this->member('admin', email: 'admin@example.test');

        $this->assertStringNotContainsString('Pilih Avatar', Livewire::actingAs($exploration)->test(Edit::class)->html());
        $this->assertStringNotContainsString('Pilih Avatar', Livewire::actingAs($admin)->test(Edit::class)->html());
    }

    public function test_execution_member_project_contributions_shows_correct_task_count_without_a_role_column(): void
    {
        $admin = $this->member('admin', email: 'admin@example.test');
        $user = $this->member('execution_member', email: 'execution@example.test');
        $other = User::create([
            'name' => 'Lainnya', 'email' => 'lainnya@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'execution_member', 'membership_status' => 'active',
        ]);

        $project = Project::create([
            'title' => 'Website Portfolio RIT', 'description' => 'x', 'objective' => 'y',
            'project_type' => 'internal', 'status' => 'active',
            'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'M1', 'target_date' => '2026-07-15', 'sort_order' => 1]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $user->id]);

        $taskA = Task::create(['project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'T1', 'status' => 'todo', 'priority' => 'low', 'deadline' => '2026-07-20', 'created_by' => $admin->id]);
        $taskB = Task::create(['project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'T2', 'status' => 'in_progress', 'priority' => 'low', 'deadline' => '2026-07-20', 'created_by' => $admin->id]);
        $taskOther = Task::create(['project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'T3', 'status' => 'todo', 'priority' => 'low', 'deadline' => '2026-07-20', 'created_by' => $admin->id]);

        TaskAssignment::create(['task_id' => $taskA->id, 'user_id' => $user->id, 'assigned_by' => $admin->id]);
        TaskAssignment::create(['task_id' => $taskB->id, 'user_id' => $user->id, 'assigned_by' => $admin->id]);
        TaskAssignment::create(['task_id' => $taskOther->id, 'user_id' => $other->id, 'assigned_by' => $admin->id]);

        $html = Livewire::actingAs($user)->test(Edit::class)->assertOk()->html();

        $this->assertStringContainsString('Website Portfolio RIT', $html);
        $this->assertStringContainsString('2 task', $html);
    }

    /**
     * Fase 8 Batch 6 (gap #2 dari Batch 2's audit): "Kontribusi Proyek"
     * dulu keyed ke role==='execution_member' literal, jadi Mode Ganda
     * member (exploration_member) yang sungguhan sudah jadi ProjectMember +
     * dapat task lewat akses Eksekusi mereka tidak pernah melihat
     * kontribusi mereka SENDIRI di profil mereka sendiri. Sekarang harus
     * muncul, apa pun role/active_mode SAAT INI (data historis, bukan
     * status live, yang menentukan) -- termasuk kasus di test ini di mana
     * active_mode sudah balik ke null (origin) lagi.
     */
    public function test_dual_mode_member_project_contributions_shows_even_when_currently_back_at_origin(): void
    {
        $admin = $this->member('admin', email: 'admin@example.test');
        $user = User::create([
            'name' => 'Dual Mode', 'email' => 'dualmode@example.test', 'password_hash' => bcrypt('secret123'),
            'role' => 'exploration_member', 'membership_status' => 'active',
            'dual_mode_status' => 'approved', 'active_mode' => null,
        ]);

        $project = Project::create([
            'title' => 'Proyek Mode Ganda', 'description' => 'x', 'objective' => 'y',
            'project_type' => 'internal', 'status' => 'active',
            'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'M1', 'target_date' => '2026-07-15', 'sort_order' => 1]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $user->id]);
        $task = Task::create(['project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'T1', 'status' => 'todo', 'priority' => 'low', 'deadline' => '2026-07-20', 'created_by' => $admin->id]);
        TaskAssignment::create(['task_id' => $task->id, 'user_id' => $user->id, 'assigned_by' => $admin->id]);

        $html = Livewire::actingAs($user)->test(Edit::class)->assertOk()->html();

        $this->assertStringContainsString('Kontribusi Proyek', $html);
        $this->assertStringContainsString('Proyek Mode Ganda', $html);
        $this->assertStringContainsString('1 task', $html);
    }

    /**
     * Counterpart: a plain exploration_member who has never touched
     * Eksekusi (no ProjectMember row at all) must NOT see an empty
     * "Kontribusi Proyek" card cluttering their profile -- that behavior
     * is reserved for execution_member (native, always shown even when
     * empty) and dual-mode members who actually have data.
     */
    public function test_plain_exploration_member_never_sees_an_empty_project_contributions_card(): void
    {
        $user = $this->member('exploration_member');

        $html = Livewire::actingAs($user)->test(Edit::class)->assertOk()->html();

        $this->assertStringNotContainsString('Kontribusi Proyek', $html);
    }

    public function test_info_mode_ganda_card_shown_for_members_not_admin(): void
    {
        $exploration = $this->member('exploration_member', email: 'exploration@example.test');
        $execution = $this->member('execution_member', email: 'execution@example.test');
        $admin = $this->member('admin', email: 'admin@example.test');

        $this->assertStringContainsString('Info Mode Ganda', Livewire::actingAs($exploration)->test(Edit::class)->html());
        $this->assertStringContainsString('Info Mode Ganda', Livewire::actingAs($execution)->test(Edit::class)->html());
        $this->assertStringNotContainsString('Info Mode Ganda', Livewire::actingAs($admin)->test(Edit::class)->html());
    }

    /**
     * Heatmap tooltip fix: proves each cell is bound to the SPECIFIC
     * calendar date it visually represents, not just that the right number
     * of cells exists. `data-date`/`data-tooltip` are rendered server-side
     * (read by Alpine's showTooltip() at hover/tap time), so asserting on
     * them directly proves the date-to-cell mapping without needing a
     * browser to actually trigger hover/tap.
     */
    public function test_each_heatmap_cell_maps_to_its_correct_calendar_date_and_activity_count(): void
    {
        $this->seed(ExplorationSampleSeeder::class);
        $user = $this->member('exploration_member');
        $unit = Unit::first();
        // The heatmap is only rendered when $explorationProgress exists
        // (Edit::render()) — a bare User row has no UserExplorationProgress
        // until something creates one, same as FoxAvatarServiceTest's setup.
        app(ProgressService::class)->ensureProgress($user);

        $activeDate = Carbon::today()->subDays(10);
        UserUnitProgress::create([
            'user_id' => $user->id, 'unit_id' => $unit->id, 'status' => 'completed',
            'open_count_without_completion' => 0, 'completed_at' => $activeDate->copy()->setTime(10, 0),
        ]);

        $html = Livewire::actingAs($user)->test(Edit::class)->assertOk()->html();

        // The two attributes sit on separate lines in the Blade source
        // (multi-line tag formatting), so the rendered HTML has a newline +
        // indentation between them, not a single space — \s+ tolerates
        // that instead of assuming exact adjacent-attribute formatting.
        $activeLabel = $activeDate->copy()->locale('id')->translatedFormat('d F Y');
        $this->assertMatchesRegularExpression(
            '/data-date="'.preg_quote($activeDate->format('Y-m-d'), '/').'"\s+data-tooltip="1 aktivitas pada '.preg_quote($activeLabel, '/').'"/',
            $html
        );

        // A tracked day with zero activity still gets an informative
        // tooltip (not silence) — pinned exactly per the spec wording.
        $quietDate = Carbon::today()->subDays(20);
        $quietLabel = $quietDate->copy()->locale('id')->translatedFormat('d F Y');
        $this->assertMatchesRegularExpression(
            '/data-date="'.preg_quote($quietDate->format('Y-m-d'), '/').'"\s+data-tooltip="Tidak ada aktivitas pada '.preg_quote($quietLabel, '/').'"/',
            $html
        );

        // Exactly HEATMAP_DAYS (182 = 6 months) interactive cells exist —
        // proves the whole tracked window is represented, not just the two
        // spot-checked days above.
        $this->assertSame(182, substr_count($html, 'data-date="'));
    }
}

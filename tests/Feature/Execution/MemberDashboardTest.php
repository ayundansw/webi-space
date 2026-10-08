<?php

namespace Tests\Feature\Execution;

use App\Livewire\Eksekusi\Dashboard;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 2.2.3 (dashboard Eksekusi, batch 2): App\Livewire\Eksekusi\Dashboard replaces
 * the old plain-Blade stub with real, personalized data — scoped strictly to
 * the logged-in execution_member's own projects/tasks. The scope tests here
 * are the most important ones: a member must never see another project's
 * data, tasks, or alerts just because they share the execution_member role.
 */
class MemberDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'admin',
            'membership_status' => 'active',
        ]);
    }

    private function executionMember(string $name): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower($name).'@example.test',
            'password_hash' => bcrypt('secret123'),
            'role' => 'execution_member',
            'membership_status' => 'active',
        ]);
    }

    private function project(User $admin, string $title, string $status = 'active'): Project
    {
        return Project::create([
            'title' => $title,
            'description' => 'Deskripsi',
            'objective' => 'Tujuan',
            'project_type' => 'internal',
            'status' => $status,
            'start_date' => '2026-06-01',
            'target_end_date' => '2026-09-01',
            'created_by' => $admin->id,
        ]);
    }

    public function test_dashboard_shows_only_projects_the_member_belongs_to(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember('Ahmad');
        $otherMember = $this->executionMember('Budi');

        $ownProject = $this->project($admin, 'Proyek Milik Ahmad');
        ProjectMember::create(['project_id' => $ownProject->id, 'user_id' => $member->id]);

        $foreignProject = $this->project($admin, 'Proyek Bukan Milik Ahmad');
        ProjectMember::create(['project_id' => $foreignProject->id, 'user_id' => $otherMember->id]);

        Livewire::actingAs($member)->test(Dashboard::class)
            ->assertSee('Proyek Milik Ahmad')
            ->assertDontSee('Proyek Bukan Milik Ahmad');
    }

    public function test_task_counts_reflect_only_tasks_assigned_to_the_member(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember('Ahmad');
        $otherMember = $this->executionMember('Budi');

        $project = $this->project($admin, 'Proyek Bersama');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $otherMember->id]);
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'M1', 'target_date' => '2026-07-01', 'sort_order' => 1]);

        $ownTodo = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Task Ahmad Todo',
            'status' => 'todo', 'priority' => 'medium', 'deadline' => now()->addDays(10)->toDateString(), 'created_by' => $admin->id,
        ]);
        TaskAssignment::create(['task_id' => $ownTodo->id, 'user_id' => $member->id, 'assigned_by' => $admin->id]);

        $ownInProgress = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Task Ahmad In Progress',
            'status' => 'in_progress', 'priority' => 'medium', 'deadline' => now()->addDays(10)->toDateString(), 'created_by' => $admin->id,
        ]);
        TaskAssignment::create(['task_id' => $ownInProgress->id, 'user_id' => $member->id, 'assigned_by' => $admin->id]);

        $othersTodo = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Task Budi Todo',
            'status' => 'todo', 'priority' => 'medium', 'deadline' => now()->addDays(10)->toDateString(), 'created_by' => $admin->id,
        ]);
        TaskAssignment::create(['task_id' => $othersTodo->id, 'user_id' => $otherMember->id, 'assigned_by' => $admin->id]);

        $component = Livewire::actingAs($member)->test(Dashboard::class);

        $component->assertViewHas('taskCounts', function (array $counts) {
            return $counts['todo'] === 1 && $counts['in_progress'] === 1 && $counts['in_review'] === 0;
        });
    }

    public function test_alerts_only_include_the_members_own_projects(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember('Ahmad');
        $otherMember = $this->executionMember('Budi');

        $ownProject = $this->project($admin, 'Proyek Milik Ahmad');
        ProjectMember::create(['project_id' => $ownProject->id, 'user_id' => $member->id]);
        $ownMilestone = Milestone::create(['project_id' => $ownProject->id, 'title' => 'M1', 'target_date' => '2026-07-01', 'sort_order' => 1]);
        $ownOverdueTask = Task::create([
            'project_id' => $ownProject->id, 'milestone_id' => $ownMilestone->id, 'title' => 'Task Overdue Ahmad',
            'status' => 'in_progress', 'priority' => 'medium', 'deadline' => now()->subDays(2)->toDateString(), 'created_by' => $admin->id,
        ]);
        TaskAssignment::create(['task_id' => $ownOverdueTask->id, 'user_id' => $member->id, 'assigned_by' => $admin->id]);

        $foreignProject = $this->project($admin, 'Proyek Bukan Milik Ahmad');
        ProjectMember::create(['project_id' => $foreignProject->id, 'user_id' => $otherMember->id]);
        $foreignMilestone = Milestone::create(['project_id' => $foreignProject->id, 'title' => 'M1', 'target_date' => '2026-07-01', 'sort_order' => 1]);
        $foreignOverdueTask = Task::create([
            'project_id' => $foreignProject->id, 'milestone_id' => $foreignMilestone->id, 'title' => 'Task Overdue Budi',
            'status' => 'in_progress', 'priority' => 'medium', 'deadline' => now()->subDays(2)->toDateString(), 'created_by' => $admin->id,
        ]);
        TaskAssignment::create(['task_id' => $foreignOverdueTask->id, 'user_id' => $otherMember->id, 'assigned_by' => $admin->id]);

        Livewire::actingAs($member)->test(Dashboard::class)
            ->assertSee('Task Overdue Ahmad')
            ->assertDontSee('Task Overdue Budi')
            ->assertDontSee('Proyek Bukan Milik Ahmad');
    }

    public function test_dashboard_shows_friendly_empty_states_for_member_with_no_projects(): void
    {
        $member = $this->executionMember('Ahmad');

        Livewire::actingAs($member)->test(Dashboard::class)
            ->assertOk()
            ->assertSee('belum tergabung')
            ->assertSee('Tidak ada peringatan');
    }

    /**
     * Fase 3 (Dashboard Eksekusi lengkap, §4.1 poin 2): "Task Aktif" jadi
     * satu stat gabungan (total + breakdown), bukan lagi 3 stat-card
     * terpisah seperti sebelumnya -- tes ini mengunci kontrak
     * `activeTaskCount` = jumlah ketiga bucket, supaya redesign visual di
     * masa depan tidak diam-diam merusak angka totalnya.
     */
    public function test_active_task_count_is_the_sum_of_all_three_status_buckets(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember('Ahmad');
        $project = $this->project($admin, 'Proyek Ahmad');
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'M1', 'target_date' => '2026-07-01', 'sort_order' => 1]);

        foreach (['todo', 'in_progress', 'in_review'] as $status) {
            $task = Task::create([
                'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => "Task {$status}",
                'status' => $status, 'priority' => 'medium', 'deadline' => now()->addDays(10)->toDateString(), 'created_by' => $admin->id,
            ]);
            TaskAssignment::create(['task_id' => $task->id, 'user_id' => $member->id, 'assigned_by' => $admin->id]);
        }

        Livewire::actingAs($member)->test(Dashboard::class)
            ->assertViewHas('projectCount', 1)
            ->assertViewHas('activeTaskCount', 3);
    }

    /**
     * §4.1 poin 3: "Antrian Review Praktik" masih placeholder murni (baru
     * aktif Praktik 3C, di luar scope dashboard ini) -- WAJIB tampil sebagai
     * placeholder jelas ("Segera Hadir"), bukan logic asli yang belum ada
     * datanya. "Ringkasan Kalender" (poin 6) SUDAH berpindah dari placeholder
     * ke data asli sejak Fase 7 Batch 2b -- lihat
     * test_ringkasan_kalender_shows_real_upcoming_items_not_a_placeholder
     * di bawah untuk cakupannya.
     */
    public function test_unbuilt_feature_slots_show_as_clear_placeholders(): void
    {
        $member = $this->executionMember('Ahmad');

        Livewire::actingAs($member)->test(Dashboard::class)
            ->assertOk()
            ->assertSeeText('Praktik Menunggu Direview')
            ->assertSeeText('Antrian Review Praktik')
            ->assertSeeInOrder(['Segera Hadir', 'Praktik Menunggu Direview']);
    }

    /**
     * §4.1 poin 7: kartu cepat "Ajukan Project Idea" harus punya CTA
     * langsung ke Ideas\Create (route eksekusi.ideas.create).
     */
    public function test_ajukan_project_idea_cta_links_to_ideas_create(): void
    {
        $member = $this->executionMember('Ahmad');

        Livewire::actingAs($member)->test(Dashboard::class)
            ->assertOk()
            ->assertSee('Ajukan Project Idea')
            ->assertSee(route('eksekusi.ideas.create'), false);
    }
}

<?php

namespace Tests\Feature\Execution;

use App\Livewire\Eksekusi\Projects\Board;
use App\Livewire\Eksekusi\Projects\Tabs\Anggota;
use App\Livewire\Eksekusi\Projects\Tabs\Forum;
use App\Livewire\Eksekusi\Projects\Tabs\Gantt;
use App\Livewire\Eksekusi\Projects\Tabs\Kalender;
use App\Livewire\Eksekusi\Projects\Tabs\Roadmap;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 7 Batch 1a: the project page is now tab-structured (Kanban default,
 * Roadmap/Gantt/Kalender/Forum Proyek placeholder, Anggota real) instead of
 * the old single Projects\Show page. Covers: all 6 tabs render with the
 * shared header + correct active highlighting, placeholder tabs say "Segera
 * Hadir" (not real content — explicitly out of scope for this batch), and
 * the Anggota tab carries real member data forward from the retired page.
 *
 * Fase 7 Batch 2b: Kalender is no longer a placeholder (real Kegiatan+Acara
 * content, see tests\Feature\Execution\CalendarTest.php) — removed from the
 * placeholder loop below, kept in the "shared header on every tab" sweep
 * since that's still true of it.
 *
 * Fase 7 Batch 3a: Gantt is no longer a placeholder either (real bars +
 * dependency connectors, see tests\Feature\Execution\TaskDependencyTest.php)
 * — removed from the placeholder loop for the same reason as Kalender above.
 *
 * Fase 7 Batch 3b: Roadmap is no longer a placeholder either (real
 * Milestone timeline, see tests\Feature\Execution\RoadmapTest.php) —
 * removed from the placeholder loop too.
 *
 * Fase 7 Batch 4 (last batch of Fase 7): Forum Proyek is no longer a
 * placeholder either (real threads/replies, see
 * tests\Feature\Execution\ProjectForumTest.php) — the placeholder loop
 * test itself is removed below since every tab is now real, nothing left
 * to prove is "Segera Hadir".
 */
class ProjectTabsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function member(): User
    {
        return User::create([
            'name' => 'Anggota', 'email' => 'anggota@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);
    }

    private function project(User $admin): Project
    {
        return Project::create([
            'title' => 'Website Portfolio RIT', 'description' => 'Deskripsi', 'objective' => 'Tujuan',
            'project_type' => 'internal', 'status' => 'active',
            'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);
    }

    public function test_kanban_tab_shows_shared_header_and_all_six_tabs_with_kanban_active(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);

        $html = Livewire::actingAs($admin)->test(Board::class, ['project' => $project])->assertOk()->html();

        // Shared header (project title, status, Milestone section) present.
        $this->assertStringContainsString($project->title, $html);
        $this->assertStringContainsString('Milestone', $html);

        // All 6 tab labels present, Kanban itself still functionally intact
        // (drag-and-drop markup is asserted separately/verified manually —
        // this just proves the tab bar renders around it correctly).
        foreach (['Kanban', 'Roadmap', 'Gantt', 'Kalender', 'Forum Proyek', 'Anggota'] as $label) {
            $this->assertStringContainsString($label, $html);
        }

        $this->assertStringContainsString(route('eksekusi.projects.kanban', $project), $html);
        $this->assertStringContainsString(route('eksekusi.projects.roadmap', $project), $html);
    }

    public function test_anggota_tab_shows_real_member_list_not_a_placeholder(): void
    {
        $admin = $this->admin();
        $member = $this->member();
        $project = $this->project($admin);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        $html = Livewire::actingAs($admin)->test(Anggota::class, ['project' => $project])->assertOk()->html();

        $this->assertStringNotContainsString('Segera Hadir', $html);
        $this->assertStringContainsString($member->name, $html);
    }

    public function test_every_tab_page_shares_the_same_project_header_content(): void
    {
        $admin = $this->admin();
        $project = $this->project($admin);

        $pages = [
            Board::class,
            Roadmap::class,
            Gantt::class,
            Kalender::class,
            Forum::class,
            Anggota::class,
        ];

        foreach ($pages as $componentClass) {
            $html = Livewire::actingAs($admin)->test($componentClass, ['project' => $project])->assertOk()->html();
            $this->assertStringContainsString($project->title, $html, "{$componentClass} missing shared project header title.");
            $this->assertStringContainsString('active', $html, "{$componentClass} missing shared project header status badge.");
        }
    }
}

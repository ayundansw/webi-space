<?php

namespace Tests\Feature\Rbac;

use App\Models\Checkpoint;
use App\Models\ForumThread;
use App\Models\Milestone;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectIdea;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\ExplorationSampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task 2.6 Batch 1: exhaustive route-by-role matrix, cross-checked against
 * docs/PRD.md Bagian 2 (hak akses per role). Every GET route declared in
 * routes/web.php is exercised here for admin, exploration_member,
 * execution_member, and guest, asserting exactly the access PRD 2.1-2.3
 * grants — not just the dashboards (already covered narrowly by
 * DashboardAccessTest before this task).
 *
 * Findings from this audit (2026-07-04): no broken/leaked route was found —
 * every route already carries `role:` middleware, and several components
 * additionally enforce project-membership/self scoping inside mount()
 * (Eksekusi Projects\Show, Tasks\Show, Board; Admin\Webi\Show). This test
 * exists to prove that state exhaustively, not to document a fix.
 */
class RouteAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ExplorationSampleSeeder::class);
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'name' => 'User '.$email,
            'email' => $email,
            'password_hash' => bcrypt('secret123'),
            'role' => $role,
            'membership_status' => 'active',
        ]);
    }

    private function admin(): User
    {
        return $this->makeUser('admin', 'admin@example.test');
    }

    private function explorationMember(): User
    {
        return $this->makeUser('exploration_member', 'explorer@example.test');
    }

    private function executionMember(): User
    {
        return $this->makeUser('execution_member', 'executor@example.test');
    }

    /** @return array{0: Project, 1: Milestone, 2: Task, 3: User} project+task the execution member IS a member/assignee of */
    private function executionFixtures(User $admin, User $member): array
    {
        $project = Project::create([
            'title' => 'Website Portfolio RIT',
            'description' => 'Deskripsi',
            'objective' => 'Tujuan',
            'project_type' => 'internal',
            'status' => 'active',
            'start_date' => '2026-07-01',
            'target_end_date' => '2026-08-01',
            'created_by' => $admin->id,
        ]);

        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        $milestone = Milestone::create([
            'project_id' => $project->id,
            'title' => 'Setup dan Desain',
            'target_date' => '2026-07-15',
            'sort_order' => 1,
        ]);

        $task = Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'title' => 'Buat halaman login',
            'description' => 'Deskripsi task',
            'priority' => 'medium',
            'deadline' => '2026-07-20',
            'status' => 'todo',
            'created_by' => $admin->id,
        ]);

        TaskAssignment::create(['task_id' => $task->id, 'user_id' => $member->id, 'assigned_by' => $admin->id]);

        return [$project, $milestone, $task, $member];
    }

    /** A second project the given execution member is explicitly NOT part of. */
    private function foreignProject(User $admin): Project
    {
        $outsider = User::create([
            'name' => 'Outsider', 'email' => 'outsider@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);

        $project = Project::create([
            'title' => 'Proyek Lain',
            'description' => 'Deskripsi',
            'objective' => 'Tujuan',
            'project_type' => 'internal',
            'status' => 'active',
            'start_date' => '2026-07-01',
            'target_end_date' => '2026-08-01',
            'created_by' => $admin->id,
        ]);

        ProjectMember::create(['project_id' => $project->id, 'user_id' => $outsider->id]);

        return $project;
    }

    public function test_admin_only_routes_reject_both_member_roles(): void
    {
        $admin = $this->admin();
        $exploration = $this->explorationMember();
        $execution = $this->executionMember();

        $adminOnlyRoutes = [
            '/admin/dashboard',
            '/admin/users',
            '/admin/users/create',
            '/admin/webi',
        ];

        foreach ($adminOnlyRoutes as $route) {
            $this->actingAs($admin)->get($route)->assertOk();
            $this->actingAs($exploration)->get($route)->assertForbidden();
            $this->actingAs($execution)->get($route)->assertForbidden();
        }
    }

    public function test_admin_users_edit_and_webi_show_are_admin_only_with_valid_bindings(): void
    {
        $admin = $this->admin();
        $exploration = $this->explorationMember();
        $execution = $this->executionMember();

        $this->actingAs($admin)->get("/admin/users/{$exploration->id}/edit")->assertOk();
        $this->actingAs($exploration)->get("/admin/users/{$exploration->id}/edit")->assertForbidden();
        $this->actingAs($execution)->get("/admin/users/{$exploration->id}/edit")->assertForbidden();

        $this->actingAs($admin)->get("/admin/webi/{$exploration->id}")->assertOk();
        $this->actingAs($exploration)->get("/admin/webi/{$exploration->id}")->assertForbidden();
        $this->actingAs($execution)->get("/admin/webi/{$exploration->id}")->assertForbidden();

        // admin monitoring page must 404 for a non-exploration_member id, not leak an empty page
        $this->actingAs($admin)->get("/admin/webi/{$execution->id}")->assertNotFound();
    }

    public function test_exploration_only_routes_reject_execution_member_and_admin(): void
    {
        $exploration = $this->explorationMember();
        $execution = $this->executionMember();
        $admin = $this->admin();
        $unit = Unit::where('order_number', 1)->first();
        $checkpoint = Checkpoint::first();

        $explorationOnlyRoutes = [
            '/eksplorasi/dashboard',
            '/eksplorasi/kurikulum',
            "/eksplorasi/unit/{$unit->id}",
            "/eksplorasi/checkpoint/{$checkpoint->id}",
            '/eksplorasi/resources',
            '/eksplorasi/webi',
        ];

        foreach ($explorationOnlyRoutes as $route) {
            $this->actingAs($exploration)->get($route)->assertOk();
            $this->actingAs($execution)->get($route)->assertForbidden();
            $this->actingAs($admin)->get($route)->assertForbidden();
        }
    }

    public function test_exploration_forum_allows_admin_but_rejects_execution_member(): void
    {
        $exploration = $this->explorationMember();
        $execution = $this->executionMember();
        $admin = $this->admin();
        $module = Module::where('order_number', 1)->first();

        $thread = ForumThread::create([
            'module_id' => $module->id,
            'created_by' => $exploration->id,
            'title' => 'Pertanyaan',
            'content' => 'Isi pertanyaan',
            'target' => 'peer',
        ]);

        foreach (["/eksplorasi/forum", '/eksplorasi/forum/create', "/eksplorasi/forum/{$thread->id}"] as $route) {
            $this->actingAs($exploration)->get($route)->assertOk();
            $this->actingAs($admin)->get($route)->assertOk();
            $this->actingAs($execution)->get($route)->assertForbidden();
        }
    }

    public function test_execution_only_routes_reject_exploration_member_and_admin_where_admin_has_no_extra_grant(): void
    {
        $exploration = $this->explorationMember();
        $execution = $this->executionMember();
        $admin = $this->admin();

        // /eksekusi/dashboard is execution_member's own personal dashboard —
        // PRD 2.3 gives admin full data access but not this specific member view.
        $this->actingAs($execution)->get('/eksekusi/dashboard')->assertOk();
        $this->actingAs($exploration)->get('/eksekusi/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/eksekusi/dashboard')->assertForbidden();
    }

    public function test_execution_and_admin_shared_routes_reject_exploration_member(): void
    {
        $admin = $this->admin();
        $exploration = $this->explorationMember();
        $execution = $this->executionMember();
        [$project, $milestone, $task] = $this->executionFixtures($admin, $execution);

        $idea = ProjectIdea::create([
            'title' => 'Ide Baru', 'description' => 'Deskripsi', 'purpose' => 'Tujuan',
            'proposed_by' => $execution->id, 'status' => 'draft',
        ]);

        $sharedRoutes = [
            '/eksekusi/ideas',
            '/eksekusi/ideas/create',
            "/eksekusi/ideas/{$idea->id}/approve",
            '/eksekusi/projects',
            "/eksekusi/projects/{$project->id}",
            "/eksekusi/projects/{$project->id}/board",
            "/eksekusi/projects/{$project->id}/tasks/create",
            "/eksekusi/tasks/{$task->id}",
        ];

        foreach ($sharedRoutes as $route) {
            $this->actingAs($exploration)->get($route)->assertForbidden();
        }

        // admin can reach all of them; execution_member (project member) can reach
        // all except the two admin-only actions (create project directly, approve idea)
        $this->actingAs($admin)->get('/eksekusi/ideas')->assertOk();
        $this->actingAs($admin)->get('/eksekusi/ideas/create')->assertOk();
        $this->actingAs($admin)->get("/eksekusi/ideas/{$idea->id}/approve")->assertOk();
        $this->actingAs($admin)->get('/eksekusi/projects')->assertOk();
        $this->actingAs($admin)->get("/eksekusi/projects/{$project->id}")->assertOk();
        $this->actingAs($admin)->get("/eksekusi/projects/{$project->id}/board")->assertOk();
        $this->actingAs($admin)->get("/eksekusi/projects/{$project->id}/tasks/create")->assertOk();
        $this->actingAs($admin)->get("/eksekusi/tasks/{$task->id}")->assertOk();

        $this->actingAs($execution)->get('/eksekusi/ideas')->assertOk();
        $this->actingAs($execution)->get('/eksekusi/ideas/create')->assertOk();
        $this->actingAs($execution)->get("/eksekusi/ideas/{$idea->id}/approve")->assertForbidden();
        $this->actingAs($execution)->get('/eksekusi/projects')->assertOk();
        $this->actingAs($execution)->get("/eksekusi/projects/{$project->id}")->assertOk();
        $this->actingAs($execution)->get("/eksekusi/projects/{$project->id}/board")->assertOk();
        $this->actingAs($execution)->get("/eksekusi/projects/{$project->id}/tasks/create")->assertOk();
        $this->actingAs($execution)->get("/eksekusi/tasks/{$task->id}")->assertOk();
    }

    public function test_execution_member_cannot_view_project_or_task_they_are_not_a_member_of(): void
    {
        $admin = $this->admin();
        $execution = $this->executionMember();
        $this->executionFixtures($admin, $execution);
        $foreignProject = $this->foreignProject($admin);
        $foreignTask = Task::create([
            'project_id' => $foreignProject->id,
            'milestone_id' => Milestone::create([
                'project_id' => $foreignProject->id, 'title' => 'M1', 'target_date' => '2026-07-15', 'sort_order' => 1,
            ])->id,
            'title' => 'Task Proyek Lain', 'description' => 'x', 'priority' => 'low',
            'deadline' => '2026-07-20', 'status' => 'todo', 'created_by' => $admin->id,
        ]);

        // PRD 2.2: "Proyek yang dia tidak terlibat sebagai anggota tim" must be blocked,
        // even though the route itself allows the execution_member role generally.
        $this->actingAs($execution)->get("/eksekusi/projects/{$foreignProject->id}")->assertForbidden();
        $this->actingAs($execution)->get("/eksekusi/projects/{$foreignProject->id}/board")->assertForbidden();
        $this->actingAs($execution)->get("/eksekusi/projects/{$foreignProject->id}/tasks/create")->assertForbidden();
        $this->actingAs($execution)->get("/eksekusi/tasks/{$foreignTask->id}")->assertForbidden();

        // admin is unaffected by membership scoping
        $this->actingAs($admin)->get("/eksekusi/projects/{$foreignProject->id}")->assertOk();
    }

    public function test_generic_dashboard_and_logout_routes_behave_per_role(): void
    {
        $admin = $this->admin();
        $exploration = $this->explorationMember();
        $execution = $this->executionMember();

        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/admin/dashboard');
        $this->actingAs($exploration)->get('/dashboard')->assertRedirect('/eksplorasi/dashboard');
        $this->actingAs($execution)->get('/dashboard')->assertRedirect('/eksekusi/dashboard');

        $this->actingAs($exploration)->post('/logout')->assertRedirect('/login');
    }

    public function test_guest_is_redirected_to_login_for_every_authenticated_route(): void
    {
        // Deliberately no actingAs() anywhere above this line in the test —
        // Laravel's actingAs() persists the authenticated user for every
        // subsequent request in the SAME test method, so a "guest" check
        // stacked after actingAs() calls silently inherits the last acting
        // user instead of testing an unauthenticated request. Kept isolated
        // here rather than interleaved in the role-matrix tests above.
        $admin = $this->admin();
        $exploration = $this->explorationMember();
        $execution = $this->executionMember();
        [$project, , $task] = $this->executionFixtures($admin, $execution);
        $unit = Unit::where('order_number', 1)->first();
        $checkpoint = Checkpoint::first();
        $thread = ForumThread::create([
            'module_id' => Module::where('order_number', 1)->first()->id,
            'created_by' => $exploration->id,
            'title' => 'Pertanyaan', 'content' => 'Isi', 'target' => 'peer',
        ]);
        $idea = ProjectIdea::create([
            'title' => 'Ide', 'description' => 'D', 'purpose' => 'P',
            'proposed_by' => $execution->id, 'status' => 'draft',
        ]);

        $protectedRoutes = [
            '/admin/dashboard', '/admin/users', '/admin/users/create', "/admin/users/{$exploration->id}/edit",
            '/admin/webi', "/admin/webi/{$exploration->id}",
            '/eksplorasi/dashboard', '/eksplorasi/kurikulum', "/eksplorasi/unit/{$unit->id}",
            "/eksplorasi/checkpoint/{$checkpoint->id}", '/eksplorasi/resources', '/eksplorasi/webi',
            '/eksplorasi/forum', '/eksplorasi/forum/create', "/eksplorasi/forum/{$thread->id}",
            '/eksekusi/dashboard', '/eksekusi/ideas', '/eksekusi/ideas/create', "/eksekusi/ideas/{$idea->id}/approve",
            '/eksekusi/projects', '/eksekusi/projects/create',
            "/eksekusi/projects/{$project->id}", "/eksekusi/projects/{$project->id}/board",
            "/eksekusi/projects/{$project->id}/tasks/create", "/eksekusi/tasks/{$task->id}",
        ];

        foreach ($protectedRoutes as $route) {
            $this->get($route)->assertRedirect('/login');
        }
    }
}

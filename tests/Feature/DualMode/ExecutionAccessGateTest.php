<?php

namespace Tests\Feature\DualMode;

use App\Livewire\Eksekusi\Ideas\Index as IdeasIndex;
use App\Models\Attachment;
use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectIdea;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fase 8 Batch 2 — the security-critical file for this batch.
 * User::canAccessExecution() + App\Http\Middleware\EnsureCanAccessMode now
 * guard every one of the 8 route/group declarations
 * RECON_fase8_mode_ganda.md poin 4 mapped (`role:execution_member[,admin]`
 * -> `mode:execution[,admin]`). This file hits EVERY one of those routes
 * (17 concrete URLs, not a sample — was 18 until the Project Idea/Project
 * independence revision on 2026-07-17 removed the standalone
 * `/eksekusi/ideas/{idea}/approve` route entirely; that admin-only action
 * is now covered via Livewire::test() below instead of a URL) under three
 * scenarios: no grant, grant without active mode, and grant WITH active mode.
 */
class ExecutionAccessGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('attachments');
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function explorationMember(string $email, array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Explorer', 'email' => $email,
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ], $overrides));
    }

    private function noGrantMember(string $email = 'no-grant@example.test'): User
    {
        return $this->explorationMember($email, ['dual_mode_status' => 'none']);
    }

    private function approvedButInactiveMember(string $email = 'approved-inactive@example.test'): User
    {
        // Approved, but active_mode is still null (never switched) -- must
        // NOT be treated as having execution access. "approved" alone is
        // not enough, the mode has to actually be switched ON.
        return $this->explorationMember($email, ['dual_mode_status' => 'approved', 'active_mode' => null]);
    }

    private function approvedButStillInExplorationMode(string $email = 'approved-still-exploring@example.test'): User
    {
        return $this->explorationMember($email, ['dual_mode_status' => 'approved', 'active_mode' => 'exploration']);
    }

    private function grantedActiveMember(string $email = 'granted-active@example.test'): User
    {
        return $this->explorationMember($email, ['dual_mode_status' => 'approved', 'active_mode' => 'execution']);
    }

    /**
     * @return array{0: Project, 1: Task, 2: ProjectIdea, 3: ChallengeSubmission, 4: Attachment}
     */
    private function fixtures(User $admin): array
    {
        $project = Project::create([
            'title' => 'Proyek Uji', 'description' => 'D', 'objective' => 'T',
            'project_type' => 'internal', 'status' => 'active',
            'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'M1', 'target_date' => '2026-07-20', 'sort_order' => 1]);
        $task = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Task Uji',
            'status' => 'todo', 'priority' => 'medium', 'deadline' => '2026-07-25', 'created_by' => $admin->id,
        ]);
        $idea = ProjectIdea::create([
            'title' => 'Ide Uji', 'description' => 'D', 'purpose' => 'P',
            'proposed_by' => $admin->id, 'status' => 'draft',
        ]);
        $challenge = Challenge::create([
            'title' => 'Challenge Uji', 'description' => 'D', 'level' => 'low',
            'points_reward' => 25, 'status' => 'published',
        ]);
        $submission = ChallengeSubmission::create([
            'challenge_id' => $challenge->id, 'user_id' => $admin->id,
            'submission_type' => 'text', 'content' => 'Isi submission', 'status' => 'pending',
        ]);

        Storage::disk('attachments')->put('attachments/uji.pdf', 'isi file uji');
        $attachment = Attachment::create([
            'task_id' => $task->id, 'uploaded_by' => $admin->id,
            'file_name' => 'uji.pdf', 'file_url' => 'attachments/uji.pdf',
            'file_type' => 'application/pdf', 'file_size' => 13,
        ]);

        return [$project, $task, $idea, $submission, $attachment];
    }

    /**
     * All 18 concrete URLs behind the 8 swapped middleware declarations.
     *
     * @return array<string, string>
     */
    private function allExecutionUrls(Project $project, Task $task, ProjectIdea $idea, ChallengeSubmission $submission, Attachment $attachment): array
    {
        return [
            'dashboard' => '/eksekusi/dashboard',
            'avatar' => '/eksekusi/avatar',
            'kalender_personal' => '/eksekusi/kalender',
            'ideas.index' => '/eksekusi/ideas',
            'ideas.create' => '/eksekusi/ideas/create',
            'projects.index' => '/eksekusi/projects',
            'projects.create' => '/eksekusi/projects/create',
            'projects.kanban' => "/eksekusi/projects/{$project->id}/kanban",
            'projects.roadmap' => "/eksekusi/projects/{$project->id}/roadmap",
            'projects.gantt' => "/eksekusi/projects/{$project->id}/gantt",
            'projects.kalender' => "/eksekusi/projects/{$project->id}/kalender",
            'projects.forum' => "/eksekusi/projects/{$project->id}/forum",
            'projects.anggota' => "/eksekusi/projects/{$project->id}/anggota",
            'projects.tasks.create' => "/eksekusi/projects/{$project->id}/tasks/create",
            'tasks.show_redirect' => "/eksekusi/tasks/{$task->id}",
            'praktik.submissions.show' => "/eksekusi/praktik/submissions/{$submission->id}",
            'attachments.download' => "/attachments/{$attachment->id}/download",
        ];
    }

    // ------------------------------------------------------------------
    // NEGATIF: tanpa grant sama sekali -- 403 di SEMUA 18 URL
    // ------------------------------------------------------------------

    public function test_an_exploration_member_with_no_dual_mode_grant_is_forbidden_from_every_single_execution_route(): void
    {
        $admin = $this->admin();
        [$project, $task, $idea, $submission, $attachment] = $this->fixtures($admin);
        $user = $this->noGrantMember();

        $urls = $this->allExecutionUrls($project, $task, $idea, $submission, $attachment);
        $this->assertCount(17, $urls, 'Sanity check: exactly the 17 URLs recon mapped must be covered.');

        foreach ($urls as $label => $url) {
            $this->actingAs($user)->get($url)->assertForbidden("Expected 403 for [{$label}] ({$url}) with no dual-mode grant.");
        }
    }

    // ------------------------------------------------------------------
    // NEGATIF: disetujui TAPI mode aktif belum dinyalakan -- 403 di SEMUA
    // ------------------------------------------------------------------

    public function test_an_approved_member_whose_active_mode_is_still_null_is_forbidden_from_every_single_execution_route(): void
    {
        $admin = $this->admin();
        [$project, $task, $idea, $submission, $attachment] = $this->fixtures($admin);
        $user = $this->approvedButInactiveMember();

        foreach ($this->allExecutionUrls($project, $task, $idea, $submission, $attachment) as $label => $url) {
            $this->actingAs($user)->get($url)->assertForbidden("Expected 403 for [{$label}] ({$url}) -- approved but active_mode is null, not 'execution'.");
        }
    }

    public function test_an_approved_member_still_actively_in_exploration_mode_is_forbidden_from_every_single_execution_route(): void
    {
        $admin = $this->admin();
        [$project, $task, $idea, $submission, $attachment] = $this->fixtures($admin);
        $user = $this->approvedButStillInExplorationMode();

        foreach ($this->allExecutionUrls($project, $task, $idea, $submission, $attachment) as $label => $url) {
            $this->actingAs($user)->get($url)->assertForbidden("Expected 403 for [{$label}] ({$url}) -- approved but active_mode is still 'exploration'.");
        }
    }

    // ------------------------------------------------------------------
    // POSITIF: disetujui DAN mode aktif = execution -- akses penuh
    // ------------------------------------------------------------------

    public function test_an_approved_member_with_active_mode_execution_can_reach_every_execution_route_that_a_native_execution_member_can(): void
    {
        $admin = $this->admin();
        [$project, $task, $idea, $submission, $attachment] = $this->fixtures($admin);
        $user = $this->grantedActiveMember();
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $user->id]);

        // Excludes 3 URLs with an ORTHOGONAL, unaffected restriction
        // (admin-only mount(), or reviewer/project-membership specific to
        // a DIFFERENT user) -- covered by their own dedicated tests below,
        // proving the Gate doesn't accidentally widen those separately.
        $urls = $this->allExecutionUrls($project, $task, $idea, $submission, $attachment);
        unset($urls['projects.create'], $urls['praktik.submissions.show'], $urls['attachments.download']);

        foreach ($urls as $label => $url) {
            $response = $this->actingAs($user)->get($url);
            $this->assertNotSame(403, $response->getStatusCode(), "Expected NOT forbidden for [{$label}] ({$url}) with an approved+active-mode grant.");
        }

        // Approving an idea is admin-only too, same as the other 3 excluded
        // above, but it's not a URL anymore since the Project Idea/Project
        // independence revision (2026-07-17) — checked directly against the
        // Index component's own role gate instead.
        Livewire::actingAs($user)->test(IdeasIndex::class)
            ->call('changeStatus', $idea->id, 'approved')
            ->assertForbidden();
    }

    public function test_admin_only_actions_still_reject_a_granted_active_member_exactly_like_a_native_execution_member(): void
    {
        $admin = $this->admin();
        [$project, , $idea] = $this->fixtures($admin);
        $user = $this->grantedActiveMember();

        // Both actions pass the new Gate (mode:execution,admin) fine, but
        // their OWN role check is abort_unless(role==='admin') -- untouched
        // by this batch, and correctly still excludes a granted member
        // (whose role is, and stays, 'exploration_member'). Idea approval
        // is a component method (changeStatus()) rather than a route since
        // the Project Idea/Project independence revision (2026-07-17).
        Livewire::actingAs($user)->test(IdeasIndex::class)
            ->call('changeStatus', $idea->id, 'approved')
            ->assertForbidden();
        $this->actingAs($user)->get('/eksekusi/projects/create')->assertForbidden();
    }

    public function test_a_granted_active_member_can_review_a_submission_once_actually_assigned_as_its_reviewer(): void
    {
        $admin = $this->admin();
        [, , , $submission] = $this->fixtures($admin);
        $user = $this->grantedActiveMember();

        // Not assigned yet -- still forbidden, same as any unassigned
        // execution_member would be (Praktik\Review::mount()'s own rule).
        $this->actingAs($user)->get("/eksekusi/praktik/submissions/{$submission->id}")->assertForbidden();

        $submission->update(['assigned_reviewer_id' => $user->id]);

        $this->actingAs($user)->get("/eksekusi/praktik/submissions/{$submission->id}")->assertOk();
    }

    public function test_a_granted_active_member_can_download_an_attachment_from_a_project_they_actually_belong_to(): void
    {
        $admin = $this->admin();
        [$project, , , , $attachment] = $this->fixtures($admin);
        $user = $this->grantedActiveMember();

        // Not a project member yet -- forbidden, same as any execution_member
        // outsider (AttachmentDownloadController's own membership check).
        $this->actingAs($user)->get("/attachments/{$attachment->id}/download")->assertForbidden();

        ProjectMember::create(['project_id' => $project->id, 'user_id' => $user->id]);

        $this->actingAs($user)->get("/attachments/{$attachment->id}/download")->assertOk();
    }

    // ------------------------------------------------------------------
    // Regresi: native execution_member dan admin tidak berubah
    // ------------------------------------------------------------------

    public function test_a_native_execution_member_is_unaffected_by_the_new_gate(): void
    {
        $admin = $this->admin();
        [$project, $task, $idea, $submission, $attachment] = $this->fixtures($admin);
        $member = User::create([
            'name' => 'Native', 'email' => 'native@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);
        ProjectMember::create(['project_id' => $project->id, 'user_id' => $member->id]);

        $urls = $this->allExecutionUrls($project, $task, $idea, $submission, $attachment);
        unset($urls['projects.create'], $urls['praktik.submissions.show'], $urls['attachments.download']);

        foreach ($urls as $label => $url) {
            $response = $this->actingAs($member)->get($url);
            $this->assertNotSame(403, $response->getStatusCode(), "Native execution_member unexpectedly forbidden from [{$label}] ({$url}).");
        }
    }

    public function test_admin_is_unaffected_by_the_new_gate(): void
    {
        $admin = $this->admin();
        [$project, $task, $idea, $submission, $attachment] = $this->fixtures($admin);

        $urls = $this->allExecutionUrls($project, $task, $idea, $submission, $attachment);
        // dashboard/avatar/kalender_personal use `mode:execution` WITHOUT
        // `,admin` -- admin was already excluded from these 3 BEFORE this
        // batch (`role:execution_member` alone, no `,admin` -- see
        // RECON_fase8_mode_ganda.md poin 1a), unaffected by this batch.
        unset($urls['dashboard'], $urls['avatar'], $urls['kalender_personal']);

        foreach ($urls as $label => $url) {
            $response = $this->actingAs($admin)->get($url);
            $this->assertNotSame(403, $response->getStatusCode(), "Admin unexpectedly forbidden from [{$label}] ({$url}).");
        }
    }

    // ------------------------------------------------------------------
    // requestDualModeAccess() (DualModeService, minimal member-facing flow
    // from Batch 2 — Batch 4 fleshed this out much further, see
    // tests/Feature/DualMode/DualModeRequestFlowTest.php for the full
    // submit/approve/reject/revoke/notification coverage; these 3 stay as
    // a lightweight Gate-adjacent regression check, updated for Batch 4's
    // requestAccess() -> submitRequest() rename, not deleted).
    // ------------------------------------------------------------------

    public function test_requesting_access_creates_a_pending_request_and_sets_the_users_status(): void
    {
        $user = $this->noGrantMember();

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Profile\Edit::class)
            ->call('requestDualModeAccess')
            ->assertHasNoErrors();

        $this->assertSame('pending', $user->fresh()->dual_mode_status);
        $this->assertSame(1, \App\Models\DualModeRequest::where('user_id', $user->id)->where('status', 'pending')->count());
    }

    public function test_requesting_access_twice_is_rejected_the_second_time(): void
    {
        $user = $this->noGrantMember();
        app(\App\Services\DualMode\DualModeService::class)->submitRequest($user);

        \Livewire\Livewire::actingAs($user->fresh())->test(\App\Livewire\Profile\Edit::class)
            ->call('requestDualModeAccess')
            ->assertHasErrors('dual_mode');

        $this->assertSame(1, \App\Models\DualModeRequest::where('user_id', $user->id)->count());
    }

    public function test_an_execution_member_cannot_request_dual_mode_access(): void
    {
        $user = User::create([
            'name' => 'Native', 'email' => 'native2@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);

        \Livewire\Livewire::actingAs($user)->test(\App\Livewire\Profile\Edit::class)
            ->call('requestDualModeAccess')
            ->assertHasErrors('dual_mode');

        $this->assertSame(0, \App\Models\DualModeRequest::count());
    }
}

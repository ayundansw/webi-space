<?php

namespace Tests\Feature\Execution;

use App\Models\Attachment;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Task 2.9 Part 1: the download route + access check, built and tested
 * before touching TaskService/the view (staged per the task instructions).
 * Access rule mirrors App\Livewire\Eksekusi\Tasks\Show::mount() exactly:
 * admin, or a member of the same project as the attachment's task.
 */
class AttachmentDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake both disks so these tests never touch real files on
        // storage/app/private or public/storage_files.
        Storage::fake('local');
        Storage::fake('storage_files');
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function executionMember(string $name): User
    {
        return User::create([
            'name' => $name, 'email' => strtolower($name).'@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);
    }

    private function explorationMember(): User
    {
        return User::create([
            'name' => 'Explorer', 'email' => 'explorer@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ]);
    }

    private function taskWithProject(User $admin): Task
    {
        $project = Project::create([
            'title' => 'Website Portfolio RIT', 'description' => 'D', 'objective' => 'T',
            'project_type' => 'internal', 'status' => 'active',
            'start_date' => '2026-07-01', 'target_end_date' => '2026-08-01', 'created_by' => $admin->id,
        ]);
        $milestone = Milestone::create(['project_id' => $project->id, 'title' => 'M1', 'target_date' => '2026-07-20', 'sort_order' => 1]);

        return Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone->id, 'title' => 'Buat halaman utama',
            'status' => 'in_progress', 'priority' => 'medium', 'deadline' => '2026-07-15', 'created_by' => $admin->id,
        ]);
    }

    private function fileAttachment(Task $task, User $uploader, string $relativePath = 'attachments/report.pdf'): Attachment
    {
        Storage::disk('local')->put($relativePath, 'isi file rahasia proyek');

        return Attachment::create([
            'task_id' => $task->id, 'uploaded_by' => $uploader->id,
            'file_name' => 'report.pdf', 'file_url' => $relativePath,
            'file_type' => 'application/pdf', 'file_size' => 22,
        ]);
    }

    public function test_project_member_can_download_an_attachment_from_their_own_task(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember('Ahmad');
        $task = $this->taskWithProject($admin);
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);
        $attachment = $this->fileAttachment($task, $member);

        $this->actingAs($member)->get("/attachments/{$attachment->id}/download")->assertOk();
    }

    public function test_admin_can_download_regardless_of_project_membership(): void
    {
        $admin = $this->admin();
        $task = $this->taskWithProject($admin);
        $member = $this->executionMember('Ahmad');
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);
        $attachment = $this->fileAttachment($task, $member);

        $this->actingAs($admin)->get("/attachments/{$attachment->id}/download")->assertOk();
    }

    public function test_execution_member_of_a_different_project_is_forbidden(): void
    {
        $admin = $this->admin();
        $task = $this->taskWithProject($admin);
        $owner = $this->executionMember('Ahmad');
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $owner->id]);
        $attachment = $this->fileAttachment($task, $owner);

        $outsider = $this->executionMember('Bilal');

        $this->actingAs($outsider)->get("/attachments/{$attachment->id}/download")->assertForbidden();
    }

    public function test_exploration_member_is_forbidden(): void
    {
        $admin = $this->admin();
        $task = $this->taskWithProject($admin);
        $owner = $this->executionMember('Ahmad');
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $owner->id]);
        $attachment = $this->fileAttachment($task, $owner);

        $this->actingAs($this->explorationMember())->get("/attachments/{$attachment->id}/download")->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $admin = $this->admin();
        $task = $this->taskWithProject($admin);
        $owner = $this->executionMember('Ahmad');
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $owner->id]);
        $attachment = $this->fileAttachment($task, $owner);

        $this->get("/attachments/{$attachment->id}/download")->assertRedirect('/login');
    }

    public function test_link_attachment_is_not_downloadable_via_this_route(): void
    {
        $admin = $this->admin();
        $task = $this->taskWithProject($admin);
        $member = $this->executionMember('Ahmad');
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);

        $link = Attachment::create([
            'task_id' => $task->id, 'uploaded_by' => $member->id,
            'file_name' => 'Link Desain', 'file_url' => 'https://figma.com/design',
            'file_type' => 'link', 'file_size' => null,
        ]);

        $this->actingAs($member)->get("/attachments/{$link->id}/download")->assertNotFound();
    }

    public function test_text_attachment_is_not_downloadable_via_this_route(): void
    {
        $admin = $this->admin();
        $task = $this->taskWithProject($admin);
        $member = $this->executionMember('Ahmad');
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);

        $note = Attachment::create([
            'task_id' => $task->id, 'uploaded_by' => $member->id,
            'file_name' => 'Catatan', 'file_url' => 'Ini catatan biasa, bukan file.',
            'file_type' => 'text', 'file_size' => null,
        ]);

        $this->actingAs($member)->get("/attachments/{$note->id}/download")->assertNotFound();
    }

    public function test_legacy_storage_files_url_attachment_still_downloads_via_fallback(): void
    {
        $admin = $this->admin();
        $task = $this->taskWithProject($admin);
        $member = $this->executionMember('Ahmad');
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);

        Storage::disk('storage_files')->put('attachments/old-upload.png', 'file lama dari sebelum 2.9');

        $legacy = Attachment::create([
            'task_id' => $task->id, 'uploaded_by' => $member->id,
            'file_name' => 'old-upload.png',
            'file_url' => 'http://localhost/storage_files/attachments/old-upload.png',
            'file_type' => 'image/png', 'file_size' => 27,
        ]);

        $this->actingAs($member)->get("/attachments/{$legacy->id}/download")->assertOk();
    }

    public function test_missing_underlying_file_returns_not_found_not_a_server_error(): void
    {
        $admin = $this->admin();
        $task = $this->taskWithProject($admin);
        $member = $this->executionMember('Ahmad');
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);

        $attachment = Attachment::create([
            'task_id' => $task->id, 'uploaded_by' => $member->id,
            'file_name' => 'ghost.pdf', 'file_url' => 'attachments/does-not-exist.pdf',
            'file_type' => 'application/pdf', 'file_size' => 10,
        ]);

        $this->actingAs($member)->get("/attachments/{$attachment->id}/download")->assertNotFound();
    }
}

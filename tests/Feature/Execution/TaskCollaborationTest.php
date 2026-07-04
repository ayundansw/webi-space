<?php

namespace Tests\Feature\Execution;

use App\Livewire\Eksekusi\Tasks\Show;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TaskCollaborationTest extends TestCase
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

    private function taskWithProject(User $admin, string $status = 'in_progress'): Task
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

        $milestone = Milestone::create([
            'project_id' => $project->id,
            'title' => 'Development',
            'target_date' => '2026-07-20',
            'sort_order' => 1,
        ]);

        return Task::create([
            'project_id' => $project->id,
            'milestone_id' => $milestone->id,
            'title' => 'Buat halaman utama',
            'status' => $status,
            'priority' => 'medium',
            'deadline' => '2026-07-15',
            'created_by' => $admin->id,
        ]);
    }

    public function test_project_member_can_add_comment_via_real_form(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember('Ahmad');
        $task = $this->taskWithProject($admin);
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);

        Livewire::actingAs($member)->test(Show::class, ['task' => $task])
            ->set('commentContent', 'Pakai layout flexbox untuk section hero, setuju?')
            ->call('addComment');

        $this->assertDatabaseHas('comments', [
            'task_id' => $task->id,
            'user_id' => $member->id,
            'content' => 'Pakai layout flexbox untuk section hero, setuju?',
        ]);
        $this->assertDatabaseHas('activity_logs', ['task_id' => $task->id, 'action_type' => 'comment_added']);
    }

    public function test_admin_comment_notifies_assignee_as_comment_from_admin_not_duplicated(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember('Ahmad');
        $task = $this->taskWithProject($admin);
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);
        TaskAssignment::create(['task_id' => $task->id, 'user_id' => $member->id, 'assigned_by' => $admin->id]);

        Livewire::actingAs($admin)->test(Show::class, ['task' => $task])
            ->set('commentContent', 'Footer-nya di mobile masih nabrak, coba pakai media query.')
            ->call('addComment');

        $this->assertDatabaseHas('notifications', ['recipient_id' => $member->id, 'type' => 'comment_from_admin']);
        $this->assertDatabaseMissing('notifications', ['recipient_id' => $member->id, 'type' => 'comment_on_my_task']);
        $this->assertSame(1, \App\Models\Notification::where('recipient_id', $member->id)->count());
    }

    public function test_member_comment_notifies_other_assignees_but_not_self(): void
    {
        $admin = $this->admin();
        $author = $this->executionMember('Ahmad');
        $other = $this->executionMember('Azmi');
        $task = $this->taskWithProject($admin);
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $author->id]);
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $other->id]);
        TaskAssignment::create(['task_id' => $task->id, 'user_id' => $author->id, 'assigned_by' => $admin->id]);
        TaskAssignment::create(['task_id' => $task->id, 'user_id' => $other->id, 'assigned_by' => $admin->id]);

        Livewire::actingAs($author)->test(Show::class, ['task' => $task])
            ->set('commentContent', 'Update progres ya')
            ->call('addComment');

        $this->assertDatabaseHas('notifications', ['recipient_id' => $other->id, 'type' => 'comment_on_my_task']);
        $this->assertDatabaseMissing('notifications', ['recipient_id' => $author->id, 'type' => 'comment_on_my_task']);
    }

    public function test_member_can_upload_file_attachment_via_real_form(): void
    {
        // Task 2.9: files now write to the private 'local' disk, never a
        // publicly-servable one — access is only ever through
        // AttachmentDownloadController (see AttachmentDownloadTest.php),
        // not a direct URL.
        Storage::fake('local');

        $admin = $this->admin();
        $member = $this->executionMember('Ahmad');
        $task = $this->taskWithProject($admin);
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);

        $file = UploadedFile::fake()->image('progres.png', 800, 600)->size(120);

        Livewire::actingAs($member)->test(Show::class, ['task' => $task])
            ->set('attachmentMode', 'file')
            ->set('attachmentFile', $file)
            ->call('addAttachment');

        $attachment = \App\Models\Attachment::where('task_id', $task->id)->first();
        $this->assertNotNull($attachment);
        $this->assertSame('progres.png', $attachment->file_name);
        $this->assertNotSame('link', $attachment->file_type);
        $this->assertNotNull($attachment->file_size);
        Storage::disk('local')->assertExists($attachment->file_url);

        // file_url is a relative disk path now, never a resolvable public URL.
        $this->assertStringNotContainsString('http', $attachment->file_url);
        $this->assertStringNotContainsString('/storage_files/', $attachment->file_url);
        $this->assertStringNotContainsString('/storage/', $attachment->file_url);

        $this->assertDatabaseHas('activity_logs', ['task_id' => $task->id, 'action_type' => 'attachment_added']);
    }

    public function test_uploaded_attachment_is_only_reachable_through_the_access_checked_download_route(): void
    {
        Storage::fake('local');

        $admin = $this->admin();
        $member = $this->executionMember('Ahmad');
        $task = $this->taskWithProject($admin);
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);

        Livewire::actingAs($member)->test(Show::class, ['task' => $task])
            ->set('attachmentMode', 'file')
            ->set('attachmentFile', UploadedFile::fake()->image('progres.png'))
            ->call('addAttachment');

        $attachment = \App\Models\Attachment::where('task_id', $task->id)->first();

        $rendered = Livewire::actingAs($member)->test(Show::class, ['task' => $task])->html();
        $this->assertStringContainsString('/attachments/'.$attachment->id.'/download', $rendered);

        $this->actingAs($member)->get('/attachments/'.$attachment->id.'/download')->assertOk();

        $outsider = $this->executionMember('Bilal');
        $this->actingAs($outsider)->get('/attachments/'.$attachment->id.'/download')->assertForbidden();
    }

    public function test_member_can_add_link_attachment_via_real_form(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember('Ahmad');
        $task = $this->taskWithProject($admin);
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);

        Livewire::actingAs($member)->test(Show::class, ['task' => $task])
            ->set('attachmentMode', 'link')
            ->set('attachmentLinkLabel', 'Link deploy preview')
            ->set('attachmentLinkUrl', 'https://preview.example.test')
            ->call('addAttachment');

        $attachment = \App\Models\Attachment::where('task_id', $task->id)->first();
        $this->assertNotNull($attachment);
        $this->assertSame('Link deploy preview', $attachment->file_name);
        $this->assertSame('https://preview.example.test', $attachment->file_url);
        $this->assertSame('link', $attachment->file_type);
        $this->assertNull($attachment->file_size);
    }

    public function test_link_attachment_requires_both_label_and_url(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember('Ahmad');
        $task = $this->taskWithProject($admin);
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);

        Livewire::actingAs($member)->test(Show::class, ['task' => $task])
            ->set('attachmentMode', 'link')
            ->set('attachmentLinkUrl', '')
            ->set('attachmentLinkLabel', '')
            ->call('addAttachment')
            ->assertHasErrors(['attachmentLinkUrl', 'attachmentLinkLabel']);

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_member_can_add_plain_text_attachment_via_real_form(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember('Ahmad');
        $task = $this->taskWithProject($admin);
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);

        $longNote = str_repeat('Catatan progres yang cukup panjang. ', 20); // > 255 chars
        $this->assertGreaterThan(255, strlen($longNote));

        Livewire::actingAs($member)->test(Show::class, ['task' => $task])
            ->set('attachmentMode', 'text')
            ->set('attachmentTextLabel', 'Catatan desain')
            ->set('attachmentTextContent', $longNote)
            ->call('addAttachment');

        $attachment = \App\Models\Attachment::where('task_id', $task->id)->first();
        $this->assertNotNull($attachment);
        $this->assertSame('Catatan desain', $attachment->file_name);
        $this->assertSame($longNote, $attachment->file_url);
        $this->assertSame('text', $attachment->file_type);
        $this->assertNull($attachment->file_size);
        $this->assertDatabaseHas('activity_logs', ['task_id' => $task->id, 'action_type' => 'attachment_added']);
    }

    public function test_text_attachment_requires_both_label_and_content(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember('Ahmad');
        $task = $this->taskWithProject($admin);
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);

        Livewire::actingAs($member)->test(Show::class, ['task' => $task])
            ->set('attachmentMode', 'text')
            ->set('attachmentTextLabel', '')
            ->set('attachmentTextContent', '')
            ->call('addAttachment')
            ->assertHasErrors(['attachmentTextLabel', 'attachmentTextContent']);

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_attachment_cannot_be_added_once_task_is_done(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember('Ahmad');
        $task = $this->taskWithProject($admin, 'done');
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);

        Livewire::actingAs($member)->test(Show::class, ['task' => $task])
            ->set('attachmentMode', 'link')
            ->set('attachmentLinkLabel', 'Late link')
            ->set('attachmentLinkUrl', 'https://late.example.test')
            ->call('addAttachment')
            ->assertForbidden();

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_member_can_send_progress_update_via_real_form_and_admin_is_notified(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember('Azmi');
        $task = $this->taskWithProject($admin);
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);

        Livewire::actingAs($member)->test(Show::class, ['task' => $task])
            ->set('progressContent', 'Tabel users dan projects sudah selesai. Tabel tasks masih progress.')
            ->set('progressAttachmentUrl', 'https://drive.example.test/file.sql')
            ->call('addProgressUpdate');

        $this->assertDatabaseHas('progress_updates', [
            'task_id' => $task->id,
            'user_id' => $member->id,
            'content' => 'Tabel users dan projects sudah selesai. Tabel tasks masih progress.',
            'attachment_url' => 'https://drive.example.test/file.sql',
        ]);
        $this->assertDatabaseHas('activity_logs', ['task_id' => $task->id, 'action_type' => 'progress_update_added']);
        $this->assertDatabaseHas('notifications', ['recipient_id' => $admin->id, 'type' => 'progress_update_received']);
    }

    public function test_progress_update_requires_content(): void
    {
        $admin = $this->admin();
        $member = $this->executionMember('Azmi');
        $task = $this->taskWithProject($admin);
        ProjectMember::create(['project_id' => $task->project_id, 'user_id' => $member->id]);

        Livewire::actingAs($member)->test(Show::class, ['task' => $task])
            ->set('progressContent', '')
            ->call('addProgressUpdate')
            ->assertHasErrors('progressContent');

        $this->assertDatabaseCount('progress_updates', 0);
    }
}

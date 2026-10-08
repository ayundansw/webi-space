<?php

namespace Tests\Feature\Admin\Curriculum;

use App\Livewire\Admin\Curriculum\Challenges\Edit;
use App\Models\Challenge;
use App\Models\ChallengeAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Praktik 3 Bagian B: admin-side reference attachments on a Challenge.
 */
class ChallengeAttachmentManagementTest extends TestCase
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

    private function challenge(): Challenge
    {
        return Challenge::create([
            'title' => 'Challenge Uji', 'description' => 'D', 'level' => 'low',
            'points_reward' => 50, 'status' => 'draft',
        ]);
    }

    public function test_admin_can_upload_multiple_attachments_at_once(): void
    {
        $admin = $this->admin();
        $challenge = $this->challenge();
        $fileA = UploadedFile::fake()->create('brief.pdf', 200, 'application/pdf');
        $fileB = UploadedFile::fake()->image('starter.png', 100, 100);

        Livewire::actingAs($admin)
            ->test(Edit::class, ['challenge' => $challenge])
            ->set('newAttachments', [$fileA, $fileB])
            ->call('uploadAttachments')
            ->assertHasNoErrors();

        $this->assertSame(2, ChallengeAttachment::where('challenge_id', $challenge->id)->count());
        $attachment = ChallengeAttachment::where('file_name', 'brief.pdf')->first();
        $this->assertSame($admin->id, $attachment->uploaded_by);
        Storage::disk('attachments')->assertExists($attachment->file_path);
    }

    public function test_disallowed_mime_type_is_rejected(): void
    {
        $admin = $this->admin();
        $challenge = $this->challenge();
        $file = UploadedFile::fake()->create('installer.exe', 100, 'application/x-msdownload');

        Livewire::actingAs($admin)
            ->test(Edit::class, ['challenge' => $challenge])
            ->set('newAttachments', [$file])
            ->call('uploadAttachments')
            ->assertHasErrors(['newAttachments.0']);

        $this->assertSame(0, ChallengeAttachment::count());
    }

    public function test_admin_can_delete_an_attachment_and_the_file_is_removed_from_disk(): void
    {
        $admin = $this->admin();
        $challenge = $this->challenge();
        Storage::disk('attachments')->put('challenge-attachments/brief.pdf', 'isi brief');
        $attachment = $challenge->attachments()->create([
            'file_name' => 'brief.pdf', 'file_path' => 'challenge-attachments/brief.pdf',
            'file_size' => 10, 'uploaded_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(Edit::class, ['challenge' => $challenge])
            ->call('deleteAttachment', $attachment->id);

        $this->assertSame(0, ChallengeAttachment::count());
        Storage::disk('attachments')->assertMissing('challenge-attachments/brief.pdf');
    }

    public function test_non_admin_cannot_reach_the_challenge_edit_page_at_all(): void
    {
        $challenge = $this->challenge();
        $exploration = User::create([
            'name' => 'Member', 'email' => 'member@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ]);

        $this->actingAs($exploration)
            ->get("/admin/curriculum/challenges/{$challenge->id}/edit")
            ->assertForbidden();
    }
}

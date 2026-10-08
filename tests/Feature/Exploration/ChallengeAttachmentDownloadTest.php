<?php

namespace Tests\Feature\Exploration;

use App\Models\Challenge;
use App\Models\ChallengeAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Praktik 3 Bagian B: member-facing side of Challenge attachments. Access is
 * deliberately loose — reference material, not private submission content
 * (contrast with tests/Feature/Exploration/ChallengeSubmissionFileUploadTest.php's
 * strict owner/reviewer/admin RBAC).
 */
class ChallengeAttachmentDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('attachments');
    }

    private function member(): User
    {
        return User::create([
            'name' => 'Member', 'email' => 'member@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function attachedChallenge(string $status): array
    {
        $admin = $this->admin();
        $challenge = Challenge::create([
            'title' => 'Challenge Uji', 'description' => 'D', 'level' => 'low',
            'points_reward' => 50, 'status' => $status,
        ]);
        Storage::disk('attachments')->put('challenge-attachments/brief.pdf', 'isi brief');
        $attachment = $challenge->attachments()->create([
            'file_name' => 'brief.pdf', 'file_path' => 'challenge-attachments/brief.pdf',
            'file_size' => 10, 'uploaded_by' => $admin->id,
        ]);

        return [$challenge, $attachment, $admin];
    }

    public function test_show_page_lists_attachments_for_a_published_challenge(): void
    {
        [$challenge] = $this->attachedChallenge('published');
        $member = $this->member();

        $this->actingAs($member)->get("/eksplorasi/praktik/{$challenge->id}")
            ->assertOk()
            ->assertSee('brief.pdf');
    }

    public function test_any_exploration_member_can_download_a_published_challenges_attachment(): void
    {
        [, $attachment] = $this->attachedChallenge('published');
        $member = $this->member();

        $this->actingAs($member)->get("/challenge-attachments/{$attachment->id}/download")->assertOk();
    }

    public function test_a_draft_challenges_attachment_is_forbidden_to_a_member(): void
    {
        [, $attachment] = $this->attachedChallenge('draft');
        $member = $this->member();

        $this->actingAs($member)->get("/challenge-attachments/{$attachment->id}/download")->assertForbidden();
    }

    public function test_admin_can_download_a_draft_challenges_attachment(): void
    {
        [, $attachment, $admin] = $this->attachedChallenge('draft');

        $this->actingAs($admin)->get("/challenge-attachments/{$attachment->id}/download")->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        [, $attachment] = $this->attachedChallenge('published');

        $this->get("/challenge-attachments/{$attachment->id}/download")->assertRedirect('/login');
    }

    public function test_missing_underlying_file_returns_not_found(): void
    {
        $admin = $this->admin();
        $challenge = Challenge::create([
            'title' => 'Challenge Uji', 'description' => 'D', 'level' => 'low',
            'points_reward' => 50, 'status' => 'published',
        ]);
        $attachment = $challenge->attachments()->create([
            'file_name' => 'ghost.pdf', 'file_path' => 'challenge-attachments/does-not-exist.pdf',
            'file_size' => 10, 'uploaded_by' => $admin->id,
        ]);

        $this->actingAs($this->member())->get("/challenge-attachments/{$attachment->id}/download")->assertNotFound();
    }

    public function test_deleting_a_challenge_cascades_to_its_attachments(): void
    {
        [$challenge, $attachment] = $this->attachedChallenge('published');

        $challenge->delete();

        $this->assertSame(0, ChallengeAttachment::where('id', $attachment->id)->count());
    }
}

<?php

namespace Tests\Feature\Exploration;

use App\Livewire\Eksplorasi\Praktik\Show;
use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Praktik 3 Bagian A: file upload for Challenge Submission. Same disk
 * ('attachments') and WithFileUploads trait as Task attachments (2.9), but
 * a separate download controller/route with different RBAC (owner OR
 * assigned reviewer OR admin — not project membership, which doesn't apply
 * here at all).
 */
class ChallengeSubmissionFileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('attachments');
    }

    private function member(string $name = 'Member'): User
    {
        return User::create([
            'name' => $name, 'email' => strtolower($name).'@example.test',
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

    private function executionMember(string $name = 'Executor'): User
    {
        return User::create([
            'name' => $name, 'email' => strtolower($name).'@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);
    }

    private function challenge(): Challenge
    {
        return Challenge::create([
            'title' => 'Challenge Uji', 'description' => 'D', 'level' => 'low',
            'points_reward' => 50, 'status' => 'published',
        ]);
    }

    public function test_member_can_submit_an_allowed_file_type(): void
    {
        $member = $this->member();
        $challenge = $this->challenge();
        $file = UploadedFile::fake()->create('demo.pdf', 500, 'application/pdf');

        Livewire::actingAs($member)
            ->test(Show::class, ['challenge' => $challenge])
            ->set('submissionType', 'file')
            ->set('submissionFile', $file)
            ->call('submit')
            ->assertHasNoErrors();

        $submission = ChallengeSubmission::first();
        $this->assertSame('file', $submission->submission_type);
        $this->assertSame('demo.pdf', $submission->file_name);
        $this->assertSame(500 * 1024, $submission->file_size);
        $this->assertNotNull($submission->file_path);
        Storage::disk('attachments')->assertExists($submission->file_path);
    }

    public function test_disallowed_mime_type_is_rejected(): void
    {
        $member = $this->member();
        $challenge = $this->challenge();
        $file = UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload');

        Livewire::actingAs($member)
            ->test(Show::class, ['challenge' => $challenge])
            ->set('submissionType', 'file')
            ->set('submissionFile', $file)
            ->call('submit')
            ->assertHasErrors(['submissionFile']);

        $this->assertSame(0, ChallengeSubmission::count());
    }

    public function test_file_larger_than_10mb_is_rejected(): void
    {
        $member = $this->member();
        $challenge = $this->challenge();
        $file = UploadedFile::fake()->create('big.zip', 10241, 'application/zip'); // 10241 KB > 10MB

        Livewire::actingAs($member)
            ->test(Show::class, ['challenge' => $challenge])
            ->set('submissionType', 'file')
            ->set('submissionFile', $file)
            ->call('submit')
            ->assertHasErrors(['submissionFile']);
    }

    public function test_each_allowed_extension_passes_validation(): void
    {
        $challenge = $this->challenge();

        foreach ([
            ['photo.jpg', 'image/jpeg'],
            ['photo.png', 'image/png'],
            ['photo.gif', 'image/gif'],
            ['photo.webp', 'image/webp'],
            ['doc.pdf', 'application/pdf'],
            ['clip.mp4', 'video/mp4'],
            ['clip.mov', 'video/quicktime'],
            ['clip.webm', 'video/webm'],
            ['archive.zip', 'application/zip'],
        ] as [$filename, $mime]) {
            $member = $this->member($filename);
            $file = UploadedFile::fake()->create($filename, 100, $mime);

            Livewire::actingAs($member)
                ->test(Show::class, ['challenge' => $challenge])
                ->set('submissionType', 'file')
                ->set('submissionFile', $file)
                ->call('submit')
                ->assertHasNoErrors();
        }

        $this->assertSame(9, ChallengeSubmission::count());
    }

    public function test_a_missing_file_is_required_when_type_is_file(): void
    {
        $member = $this->member();
        $challenge = $this->challenge();

        Livewire::actingAs($member)
            ->test(Show::class, ['challenge' => $challenge])
            ->set('submissionType', 'file')
            ->call('submit')
            ->assertHasErrors(['submissionFile']);
    }

    // --- Download RBAC ---

    private function fileSubmission(Challenge $challenge, User $owner, ?User $reviewer = null): ChallengeSubmission
    {
        $path = 'challenge-submissions/report.pdf';
        Storage::disk('attachments')->put($path, 'isi laporan rahasia');

        return ChallengeSubmission::create([
            'challenge_id' => $challenge->id, 'user_id' => $owner->id,
            'submission_type' => 'file', 'content' => 'report.pdf',
            'file_name' => 'report.pdf', 'file_path' => $path, 'file_size' => 20,
            'status' => 'pending', 'attempt_number' => 1,
            'assigned_reviewer_id' => $reviewer?->id,
        ]);
    }

    public function test_owner_can_download_their_own_submission_file(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member('Owner');
        $submission = $this->fileSubmission($challenge, $owner);

        $this->actingAs($owner)->get("/challenge-submissions/{$submission->id}/download")->assertOk();
    }

    public function test_assigned_reviewer_can_download(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member('Owner');
        $reviewer = $this->executionMember();
        $submission = $this->fileSubmission($challenge, $owner, $reviewer);

        $this->actingAs($reviewer)->get("/challenge-submissions/{$submission->id}/download")->assertOk();
    }

    public function test_admin_can_always_download(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member('Owner');
        $submission = $this->fileSubmission($challenge, $owner);

        $this->actingAs($this->admin())->get("/challenge-submissions/{$submission->id}/download")->assertOk();
    }

    public function test_a_different_member_cannot_download(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member('Owner');
        $submission = $this->fileSubmission($challenge, $owner);
        $outsider = $this->member('Outsider');

        $this->actingAs($outsider)->get("/challenge-submissions/{$submission->id}/download")->assertForbidden();
    }

    public function test_execution_member_not_assigned_as_reviewer_cannot_download(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member('Owner');
        $assignedReviewer = $this->executionMember('Assigned');
        $submission = $this->fileSubmission($challenge, $owner, $assignedReviewer);
        $otherExecution = $this->executionMember('NotAssigned');

        $this->actingAs($otherExecution)->get("/challenge-submissions/{$submission->id}/download")->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member('Owner');
        $submission = $this->fileSubmission($challenge, $owner);

        $this->get("/challenge-submissions/{$submission->id}/download")->assertRedirect('/login');
    }

    public function test_link_or_text_submission_is_not_downloadable_via_this_route(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member('Owner');
        $submission = ChallengeSubmission::create([
            'challenge_id' => $challenge->id, 'user_id' => $owner->id,
            'submission_type' => 'link', 'content' => 'https://example.test',
            'status' => 'pending', 'attempt_number' => 1,
        ]);

        $this->actingAs($owner)->get("/challenge-submissions/{$submission->id}/download")->assertNotFound();
    }

    public function test_missing_underlying_file_returns_not_found(): void
    {
        $challenge = $this->challenge();
        $owner = $this->member('Owner');
        $submission = ChallengeSubmission::create([
            'challenge_id' => $challenge->id, 'user_id' => $owner->id,
            'submission_type' => 'file', 'content' => 'ghost.pdf',
            'file_name' => 'ghost.pdf', 'file_path' => 'challenge-submissions/does-not-exist.pdf', 'file_size' => 10,
            'status' => 'pending', 'attempt_number' => 1,
        ]);

        $this->actingAs($owner)->get("/challenge-submissions/{$submission->id}/download")->assertNotFound();
    }
}

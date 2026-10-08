<?php

namespace Tests\Feature\Exploration;

use App\Livewire\Eksplorasi\Praktik\Index;
use App\Livewire\Eksplorasi\Praktik\Show;
use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use App\Models\Notification;
use App\Models\User;
use App\Services\Exploration\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PraktikTest extends TestCase
{
    use RefreshDatabase;

    private function member(): User
    {
        return User::create([
            'name' => 'Member', 'email' => 'member@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'exploration_member', 'membership_status' => 'active',
        ]);
    }

    private function admin(string $email = 'admin@example.test'): User
    {
        return User::create([
            'name' => 'Admin', 'email' => $email,
            'password_hash' => bcrypt('secret123'), 'role' => 'admin', 'membership_status' => 'active',
        ]);
    }

    private function executionMember(): User
    {
        return User::create([
            'name' => 'Executor', 'email' => 'executor@example.test',
            'password_hash' => bcrypt('secret123'), 'role' => 'execution_member', 'membership_status' => 'active',
        ]);
    }

    private function challenge(array $overrides = []): Challenge
    {
        return Challenge::create(array_merge([
            'title' => 'Bikin Landing Page Statis', 'description' => 'Deskripsi.', 'level' => 'low',
            'points_reward' => 50, 'status' => 'published',
        ], $overrides));
    }

    public function test_non_exploration_member_cannot_access_praktik_index_or_show(): void
    {
        $challenge = $this->challenge();
        $admin = $this->admin();
        $execution = $this->executionMember();

        foreach ([$admin, $execution] as $user) {
            $this->actingAs($user)->get('/eksplorasi/praktik')->assertForbidden();
            $this->actingAs($user)->get("/eksplorasi/praktik/{$challenge->id}")->assertForbidden();
        }
    }

    public function test_index_shows_only_published_challenges(): void
    {
        $member = $this->member();
        $published = $this->challenge(['title' => 'Challenge Published', 'status' => 'published']);
        $draft = $this->challenge(['title' => 'Challenge Draft', 'status' => 'draft']);

        $this->actingAs($member)->get('/eksplorasi/praktik')
            ->assertOk()
            ->assertSee('Challenge Published')
            ->assertDontSee('Challenge Draft');
    }

    public function test_opening_a_draft_challenge_directly_is_404(): void
    {
        $member = $this->member();
        $draft = $this->challenge(['status' => 'draft']);

        $this->actingAs($member)->get("/eksplorasi/praktik/{$draft->id}")->assertNotFound();
    }

    public function test_index_filters_by_level(): void
    {
        $member = $this->member();
        $this->challenge(['title' => 'Challenge Low', 'level' => 'low']);
        $this->challenge(['title' => 'Challenge High', 'level' => 'high']);

        Livewire::actingAs($member)
            ->test(Index::class)
            ->set('levelFilter', 'high')
            ->assertSee('Challenge High')
            ->assertDontSee('Challenge Low');
    }

    public function test_show_renders_the_track_map_content_blocks(): void
    {
        $member = $this->member();
        $challenge = $this->challenge();
        $step = $challenge->challengeSteps()->create(['title' => 'Langkah 1', 'order_number' => 1]);
        $step->contentBlocks()->create(['type' => 'heading', 'content' => ['level' => 2, 'text' => 'Judul Langkah 1'], 'order' => 1]);
        $step->contentBlocks()->create(['type' => 'text', 'content' => ['markdown' => 'Instruksi langkah pertama.'], 'order' => 2]);

        $this->actingAs($member)->get("/eksplorasi/praktik/{$challenge->id}")
            ->assertOk()
            ->assertSee('Judul Langkah 1')
            ->assertSee('Instruksi langkah pertama.');
    }

    public function test_member_can_submit_a_link_and_it_is_saved_as_pending_with_attempt_number_one(): void
    {
        $member = $this->member();
        $challenge = $this->challenge();

        Livewire::actingAs($member)
            ->test(Show::class, ['challenge' => $challenge])
            ->set('submissionType', 'link')
            ->set('content', 'https://github.com/example/demo')
            ->call('submit')
            ->assertHasNoErrors();

        $submission = ChallengeSubmission::first();
        $this->assertSame($challenge->id, $submission->challenge_id);
        $this->assertSame($member->id, $submission->user_id);
        $this->assertSame('link', $submission->submission_type);
        $this->assertSame('https://github.com/example/demo', $submission->content);
        $this->assertSame('pending', $submission->status);
        $this->assertSame(1, $submission->attempt_number);
        $this->assertNull($submission->points_awarded);
        $this->assertNull($submission->assigned_reviewer_id);
    }

    public function test_resubmitting_increments_attempt_number(): void
    {
        $member = $this->member();
        $challenge = $this->challenge();

        $component = Livewire::actingAs($member)->test(Show::class, ['challenge' => $challenge]);
        $component->set('submissionType', 'text')->set('content', 'Percobaan pertama.')->call('submit');
        $component->set('submissionType', 'text')->set('content', 'Percobaan kedua, sudah diperbaiki.')->call('submit');

        $submissions = ChallengeSubmission::orderBy('attempt_number')->get();
        $this->assertCount(2, $submissions);
        $this->assertSame(1, $submissions[0]->attempt_number);
        $this->assertSame(2, $submissions[1]->attempt_number);
        $this->assertSame('Percobaan kedua, sudah diperbaiki.', $submissions[1]->content);
    }

    public function test_attempt_number_is_scoped_per_challenge_not_global(): void
    {
        $member = $this->member();
        $challengeA = $this->challenge(['title' => 'Challenge A']);
        $challengeB = $this->challenge(['title' => 'Challenge B']);

        Livewire::actingAs($member)->test(Show::class, ['challenge' => $challengeA])
            ->set('submissionType', 'text')->set('content', 'Jawaban A')->call('submit');
        Livewire::actingAs($member)->test(Show::class, ['challenge' => $challengeB])
            ->set('submissionType', 'text')->set('content', 'Jawaban B')->call('submit');

        $this->assertSame(1, ChallengeSubmission::where('challenge_id', $challengeA->id)->first()->attempt_number);
        $this->assertSame(1, ChallengeSubmission::where('challenge_id', $challengeB->id)->first()->attempt_number);
    }

    public function test_submitting_does_not_award_any_points(): void
    {
        $member = $this->member();
        $challenge = $this->challenge(['points_reward' => 100]);
        $progress = app(ProgressService::class);
        $before = $progress->ensureProgress($member)->total_points;

        Livewire::actingAs($member)
            ->test(Show::class, ['challenge' => $challenge])
            ->set('submissionType', 'link')
            ->set('content', 'https://example.test/demo')
            ->call('submit');

        $this->assertSame($before, $progress->ensureProgress($member)->total_points);
    }

    public function test_link_submission_requires_a_valid_url(): void
    {
        $member = $this->member();
        $challenge = $this->challenge();

        Livewire::actingAs($member)
            ->test(Show::class, ['challenge' => $challenge])
            ->set('submissionType', 'link')
            ->set('content', 'bukan-url')
            ->call('submit')
            ->assertHasErrors(['content']);

        $this->assertSame(0, ChallengeSubmission::count());
    }

    public function test_text_submission_requires_non_empty_content(): void
    {
        $member = $this->member();
        $challenge = $this->challenge();

        Livewire::actingAs($member)
            ->test(Show::class, ['challenge' => $challenge])
            ->set('submissionType', 'text')
            ->set('content', '')
            ->call('submit')
            ->assertHasErrors(['content']);
    }

    public function test_submitting_notifies_every_admin_but_no_one_else(): void
    {
        $member = $this->member();
        $admin1 = $this->admin('admin1@example.test');
        $admin2 = $this->admin('admin2@example.test');
        $execution = $this->executionMember();
        $challenge = $this->challenge();

        Livewire::actingAs($member)
            ->test(Show::class, ['challenge' => $challenge])
            ->set('submissionType', 'link')
            ->set('content', 'https://example.test/demo')
            ->call('submit');

        $notifications = Notification::where('type', 'submission_received_alert')->get();
        $this->assertCount(2, $notifications);
        $this->assertEqualsCanonicalizing(
            [$admin1->id, $admin2->id],
            $notifications->pluck('recipient_id')->all(),
        );

        $submission = ChallengeSubmission::first();
        foreach ($notifications as $notification) {
            $this->assertSame('challenge_submission', $notification->context_type);
            $this->assertSame($submission->id, $notification->context_id);
        }

        $this->assertSame(0, Notification::where('recipient_id', $execution->id)->count());
        $this->assertSame(0, Notification::where('recipient_id', $member->id)->count());
    }

    public function test_submission_history_shows_previous_attempts(): void
    {
        $member = $this->member();
        $challenge = $this->challenge();
        ChallengeSubmission::create([
            'challenge_id' => $challenge->id, 'user_id' => $member->id, 'submission_type' => 'text',
            'content' => 'Jawaban lama.', 'status' => 'perlu_revisi', 'feedback' => 'Perbaiki bagian ini.',
            'attempt_number' => 1,
        ]);

        $this->actingAs($member)->get("/eksplorasi/praktik/{$challenge->id}")
            ->assertOk()
            ->assertSee('Percobaan ke-1')
            ->assertSee('Perlu Revisi')
            ->assertSee('Perbaiki bagian ini.');
    }
}

<?php

namespace App\Livewire\Eksplorasi\Praktik;

use App\Models\Challenge;
use App\Models\ChallengeSubmission;
use App\Models\User;
use App\Services\Exploration\Notifier;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * File-upload submission (`submissionType = 'file'`) uses Livewire's
 * WithFileUploads, writing to the private `attachments` disk (never the
 * shared `local` disk Livewire defaults to — see that disk's comment for
 * why). NOT the Task `Attachment` model — different RBAC domain (reviewer
 * assignment vs project membership); file_* columns live directly on
 * `challenge_submissions`.
 *
 * MIME whitelist is stricter than Task attachments (which have none) —
 * this surface is open to any exploration_member, not just project members.
 */
#[Layout('components.layouts.app')]
#[Title('Detail Praktik')]
class Show extends Component
{
    use WithFileUploads;

    public Challenge $challenge;

    public string $submissionType = 'link';

    public string $content = '';

    public mixed $submissionFile = null;

    public function mount(Challenge $challenge): void
    {
        // A draft challenge must be exactly as invisible to a member who
        // guesses/bookmarks its URL as it is from the Index listing.
        abort_if($challenge->status !== 'published', 404);

        $this->challenge = $challenge;
    }

    public function submit(Notifier $notifier): void
    {
        $this->validate([
            'submissionType' => ['required', 'in:link,text,file'],
        ]);

        $data = match ($this->submissionType) {
            'link' => $this->collectLinkData(),
            'text' => $this->collectTextData(),
            'file' => $this->collectFileData(),
        };

        $user = Auth::user();

        $attemptNumber = ChallengeSubmission::where('challenge_id', $this->challenge->id)
            ->where('user_id', $user->id)
            ->count() + 1;

        $submission = ChallengeSubmission::create(array_merge($data, [
            'challenge_id' => $this->challenge->id,
            'user_id' => $user->id,
            'submission_type' => $this->submissionType,
            'status' => 'pending',
            'attempt_number' => $attemptNumber,
        ]));

        // No specific reviewer yet (that's an admin action, task-separate
        // from this one) — every admin gets the alert, same broadcast
        // pattern as Execution\AlertService::notifyNewAlerts().
        foreach (User::where('role', 'admin')->get() as $admin) {
            $notifier->send(
                $admin,
                'submission_received_alert',
                'Submission Praktik baru',
                "{$user->name} mengirim percobaan ke-{$attemptNumber} untuk challenge \"{$this->challenge->title}\".",
                $submission,
            );
        }

        $this->reset(['content', 'submissionFile']);

        session()->flash('status', 'Submission berhasil dikirim, menunggu direview admin.');
    }

    private function collectLinkData(): array
    {
        $validated = $this->validate(['content' => ['required', 'url', 'max:2000']]);

        return ['content' => $validated['content']];
    }

    private function collectTextData(): array
    {
        $validated = $this->validate(['content' => ['required', 'string', 'max:5000']]);

        return ['content' => $validated['content']];
    }

    private function collectFileData(): array
    {
        $this->validate([
            'submissionFile' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,webp,pdf,mp4,mov,webm,zip',
            ],
        ]);

        $path = $this->submissionFile->store('challenge-submissions', 'attachments');

        return [
            // `content` is NOT NULL at the DB level (link/text's real data
            // column) — the original filename doubles as a human-readable
            // value there for file submissions, consistent with content
            // always being "what the reviewer should look at first".
            'content' => $this->submissionFile->getClientOriginalName(),
            'file_name' => $this->submissionFile->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => $this->submissionFile->getSize(),
        ];
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.eksplorasi.praktik.show', [
            'steps' => $this->challenge->challengeSteps,
            'attachments' => $this->challenge->attachments,
            'submissions' => ChallengeSubmission::where('challenge_id', $this->challenge->id)
                ->where('user_id', $user->id)
                ->orderByDesc('attempt_number')
                ->get(),
        ]);
    }
}

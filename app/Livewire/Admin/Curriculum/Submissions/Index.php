<?php

namespace App\Livewire\Admin\Curriculum\Submissions;

use App\Models\ChallengeSubmission;
use App\Models\User;
use App\Services\Exploration\Notifier;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Admin queue for Praktik submissions: assign to a reviewer, or review
 * directly (PIC self-review). The review UI itself is
 * App\Livewire\Eksekusi\Praktik\Review, shared with assigned reviewers —
 * admin reaches the same page, always passing its RBAC check.
 *
 * Reviewer pool uses User::canAccessExecution() (in-memory filter, same
 * source of truth as the access Gate) rather than a hand-rolled role
 * query — so Mode Ganda members with active execution access always show
 * up as assignable, and this list can never drift from the Gate itself.
 */
#[Layout('components.layouts.app')]
#[Title('Antrian Review Praktik')]
class Index extends Component
{
    /** @var array<string,string> submission id => selected reviewer id */
    public array $reviewerSelections = [];

    public function assignReviewer(string $submissionId, Notifier $notifier): void
    {
        $reviewerId = $this->reviewerSelections[$submissionId] ?? null;

        $this->validate([
            'reviewerSelections.'.$submissionId => ['required', 'uuid'],
        ]);

        $submission = ChallengeSubmission::findOrFail($submissionId);
        $reviewer = User::findOrFail($reviewerId);
        abort_unless($reviewer->canAccessExecution(), 403);

        $submission->update(['assigned_reviewer_id' => $reviewer->id]);

        $notifier->send(
            $reviewer,
            'submission_assigned_to_reviewer',
            'Submission Praktik ditugaskan ke kamu',
            "Kamu ditugaskan mereview submission {$submission->user->name} untuk challenge \"{$submission->challenge->title}\".",
            $submission,
        );

        session()->flash('status', 'Reviewer berhasil ditugaskan.');
    }

    public function render()
    {
        return view('livewire.admin.curriculum.submissions.index', [
            'submissions' => ChallengeSubmission::with(['challenge', 'user', 'assignedReviewer'])
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->get(),
            'reviewers' => User::orderBy('name')->get()->filter->canAccessExecution()->values(),
        ]);
    }
}

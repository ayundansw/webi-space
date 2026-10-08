<?php

namespace App\Livewire\Eksekusi\Praktik;

use App\Models\ChallengeSubmission;
use App\Services\Exploration\Notifier;
use App\Services\Exploration\PointService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Praktik 3 Bagian C. Shared by both an assigned execution_member reviewer
 * AND admin (PIC self-review, docs/v_2.0/archive/sumber-konsolidasi/Rancangan_Modul_Praktik_v2.md §5)
 * — same page, RBAC just always passes for admin. Points are only ever
 * awarded here, on approve, via PointService — never at submit time
 * (Praktik 2).
 *
 * RBAC mirrors the pattern from AttachmentDownloadController/
 * ChallengeSubmissionDownloadController: explicit abort_if in mount(), not
 * a role-only middleware check — a reviewer must match THIS submission's
 * assigned_reviewer_id exactly, not just "be an execution_member".
 */
#[Layout('components.layouts.app')]
#[Title('Review Submission Praktik')]
class Review extends Component
{
    public ChallengeSubmission $submission;

    public string $feedback = '';

    public function mount(ChallengeSubmission $submission): void
    {
        $user = Auth::user();

        abort_if(
            $user->role !== 'admin' && $submission->assigned_reviewer_id !== $user->id,
            403,
        );

        $this->submission = $submission;
    }

    public function approve(PointService $pointService, Notifier $notifier): void
    {
        // Defense in depth: the blade never renders this action once
        // reviewed_at is set, but a direct wire:call (or a second tab still
        // open on the form) must not be able to double-award points.
        abort_if($this->submission->reviewed_at !== null, 403);

        $validated = $this->validate(['feedback' => ['required', 'string', 'max:5000']]);

        // Diminishing return, fixed 70% per attempt (docs §5) — attempt 1
        // resolves to the full points_reward automatically (0.7^0 = 1), no
        // separate "first attempt" branch needed. The tiny epsilon guards
        // against float imprecision (0.7 has no exact binary
        // representation — 0.7**2 evaluates to 0.48999999999999994, which
        // would floor() a "should be 49" result down to 48 without it).
        $points = (int) floor(
            $this->submission->challenge->points_reward * (0.7 ** ($this->submission->attempt_number - 1)) + 1e-9
        );

        $this->submission->update([
            'status' => 'disetujui',
            'feedback' => $validated['feedback'],
            'points_awarded' => $points,
            'reviewed_at' => now(),
        ]);

        $pointService->award($this->submission->user, $points);

        $notifier->send(
            $this->submission->user,
            'submission_approved',
            'Submission Praktik disetujui!',
            "Submission kamu untuk \"{$this->submission->challenge->title}\" disetujui, kamu dapat {$points} poin!",
            $this->submission,
        );

        $this->submission->refresh();
        session()->flash('status', 'Submission disetujui.');
    }

    public function requestRevision(Notifier $notifier): void
    {
        abort_if($this->submission->reviewed_at !== null, 403);

        $validated = $this->validate(['feedback' => ['required', 'string', 'max:5000']]);

        $this->submission->update([
            'status' => 'perlu_revisi',
            'feedback' => $validated['feedback'],
            'reviewed_at' => now(),
        ]);

        $notifier->send(
            $this->submission->user,
            'submission_needs_revision',
            'Submission Praktik perlu direvisi',
            "Submission kamu untuk \"{$this->submission->challenge->title}\" perlu direvisi. Feedback: {$validated['feedback']}",
            $this->submission,
        );

        $this->submission->refresh();
        session()->flash('status', 'Feedback revisi terkirim.');
    }

    public function render()
    {
        return view('livewire.eksekusi.praktik.review', [
            'steps' => $this->submission->challenge->challengeSteps,
        ]);
    }
}

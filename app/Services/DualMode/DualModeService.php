<?php

namespace App\Services\DualMode;

use App\Models\DualModeRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * The whole request/approval/revoke state machine, centralized here — no
 * status-mutation logic lives in a Livewire component (same principle as
 * ProjectIdeaService/TaskService). Modeled as a single pending row with
 * admin deciding directly, no delegation step.
 *
 * Notifications are sent directly via Notification::create() rather than
 * through a portal Notifier — this is cross-cutting, belonging to neither
 * portal. context_type stays 'none' since no admin-facing detail page
 * exists yet to deep-link to.
 */
class DualModeService
{
    public function submitRequest(User $user): DualModeRequest
    {
        if ($user->role !== 'exploration_member') {
            throw ValidationException::withMessages([
                'dual_mode' => 'Cuma anggota Eksplorasi yang bisa mengajukan akses Eksekusi.',
            ]);
        }

        if ($user->dual_mode_status !== 'none') {
            throw ValidationException::withMessages([
                'dual_mode' => 'Kamu sudah pernah mengajukan atau sudah punya akses, tidak bisa mengajukan lagi.',
            ]);
        }

        // 'status' set explicitly (not left to the DB default) so the
        // returned instance's in-memory `status` is immediately 'pending'
        // — without this, Eloquent doesn't know the column's DB default
        // and leaves the in-memory attribute null, which guardUnreviewed()
        // in approve()/reject() would then wrongly read as "already
        // reviewed" if a caller acts on this exact return value.
        $request = DualModeRequest::create(['user_id' => $user->id, 'status' => 'pending']);

        $user->update(['dual_mode_status' => 'pending']);

        foreach (User::where('role', 'admin')->get() as $admin) {
            $this->notify(
                $admin,
                'dual_mode_request_alert',
                'Permintaan akses Eksekusi baru',
                "{$user->name} mengajukan akses Mode Eksekusi.",
            );
        }

        return $request;
    }

    /**
     * Approve grants `dual_mode_status = approved` ONLY — deliberately
     * does NOT touch `active_mode` (stays whatever it already was, null
     * for a first-time request). The member still has to actively switch
     * into Mode Eksekusi themselves (Batch 6's UI) — approval is
     * "permission granted", not "mode switched on their behalf".
     */
    public function approve(DualModeRequest $request, User $reviewer): DualModeRequest
    {
        $this->guardUnreviewed($request);

        $request->update([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $request->user->update(['dual_mode_status' => 'approved']);

        $this->notify(
            $request->user,
            'dual_mode_approved',
            'Akses Eksekusi disetujui',
            'Permintaan akses Mode Eksekusi kamu disetujui admin. Ganti mode lewat menu Akun begitu tersedia.',
        );

        return $request->fresh();
    }

    /**
     * `dual_mode_status` reverts to `none`, NOT a permanent "rejected"
     * state — see this batch's report for the full reasoning (short
     * version: `users.dual_mode_status`'s enum, fixed since Fase 8 Batch 1,
     * literally has no `rejected` value to set it to — `none` is the only
     * representable outcome, and it also happens to be the more
     * member-friendly one: a rejected member can submit a fresh request
     * later rather than being permanently locked out). The REJECTION
     * ITSELF is never lost — it stays on this specific DualModeRequest row
     * (`status = rejected`, `note`) as permanent history, only the user's
     * CURRENT effective status resets.
     */
    public function reject(DualModeRequest $request, User $reviewer, ?string $note = null): DualModeRequest
    {
        $this->guardUnreviewed($request);

        $request->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'note' => $note,
        ]);

        $request->user->update(['dual_mode_status' => 'none']);

        $this->notify(
            $request->user,
            'dual_mode_rejected',
            'Akses Eksekusi ditolak',
            $note
                ? "Permintaan akses Mode Eksekusi kamu ditolak admin. Alasan: {$note}"
                : 'Permintaan akses Mode Eksekusi kamu ditolak admin.',
        );

        return $request->fresh();
    }

    /**
     * Revokes an already-approved grant. Records the revocation as a new
     * DualModeRequest row (`status = 'revoked'`), leaving the original
     * 'approved' row untouched as permanent history. `dual_mode_status`
     * resets to `none` (not a dedicated 'revoked' state) so the member can
     * request access again; `active_mode` always resets to null.
     */
    public function revoke(User $user, User $reviewer): User
    {
        if ($user->dual_mode_status !== 'approved') {
            throw ValidationException::withMessages([
                'dual_mode' => 'Cuma akses yang sedang disetujui yang bisa dicabut.',
            ]);
        }

        $user->update(['dual_mode_status' => 'none', 'active_mode' => null]);

        DualModeRequest::create([
            'user_id' => $user->id,
            'status' => 'revoked',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $this->notify(
            $user,
            'dual_mode_revoked',
            'Akses Eksekusi dicabut',
            'Akses Mode Eksekusi kamu dicabut admin.',
        );

        return $user->fresh();
    }

    private function guardUnreviewed(DualModeRequest $request): void
    {
        if ($request->status !== 'pending' || $request->reviewed_at !== null) {
            throw ValidationException::withMessages([
                'dual_mode' => 'Permintaan ini sudah pernah diputuskan sebelumnya.',
            ]);
        }
    }

    private function notify(User $recipient, string $type, string $title, string $message): void
    {
        Notification::create([
            'recipient_id' => $recipient->id,
            'context_type' => 'none',
            'context_id' => null,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
        ]);
    }
}

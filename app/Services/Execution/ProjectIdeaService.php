<?php

namespace App\Services\Execution;

use App\Models\ProjectIdea;
use App\Models\User;

/**
 * Project Idea + Project independence (revisi, 2026-07-17, dikonfirmasi Aye):
 * approve() SEKARANG HANYA mengubah status ProjectIdea, TIDAK PERNAH membuat
 * Project. Sebelumnya ada auto-create Project di sini (ditandai eksplisit di
 * docblock lama sebagai "keputusan yang belum dikonfirmasi Aye" sejak awal) —
 * sekarang dipisah total: kalau sebuah ide mau direalisasikan, admin bikin
 * Project baru secara manual dan terpisah lewat "Buat Proyek Langsung"
 * (`Eksekusi\Projects\Create` / `ProjectService::createDirect()`), tanpa
 * keterkaitan otomatis ke ide asalnya. `promoted_to_project_id` TETAP ada di
 * skema (tidak dihapus, dormant untuk kemungkinan pemakaian manual lain nanti)
 * tapi tidak pernah lagi diisi lewat method ini.
 *
 * ActivityLog.project_id tetap required (non-nullable) FK ke projects —
 * confirmed final oleh user (2026-07-03), tidak diubah. `idea_created` dan
 * `idea_rejected` sudah lebih dulu TIDAK dicatat ke ActivityLog dengan alasan
 * ini (event terjadi sebelum ada Project, tidak ada project_id untuk dicatat).
 * `idea_approved` sekarang ikut prinsip yang SAMA PERSIS (approve juga tidak
 * lagi terjadi bersamaan dengan Project apa pun) — status ProjectIdea sendiri
 * (`status` + `created_at`/`updated_at`) sudah cukup jadi riwayatnya, TIDAK
 * ada lagi ActivityLog apa pun yang ditulis dari service ini.
 */
class ProjectIdeaService
{
    public function __construct(
        private Notifier $notifier,
    ) {}

    public function propose(User $user, array $data): ProjectIdea
    {
        return ProjectIdea::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'purpose' => $data['purpose'],
            'proposed_by' => $user->id,
            'status' => 'draft',
        ]);
    }

    public function approve(ProjectIdea $idea, User $admin): ProjectIdea
    {
        $idea->update(['status' => 'approved']);

        $this->notifier->send(
            $idea->proposer,
            'idea_status_changed',
            'Ide kamu di-approve',
            "Ide kamu '{$idea->title}' sudah disetujui.",
        );

        return $idea;
    }

    public function reject(ProjectIdea $idea, User $admin, string $reason): ProjectIdea
    {
        $idea->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        $this->notifier->send(
            $idea->proposer,
            'idea_status_changed',
            'Ide kamu di-reject',
            "Ide kamu '{$idea->title}' di-reject. Alasan: {$reason}",
        );

        return $idea;
    }

    /**
     * Single entry point for the "Ubah Status" action available on EVERY
     * card regardless of its current status (draft/approved/rejected, any
     * direction) — approve()/reject() no longer have Project side effects,
     * so free status changes are safe. Dispatches to approve()/reject()
     * above for those two targets (reusing their notification wording
     * as-is); reverting to 'draft' is a plain status reset with no
     * notification (there's no "kamu dikembalikan ke draft" wording
     * anywhere in the app's notification vocabulary, and this is expected
     * to be a rare corrective action, not a normal lifecycle step).
     */
    public function changeStatus(ProjectIdea $idea, User $admin, string $newStatus, ?string $reason = null): ProjectIdea
    {
        return match ($newStatus) {
            'approved' => $this->approve($idea, $admin),
            'rejected' => $this->reject($idea, $admin, $reason ?? ''),
            default => tap($idea)->update(['status' => $newStatus, 'rejection_reason' => null]),
        };
    }
}

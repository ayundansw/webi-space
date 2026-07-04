<?php

namespace App\Services\Execution;

use App\Models\Project;
use App\Models\ProjectIdea;
use App\Models\User;

/**
 * ActivityLog.project_id is a required (non-nullable) FK to projects — confirmed
 * final by the user (2026-07-03), not changed. `idea_created` and `idea_rejected`
 * (from Lampiran A's action_type list) are therefore deliberately NOT logged to
 * ActivityLog: they happen before any Project exists (a rejected idea never gets
 * one at all), so there's no project_id to log against. ProjectIdea's own
 * `status` + `created_at`/`updated_at` columns serve as that history instead —
 * no separate audit trail needed for these two events. Only `idea_approved` is
 * logged (against the Project created at that same moment).
 */
class ProjectIdeaService
{
    public function __construct(
        private ActivityLogger $logger,
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

    public function approve(ProjectIdea $idea, User $admin, array $projectData): Project
    {
        $project = Project::create([
            'title' => $idea->title,
            'description' => $idea->description,
            'objective' => $idea->purpose,
            'project_type' => $projectData['project_type'],
            'status' => 'planning',
            'originated_from_idea_id' => $idea->id,
            'start_date' => $projectData['start_date'],
            'target_end_date' => $projectData['target_end_date'],
            'created_by' => $admin->id,
        ]);

        $idea->update([
            'status' => 'approved',
            'promoted_to_project_id' => $project->id,
        ]);

        $this->logger->log(
            $project,
            null,
            $admin,
            'idea_approved',
            "Admin meng-approve ide: {$idea->title}",
        );

        $this->logger->log(
            $project,
            null,
            $admin,
            'project_created',
            "Proyek '{$project->title}' dibuat oleh admin",
        );

        $this->notifier->send(
            $idea->proposer,
            'idea_status_changed',
            'Ide kamu di-approve',
            "Ide kamu '{$idea->title}' sudah di-approve dan menjadi proyek aktif.",
            $project,
        );

        return $project;
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
}

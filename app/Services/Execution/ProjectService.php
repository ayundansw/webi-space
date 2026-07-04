<?php

namespace App\Services\Execution;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ProjectService
{
    /**
     * Manual, admin-triggered transitions only. `planning` > `active` is
     * deliberately absent here — docs/struktur-eksekusi.md says that transition
     * happens automatically when the first Task is created (see TaskService),
     * not as an admin action.
     */
    private const ALLOWED_TRANSITIONS = [
        'planning' => [],
        'active' => ['on_hold', 'completed'],
        'on_hold' => ['active'],
        'completed' => ['archived'],
        'archived' => [],
    ];

    public function __construct(
        private ActivityLogger $logger,
        private Notifier $notifier,
    ) {}

    public function createDirect(User $admin, array $data): Project
    {
        $project = Project::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'objective' => $data['objective'],
            'project_type' => $data['project_type'],
            'status' => 'planning',
            'originated_from_idea_id' => null,
            'start_date' => $data['start_date'],
            'target_end_date' => $data['target_end_date'],
            'created_by' => $admin->id,
        ]);

        $this->logger->log(
            $project,
            null,
            $admin,
            'project_created',
            "Proyek '{$project->title}' dibuat oleh admin",
        );

        return $project;
    }

    public function addMember(Project $project, User $member, User $admin): ProjectMember
    {
        $projectMember = ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $member->id,
        ]);

        $this->logger->log(
            $project,
            null,
            $admin,
            'project_member_added',
            "{$member->name} ditambahkan ke proyek '{$project->title}'",
        );

        $this->notifier->send(
            $member,
            'added_to_project',
            'Ditambahkan ke proyek baru',
            "Kamu ditambahkan ke proyek '{$project->title}'.",
            $project,
        );

        return $projectMember;
    }

    public function removeMember(Project $project, User $member, User $admin): void
    {
        ProjectMember::where('project_id', $project->id)->where('user_id', $member->id)->delete();

        $this->logger->log(
            $project,
            null,
            $admin,
            'project_member_removed',
            "{$member->name} dikeluarkan dari proyek '{$project->title}'",
        );
    }

    public function addMilestone(Project $project, User $admin, array $data): Milestone
    {
        $nextOrder = ($project->milestones()->max('sort_order') ?? 0) + 1;

        $milestone = Milestone::create([
            'project_id' => $project->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'target_date' => $data['target_date'],
            'sort_order' => $nextOrder,
        ]);

        $this->logger->log(
            $project,
            null,
            $admin,
            'milestone_created',
            "Milestone '{$milestone->title}' ditambahkan ke proyek '{$project->title}'",
        );

        return $milestone;
    }

    public function changeStatus(Project $project, string $newStatus, User $admin): Project
    {
        $allowed = self::ALLOWED_TRANSITIONS[$project->status] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Tidak bisa mengubah status proyek dari {$project->status} ke {$newStatus}.",
            ]);
        }

        if ($newStatus === 'completed') {
            $incomplete = $project->tasks()->where('status', '!=', 'done')->exists();

            if ($incomplete) {
                throw ValidationException::withMessages([
                    'status' => 'Semua task harus berstatus done sebelum proyek bisa ditandai completed.',
                ]);
            }
        }

        $oldStatus = $project->status;

        $project->update([
            'status' => $newStatus,
            'actual_end_date' => $newStatus === 'completed' ? now()->toDateString() : $project->actual_end_date,
        ]);

        $this->logger->log(
            $project,
            null,
            $admin,
            'project_status_changed',
            "Status proyek '{$project->title}' diubah dari {$oldStatus} ke {$newStatus}",
            ['old_status' => $oldStatus, 'new_status' => $newStatus],
        );

        foreach ($project->members()->with('user')->get() as $projectMember) {
            $this->notifier->send(
                $projectMember->user,
                'project_status_changed',
                'Status proyek berubah',
                "Status proyek '{$project->title}' diubah ke {$newStatus}.",
                $project,
            );
        }

        return $project;
    }
}

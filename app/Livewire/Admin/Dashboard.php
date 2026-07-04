<?php

namespace App\Livewire\Admin;

use App\Models\GuardrailFlag;
use App\Models\Message;
use App\Models\Project;
use App\Models\ProgressUpdate;
use App\Models\Task;
use App\Models\User;
use App\Services\Execution\AlertService;
use App\Services\Exploration\ProgressService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Unified admin panel (PRD 3.0.3 / task 2.6 Batch 2): one dashboard combining
 * the Eksplorasi section (member progress + admin-only leaderboard, PRD
 * 3.0.3/3.1.4) and the Eksekusi section (docs/struktur-eksekusi.md Bagian 5,
 * PRD 3.2.9, built in 2.4) plus a quick-access summary into the WEBI log
 * (2.5's `/admin/webi`, PRD 3.0.3 "akses ke log percakapan WEBI"). Previously
 * this component only rendered the Eksekusi half — the pre-2.4 placeholder
 * comment explicitly deferred the Eksplorasi half + merge to this task.
 */
#[Layout('components.layouts.app')]
#[Title('Dashboard Admin')]
class Dashboard extends Component
{
    public function render(AlertService $alerts, ProgressService $progress)
    {
        $projects = Project::whereIn('status', ['active', 'on_hold'])->get();

        return view('livewire.admin.dashboard', [
            'projects' => $projects->map(fn (Project $p) => $this->projectSummary($p)),
            'alerts' => $this->alertPanel($alerts),
            'feed' => $this->recentProgressUpdates($projects),
            'memberSummaries' => $this->memberSummaries(),
            'explorationLeaderboard' => $this->explorationLeaderboard($progress),
            'webiSummary' => $this->webiSummary(),
        ]);
    }

    /**
     * PRD 3.0.3 "Progres setiap anggota eksplorasi" + 3.1.4/5.10 leaderboard,
     * admin-only per PRD 2.1 ("Tidak bisa mengakses ... leaderboard" for
     * exploration_member) — reuses the SAME ProgressService/UserExplorationProgress
     * source the member's own dashboard and WEBI personalization read from
     * (App\Livewire\Eksplorasi\Dashboard, App\Services\Webi\PersonalizationContextBuilder),
     * so ranking and percentages here can never drift from what a member sees
     * of their own progress.
     */
    private function explorationLeaderboard(ProgressService $progress): Collection
    {
        return User::where('role', 'exploration_member')
            ->orderBy('name')
            ->get()
            ->map(function (User $member) use ($progress) {
                $memberProgress = $progress->ensureProgress($member);

                return [
                    'member' => $member,
                    'progress' => $memberProgress,
                    'overall_percentage' => $progress->overallProgressPercentage($member),
                    'current_unit' => $memberProgress->currentUnit,
                ];
            })
            ->sortByDesc(fn (array $row) => $row['progress']->total_points)
            ->values();
    }

    private function webiSummary(): array
    {
        $memberCount = User::where('role', 'exploration_member')->count();
        $totalMessages = Message::count();
        $totalFlags = GuardrailFlag::count();
        $lastMessageAt = Message::max('created_at');

        return [
            'member_count' => $memberCount,
            'total_messages' => $totalMessages,
            'total_flags' => $totalFlags,
            'last_message_at' => $lastMessageAt ? \Illuminate\Support\Carbon::parse($lastMessageAt) : null,
        ];
    }

    private function projectSummary(Project $project): array
    {
        $activeMemberIds = Task::where('project_id', $project->id)
            ->whereIn('status', ['in_progress', 'in_review'])
            ->with('assignments')
            ->get()
            ->flatMap(fn (Task $t) => $t->assignments->pluck('user_id'))
            ->unique();

        return [
            'project' => $project,
            'progress' => $project->progressPercentage(),
            'milestones' => $project->milestones()->orderBy('sort_order')->get(),
            'active_members' => $activeMemberIds->count(),
            'days_left' => (int) now()->startOfDay()->diffInDays($project->target_end_date->startOfDay(), false),
        ];
    }

    private function alertPanel(AlertService $alerts): Collection
    {
        $items = collect();

        foreach ($alerts->overdueTasks() as $task) {
            $items->push(['severity' => 3, 'label' => 'OVERDUE', 'text' => "Task '{$task->title}' ({$task->project->title}) sudah melewati deadline.", 'task' => $task]);
        }

        foreach ($alerts->milestonesAtRisk() as $milestone) {
            $items->push(['severity' => 3, 'label' => 'MILESTONE AT RISK', 'text' => "Milestone '{$milestone->title}' ({$milestone->project->title}) tertinggal.", 'task' => null]);
        }

        foreach ($alerts->idleProjects() as $project) {
            $items->push(['severity' => 3, 'label' => 'PROJECT IDLE', 'text' => "Proyek '{$project->title}' tidak ada aktivitas.", 'task' => null]);
        }

        foreach ($alerts->stalledTasks() as $task) {
            $items->push(['severity' => 2, 'label' => 'STALLED', 'text' => "Task '{$task->title}' ({$task->project->title}) tidak ada aktivitas selama ".config('execution.stalled_days').' hari.', 'task' => $task]);
        }

        foreach ($alerts->inactiveMembers() as $member) {
            $items->push(['severity' => 3, 'label' => 'INACTIVE MEMBER', 'text' => "{$member->name} tidak ada aktivitas selama ".config('execution.inactive_member_days').' hari.', 'task' => null]);
        }

        foreach ($alerts->dueSoonTasks() as $task) {
            $items->push(['severity' => 1, 'label' => 'DUE SOON', 'text' => "Task '{$task->title}' ({$task->project->title}) deadline dalam ".config('execution.due_soon_days').' hari.', 'task' => $task]);
        }

        return $items->sortByDesc('severity')->values();
    }

    private function recentProgressUpdates(Collection $projects): Collection
    {
        $projectIds = $projects->pluck('id');

        return ProgressUpdate::whereHas('task', fn ($q) => $q->whereIn('project_id', $projectIds))
            ->with(['task', 'user'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();
    }

    private function memberSummaries(): Collection
    {
        return User::where('role', 'execution_member')->get()->map(function (User $member) {
            $tasks = Task::whereHas('assignments', fn ($q) => $q->where('user_id', $member->id))->get();

            $lastUpdate = ProgressUpdate::where('user_id', $member->id)->max('created_at');

            return [
                'member' => $member,
                'todo' => $tasks->where('status', 'todo')->count(),
                'in_progress' => $tasks->where('status', 'in_progress')->count(),
                'in_review' => $tasks->where('status', 'in_review')->count(),
                'done' => $tasks->where('status', 'done')->count(),
                'last_update' => $lastUpdate,
                'overdue_count' => $tasks->filter(fn (Task $t) => $t->status !== 'done' && $t->deadline->isPast())->count(),
            ];
        });
    }
}

<?php

namespace App\Livewire\Eksekusi;

use App\Models\ChallengeSubmission;
use App\Models\Project;
use App\Models\Task;
use App\Services\Execution\AlertService;
use App\Services\Execution\CalendarService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * 2.2.3 (dashboard Eksekusi): converts the previous plain-Blade stub
 * (`resources/views/eksekusi/dashboard.blade.php`, just a greeting + 2 links)
 * into a real personalized dashboard, consistent with the Livewire pattern
 * already used by Admin\Dashboard and Eksplorasi\Dashboard.
 *
 * Scope rule (mirrors Eksekusi\Projects\Index): every piece of data here is
 * restricted to the logged-in execution_member's OWN projects (via
 * ProjectMember) and OWN tasks (via TaskAssignment) — never all projects
 * like the admin dashboard. AlertService calls are given this member's
 * project ids explicitly so alerts about projects this member isn't part of
 * are never computed, let alone shown.
 */
#[Layout('components.layouts.app')]
#[Title('Dashboard Eksekusi')]
class Dashboard extends Component
{
    public function render(AlertService $alerts, CalendarService $calendar)
    {
        $user = Auth::user();

        $projects = Project::whereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->orderByDesc('created_at')
            ->get();

        $projectIds = $projects->pluck('id');

        // Fase 7 Batch 2a audit: NOT filtered to whereNull('parent_task_id')
        // — this is "task saya" (personal task counts), and a subtask
        // assigned to this member is real work they're responsible for.
        // Confirmed decision (2026-07-12), see AlertService docblock.
        $tasks = Task::whereHas('assignments', fn ($q) => $q->where('user_id', $user->id))->get();

        $taskCounts = [
            'todo' => $tasks->where('status', 'todo')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'in_review' => $tasks->where('status', 'in_review')->count(),
        ];

        return view('livewire.eksekusi.dashboard', [
            'projects' => $projects->map(fn (Project $p) => $this->projectSummary($p)),
            'projectCount' => $projects->count(),
            'taskCounts' => $taskCounts,
            'activeTaskCount' => array_sum($taskCounts),
            'alerts' => $this->alertPanel($alerts, $projectIds),
            // Fase 7 Batch 2b (§4.1 poin 6): "Ringkasan Kalender" — same
            // CalendarService source as the personal Kalender page
            // (App\Livewire\Eksekusi\Kalender), windowed to the soonest 5
            // upcoming Kegiatan/Acara instead of a full month grid.
            'upcomingCalendarItems' => $calendar->upcomingForUser($user, 5),
            // Praktik 3 Bagian C: this member's own review queue — a
            // submission only shows up once admin has assigned it to THEM
            // specifically (assigned_reviewer_id), never every pending
            // submission app-wide.
            'reviewQueue' => ChallengeSubmission::with('challenge', 'user')
                ->where('assigned_reviewer_id', $user->id)
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->get(),
        ]);
    }

    private function projectSummary(Project $project): array
    {
        return [
            'project' => $project,
            'progress' => $project->progressPercentage(),
            // Fase 7 Batch 2a audit: filtered to task besar only — this is
            // a per-project rollup count shown on the project card, same
            // category as progressPercentage() above.
            'task_count' => $project->tasks()->whereNull('parent_task_id')->count(),
            'next_milestone' => $project->milestones()
                ->where('target_date', '>=', now()->toDateString())
                ->orderBy('target_date')
                ->first(),
        ];
    }

    /**
     * Same shape/severity ordering as Admin\Dashboard::alertPanel(), but
     * scoped to $projectIds — the member's own projects only. inactiveMembers()
     * is deliberately excluded: it reports on OTHER members' inactivity across
     * the whole team, which is admin-facing information, not something a
     * peer execution_member should see about their teammates.
     */
    private function alertPanel(AlertService $alerts, Collection $projectIds): Collection
    {
        $items = collect();

        foreach ($alerts->overdueTasks($projectIds) as $task) {
            $items->push(['severity' => 3, 'label' => 'OVERDUE', 'text' => "Task '{$task->title}' ({$task->project->title}) sudah melewati deadline.", 'task' => $task]);
        }

        foreach ($alerts->milestonesAtRisk($projectIds) as $milestone) {
            $items->push(['severity' => 3, 'label' => 'MILESTONE AT RISK', 'text' => "Milestone '{$milestone->title}' ({$milestone->project->title}) tertinggal.", 'task' => null]);
        }

        foreach ($alerts->idleProjects($projectIds) as $project) {
            $items->push(['severity' => 3, 'label' => 'PROJECT IDLE', 'text' => "Proyek '{$project->title}' tidak ada aktivitas.", 'task' => null]);
        }

        foreach ($alerts->stalledTasks($projectIds) as $task) {
            $items->push(['severity' => 2, 'label' => 'STALLED', 'text' => "Task '{$task->title}' ({$task->project->title}) tidak ada aktivitas selama ".config('execution.stalled_days').' hari.', 'task' => $task]);
        }

        foreach ($alerts->dueSoonTasks($projectIds) as $task) {
            $items->push(['severity' => 1, 'label' => 'DUE SOON', 'text' => "Task '{$task->title}' ({$task->project->title}) deadline dalam ".config('execution.due_soon_days').' hari.', 'task' => $task]);
        }

        return $items->sortByDesc('severity')->values();
    }
}

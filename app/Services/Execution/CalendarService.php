<?php

namespace App\Services\Execution;

use App\Models\CalendarEvent;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Two categories, same source for both the project-level tab and the
 * personal cross-project view — only the scope filter differs.
 *
 * - Kegiatan (dark/ink): derived on-the-fly from Task deadlines (task besar
 *   only) and Milestone target dates. Never stored here.
 * - Acara (accent/cyan): CalendarEvent rows, manually entered.
 *
 * Every method returns a flat Collection of normalized items so callers
 * never need to know the three different underlying query shapes.
 */
class CalendarService
{
    /**
     * @return Collection<int, array{date: Carbon, category: string, kind: string, title: string, model: mixed}>
     */
    public function projectMonth(Project $project, Carbon $monthStart): Collection
    {
        $monthEnd = $monthStart->copy()->endOfMonth();

        return $this->merge(
            Task::where('project_id', $project->id)->whereNull('parent_task_id')
                ->whereBetween('deadline', [$monthStart->toDateString(), $monthEnd->toDateString()])->get(),
            Milestone::where('project_id', $project->id)
                ->whereBetween('target_date', [$monthStart->toDateString(), $monthEnd->toDateString()])->get(),
            CalendarEvent::where('project_id', $project->id)
                ->whereBetween('start_at', [$monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay()])->get(),
        );
    }

    /**
     * @return Collection<int, array{date: Carbon, category: string, kind: string, title: string, model: mixed}>
     */
    public function personalMonth(User $user, Carbon $monthStart): Collection
    {
        $monthEnd = $monthStart->copy()->endOfMonth();
        $projectIds = $this->memberProjectIds($user);

        return $this->merge(
            Task::whereIn('project_id', $projectIds)->whereNull('parent_task_id')
                ->whereBetween('deadline', [$monthStart->toDateString(), $monthEnd->toDateString()])->get(),
            Milestone::whereIn('project_id', $projectIds)
                ->whereBetween('target_date', [$monthStart->toDateString(), $monthEnd->toDateString()])->get(),
            $this->personalEventsQuery($user, $projectIds)
                ->whereBetween('start_at', [$monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay()])->get(),
        );
    }

    /**
     * Fase 3 Batch 1's "Ringkasan Kalender" Dashboard Eksekusi card + the
     * personal Kalender page's own "upcoming" strip — same source as
     * personalMonth(), just windowed by "soonest N from today" instead of
     * a calendar month.
     *
     * @return Collection<int, array{date: Carbon, category: string, kind: string, title: string, model: mixed}>
     */
    public function upcomingForUser(User $user, int $limit = 5): Collection
    {
        $now = now();
        $projectIds = $this->memberProjectIds($user);

        $items = $this->merge(
            Task::whereIn('project_id', $projectIds)->whereNull('parent_task_id')
                ->where('status', '!=', 'done')
                ->where('deadline', '>=', $now->toDateString())->get(),
            Milestone::whereIn('project_id', $projectIds)
                ->where('target_date', '>=', $now->toDateString())->get(),
            $this->personalEventsQuery($user, $projectIds)
                ->where('start_at', '>=', $now)->get(),
        );

        return $items->take($limit);
    }

    /**
     * Task 8 (Tahap B, Isi Proyek revisi): admin's Dashboard widget needs
     * Kegiatan+Acara across EVERY project, not just ones admin happens to
     * be an explicit ProjectMember of (admin usually isn't a member of any
     * specific project — access is role-wide, not membership-based) — so
     * this deliberately skips memberProjectIds() entirely rather than
     * reusing upcomingForUser()'s scoping.
     *
     * @return Collection<int, array{date: Carbon, category: string, kind: string, title: string, model: mixed}>
     */
    public function upcomingAcrossAllProjects(int $limit = 5): Collection
    {
        $now = now();

        $items = $this->merge(
            Task::whereNull('parent_task_id')
                ->where('status', '!=', 'done')
                ->where('deadline', '>=', $now->toDateString())->get(),
            Milestone::where('target_date', '>=', $now->toDateString())->get(),
            CalendarEvent::where('start_at', '>=', $now)->get(),
        );

        return $items->take($limit);
    }

    private function personalEventsQuery(User $user, Collection $projectIds)
    {
        return CalendarEvent::where(function ($q) use ($projectIds, $user) {
            $q->whereIn('project_id', $projectIds)
                ->orWhere(function ($q2) use ($user) {
                    $q2->whereNull('project_id')->where('created_by', $user->id);
                });
        });
    }

    private function memberProjectIds(User $user): Collection
    {
        return Project::whereHas('members', fn ($q) => $q->where('user_id', $user->id))->pluck('id');
    }

    private function merge(Collection $tasks, Collection $milestones, Collection $events): Collection
    {
        $items = collect()
            ->concat($tasks->map(fn (Task $task) => [
                'date' => Carbon::parse($task->deadline),
                'category' => 'kegiatan',
                'kind' => 'task_deadline',
                'title' => $task->title,
                'model' => $task,
            ]))
            ->concat($milestones->map(fn (Milestone $milestone) => [
                'date' => Carbon::parse($milestone->target_date),
                'category' => 'kegiatan',
                'kind' => 'milestone',
                'title' => $milestone->title,
                'model' => $milestone,
            ]))
            ->concat($events->map(fn (CalendarEvent $event) => [
                'date' => Carbon::parse($event->start_at),
                'category' => 'acara',
                'kind' => 'acara',
                'title' => $event->title,
                'model' => $event,
            ]));

        return $items->sortBy('date')->values();
    }
}

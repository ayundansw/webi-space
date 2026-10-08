<?php

namespace App\Livewire\Eksekusi\Projects\Tabs;

use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDependency;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Pure CSS/HTML + inline SVG overlay, no chart library — consistent with
 * this app's preference for native browser primitives (Kanban's native
 * drag-and-drop). Overhaul (Kalender+Gantt revisi): still Opsi A (no new
 * dependency), but adds a date-ruler grid, elbow/step dependency
 * connectors instead of diagonal lines, and Milestone markers plotted on
 * the SAME timeline as task bars.
 *
 * "Tanggal mulai" uses `created_at` (no `start_date` column exists) as the
 * bar's start, clamped so a bar is never negative-width if a deadline was
 * later moved earlier than creation.
 *
 * Only task besar (parent_task_id null) appear — subtasks can't hold a
 * dependency at all (TaskDependencyService rejects it).
 *
 * No caching anywhere in this component — render() re-queries fresh on
 * every request, so the click-to-open popup (assignee/status/subtask
 * progress) can never show data that's staler than what the Kanban tab
 * shows for the same task at the same moment.
 */
#[Layout('components.layouts.app')]
#[Title('Gantt Chart Proyek')]
class Gantt extends Component
{
    private const RULER_HEIGHT = 28;

    private const ROW_HEIGHT = 40;

    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function render()
    {
        // Task 6 (batch sebelumnya): assignee + subtasks eager loaded di
        // sini supaya popup klik tidak butuh Livewire round-trip baru.
        $tasks = $this->project->tasks()->whereNull('parent_task_id')
            ->with(['assignments.user', 'subtasks'])
            ->orderBy('created_at')->get();

        $milestones = $this->project->milestones()->orderBy('sort_order')->get();

        if ($tasks->isEmpty() && $milestones->isEmpty()) {
            return view('livewire.eksekusi.projects.tabs.gantt', [
                'hasData' => false, 'rows' => [], 'edges' => [], 'gridLines' => [],
                'milestoneMarkers' => [], 'rangeStart' => null, 'rangeEnd' => null,
                'milestoneRowOffset' => 0, 'totalHeight' => 0,
                'rulerHeight' => self::RULER_HEIGHT, 'rowHeight' => self::ROW_HEIGHT,
            ]);
        }

        $taskBounds = $tasks->map(fn (Task $task) => [
            'start' => $this->clampedStart($task),
            'end' => Carbon::parse($task->deadline->toDateString()),
        ]);

        // Task 2 poin 3: Milestone target_date turut menentukan rentang
        // linimasa (bukan cuma tanggal task) supaya marker-nya tidak pernah
        // terpotong di luar grid yang ditampilkan.
        $allDates = $taskBounds->flatMap(fn (array $b) => [$b['start'], $b['end']])
            ->concat($milestones->map(fn (Milestone $m) => Carbon::parse($m->target_date->toDateString())));

        $rangeStart = $allDates->min();
        $rangeEnd = $allDates->max();

        if ($rangeStart->equalTo($rangeEnd)) {
            // A single task/milestone alone still needs SOME width to plot
            // a bar/marker against.
            $rangeEnd = $rangeStart->copy()->addDay();
        }

        $totalDays = max($rangeStart->diffInDays($rangeEnd), 1);
        $milestoneRowOffset = $milestones->isNotEmpty() ? 1 : 0;

        $rows = [];
        $rowKeyByTaskId = [];

        foreach ($tasks as $index => $task) {
            $start = $this->clampedStart($task);
            $end = Carbon::parse($task->deadline->toDateString());

            $leftPct = $rangeStart->diffInDays($start) / $totalDays * 100;
            $widthPct = max($start->diffInDays($end) / $totalDays * 100, 1.5);

            $rows[] = [
                'task' => $task,
                'rowIndex' => $index + $milestoneRowOffset,
                'leftPct' => $leftPct,
                'widthPct' => $widthPct,
                'startLabel' => $start->locale('id')->translatedFormat('d M'),
                'endLabel' => $end->locale('id')->translatedFormat('d M'),
            ];
            $rowKeyByTaskId[$task->id] = $index;
        }

        $totalHeight = self::RULER_HEIGHT + (count($rows) + $milestoneRowOffset) * self::ROW_HEIGHT;

        // Task 2 poin 2: elbow (horizontal-vertical-horizontal) connector
        // instead of a single diagonal <line> that used to cut across
        // unrelated bars in between. $midX is the shared vertical segment's
        // x — halfway between source-end and target-start, floored at a
        // minimum 10-unit stub so the elbow is still visible even when the
        // target starts at/before the source ends (overlapping schedule).
        $taskIds = $tasks->pluck('id');
        $edges = [];

        foreach (TaskDependency::whereIn('task_id', $taskIds)->whereIn('depends_on_task_id', $taskIds)->get() as $dependency) {
            $fromRow = $rows[$rowKeyByTaskId[$dependency->depends_on_task_id]];
            $toRow = $rows[$rowKeyByTaskId[$dependency->task_id]];

            $fromX = ($fromRow['leftPct'] + $fromRow['widthPct']) * 10;
            $fromY = self::RULER_HEIGHT + $fromRow['rowIndex'] * self::ROW_HEIGHT + self::ROW_HEIGHT / 2;
            $toX = $toRow['leftPct'] * 10;
            $toY = self::RULER_HEIGHT + $toRow['rowIndex'] * self::ROW_HEIGHT + self::ROW_HEIGHT / 2;
            $midX = $fromX + max(($toX - $fromX) / 2, 10);

            $edges[] = [
                'fromX' => $fromX, 'fromY' => $fromY,
                'midX' => $midX,
                'toX' => $toX, 'toY' => $toY,
            ];
        }

        // Task 2 poin 1: ruler tanggal -- interval adaptif supaya tidak
        // terlalu padat di proyek panjang maupun terlalu jarang di proyek
        // pendek.
        $intervalDays = match (true) {
            $totalDays <= 21 => 2,
            $totalDays <= 60 => 7,
            $totalDays <= 180 => 14,
            default => 30,
        };

        $gridLines = [];
        $cursor = $rangeStart->copy();
        while ($cursor->lte($rangeEnd)) {
            $gridLines[] = [
                'leftPct' => $rangeStart->diffInDays($cursor) / $totalDays * 100,
                'label' => $cursor->locale('id')->translatedFormat('d M'),
            ];
            $cursor->addDays($intervalDays);
        }
        if (end($gridLines)['leftPct'] < 98.0) {
            $gridLines[] = ['leftPct' => 100, 'label' => $rangeEnd->locale('id')->translatedFormat('d M')];
        }

        // Task 2 poin 3: marker Milestone di baris atas, warna terpisah
        // (success/teal) dari bar Task (accent).
        $milestoneMarkers = $milestones->map(fn (Milestone $milestone) => [
            'title' => $milestone->title,
            'leftPct' => $rangeStart->diffInDays(Carbon::parse($milestone->target_date->toDateString())) / $totalDays * 100,
            'dateLabel' => $milestone->target_date->locale('id')->translatedFormat('d M Y'),
        ])->all();

        return view('livewire.eksekusi.projects.tabs.gantt', [
            'hasData' => true,
            'rows' => $rows,
            'edges' => $edges,
            'gridLines' => $gridLines,
            'milestoneMarkers' => $milestoneMarkers,
            'milestoneRowOffset' => $milestoneRowOffset,
            'totalHeight' => $totalHeight,
            'rulerHeight' => self::RULER_HEIGHT,
            'rowHeight' => self::ROW_HEIGHT,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
        ]);
    }

    private function clampedStart(Task $task): Carbon
    {
        $created = $task->created_at->toDateString();
        $deadline = $task->deadline->toDateString();

        return Carbon::parse(min($created, $deadline));
    }
}

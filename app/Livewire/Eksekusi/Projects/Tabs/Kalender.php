<?php

namespace App\Livewire\Eksekusi\Projects\Tabs;

use App\Livewire\Eksekusi\Concerns\BuildsCalendarWeeks;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Services\Execution\CalendarService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Fase 7 Batch 2b: Kegiatan (task besar deadline + milestone, derived —
 * never stored) + Acara (CalendarEvent, project-scoped) drawn from the
 * SAME CalendarService source the personal Kalender page uses (see
 * App\Livewire\Eksekusi\Kalender), just filtered to this project. No
 * mount() membership check: route is already behind `project.member`.
 */
#[Layout('components.layouts.app')]
#[Title('Kalender Proyek')]
class Kalender extends Component
{
    use BuildsCalendarWeeks;

    public Project $project;

    public string $month;

    /** @var string `<input type="month">` companion to $month, format Y-m — kept in sync everywhere $month changes. */
    public string $monthInput = '';

    public string $newEventDate = '';

    public string $newEventTitle = '';

    public string $newEventDescription = '';

    public string $newEventTime = '';

    public string $newEventType = 'other';

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->month = now()->startOfMonth()->toDateString();
        $this->syncMonthInput();
    }

    public function previousMonth(): void
    {
        $this->month = Carbon::parse($this->month)->subMonthNoOverflow()->toDateString();
        $this->syncMonthInput();
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::parse($this->month)->addMonthNoOverflow()->toDateString();
        $this->syncMonthInput();
    }

    public function goToToday(): void
    {
        $this->month = now()->startOfMonth()->toDateString();
        $this->syncMonthInput();
    }

    /**
     * Task 7 (Tahap B, Isi Proyek revisi): faster month jump via
     * `<input type="month">` instead of clicking the arrows repeatedly.
     * Silently ignores a malformed value rather than throwing — this only
     * ever fires from the month-input's own wire:model.live, but a
     * defensive check costs nothing and avoids a 500 on a stray value.
     */
    public function updatedMonthInput(): void
    {
        if (preg_match('/^\d{4}-\d{2}$/', $this->monthInput) === 1) {
            $this->month = Carbon::parse($this->monthInput.'-01')->startOfMonth()->toDateString();
        }
    }

    private function syncMonthInput(): void
    {
        $this->monthInput = Carbon::parse($this->month)->format('Y-m');
    }

    /**
     * Task 1 (perf revisi, Kalender+Gantt overhaul): opening/closing the
     * add-event popup is now PURE Alpine local state on the view side
     * (`showForm`/`selectedDate`, same pattern as _idea-card.blade.php's
     * detail modal) — no Livewire method call happens just to toggle
     * visibility anymore, so no full component round-trip (and no re-query
     * of the whole month via CalendarService::projectMonth()) fires on
     * every date click. Livewire is only actually invoked here, on submit,
     * with the Alpine-picked date passed straight in as an argument.
     */
    public function addEvent(string $date): void
    {
        $this->newEventDate = $date;

        $validated = $this->validate([
            'newEventDate' => ['required', 'date'],
            'newEventTitle' => ['required', 'string', 'max:255'],
            'newEventDescription' => ['nullable', 'string'],
            'newEventTime' => ['nullable', 'date_format:H:i'],
            'newEventType' => ['required', 'in:meeting,competition,other'],
        ]);

        CalendarEvent::create([
            'project_id' => $this->project->id,
            'created_by' => Auth::id(),
            'title' => $validated['newEventTitle'],
            'description' => $validated['newEventDescription'] ?: null,
            'start_at' => Carbon::parse($validated['newEventDate'].' '.($validated['newEventTime'] ?: '00:00')),
            'type' => $validated['newEventType'],
        ]);

        $this->reset(['newEventDate', 'newEventTitle', 'newEventDescription', 'newEventTime', 'newEventType']);

        // Consumed by Alpine (x-on:event-added.window) to close the popup
        // and clear the picked date — only fires on the success path (the
        // return-early inside validate() above never reaches here), so a
        // validation error leaves the popup open showing the error, same
        // as every other modal-form pattern in this app.
        $this->dispatch('event-added');
    }

    public function render(CalendarService $service)
    {
        $monthStart = Carbon::parse($this->month);
        $items = $service->projectMonth($this->project, $monthStart);

        return view('livewire.eksekusi.projects.tabs.kalender', [
            'monthStart' => $monthStart,
            'weeks' => $this->buildCalendarWeeks($monthStart, $items),
        ]);
    }
}

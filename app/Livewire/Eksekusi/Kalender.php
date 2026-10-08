<?php

namespace App\Livewire\Eksekusi;

use App\Livewire\Eksekusi\Concerns\BuildsCalendarWeeks;
use App\Models\CalendarEvent;
use App\Services\Execution\CalendarService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * "Kalender Personal" — Kegiatan+Acara from ALL projects the user is a
 * member of, plus personal Acara (project_id null, created_by = user).
 * Same CalendarService source as the project Kalender tab, wider scope.
 *
 * execution_member only (not admin) — "proyek yang diikuti user" doesn't
 * map onto admin's app-wide access.
 *
 * The add-event form here always creates project_id = null; project-scoped
 * events are still created from that project's own Kalender tab.
 */
#[Layout('components.layouts.app')]
#[Title('Kalender Personal')]
class Kalender extends Component
{
    use BuildsCalendarWeeks;

    public string $month;

    /** @var string `<input type="month">` companion to $month, format Y-m — kept in sync everywhere $month changes. */
    public string $monthInput = '';

    public string $newEventDate = '';

    public string $newEventTitle = '';

    public string $newEventDescription = '';

    public string $newEventTime = '';

    public string $newEventType = 'other';

    public function mount(): void
    {
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
     * Task 1 (perf revisi, Kalender+Gantt overhaul): see the identical
     * docblock on App\Livewire\Eksekusi\Projects\Tabs\Kalender::addEvent()
     * — same reasoning, opening/closing the popup is pure Alpine now, this
     * is the only server round-trip left in the whole add-event flow.
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
            'project_id' => null,
            'created_by' => Auth::id(),
            'title' => $validated['newEventTitle'],
            'description' => $validated['newEventDescription'] ?: null,
            'start_at' => Carbon::parse($validated['newEventDate'].' '.($validated['newEventTime'] ?: '00:00')),
            'type' => $validated['newEventType'],
        ]);

        $this->reset(['newEventDate', 'newEventTitle', 'newEventDescription', 'newEventTime', 'newEventType']);
        $this->dispatch('event-added');
    }

    public function render(CalendarService $service)
    {
        $monthStart = Carbon::parse($this->month);
        $items = $service->personalMonth(Auth::user(), $monthStart);

        return view('livewire.eksekusi.kalender', [
            'monthStart' => $monthStart,
            'weeks' => $this->buildCalendarWeeks($monthStart, $items),
            'upcoming' => $service->upcomingForUser(Auth::user(), 5),
        ]);
    }
}

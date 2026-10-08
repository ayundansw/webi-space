<?php

namespace App\Livewire\Eksekusi\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Shared by the project Kalender tab and the personal Kalender page
 * (Fase 7 Batch 2b) — turns CalendarService's flat item list into a
 * Monday-start month grid (leading/trailing days from adjacent months
 * included so every week row has all 7 columns).
 */
trait BuildsCalendarWeeks
{
    /**
     * @return array<int, array<int, array{date: Carbon, inMonth: bool, items: Collection}>>
     */
    private function buildCalendarWeeks(Carbon $monthStart, Collection $items): array
    {
        $byDate = $items->groupBy(fn (array $item) => $item['date']->toDateString());

        $cursor = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::MONDAY);

        $weeks = [];

        while ($cursor->lte($gridEnd)) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $week[] = [
                    'date' => $cursor->copy(),
                    'inMonth' => $cursor->month === $monthStart->month,
                    'items' => $byDate->get($cursor->toDateString(), collect()),
                ];
                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }
}

<?php

namespace App\Services;

use App\Enums\EventType;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTime;

class EventService
{
    /**
     * Get total date of events in period.
     *
     * @param  User             $user The related user to calculate.
     * @param  string|DateTime  $startPeriod The start date of period.
     * @param  string|DateTime  $endPeriod The end date of period.
     * @param  array            $types Array of EventType::class enum, used to define which type of Event to be calculate.
     *
     * @return int The total count of event dates result.
     */

    public static function countTotalDateInPeriods(User $user, string|DateTime $startPeriod, string|DateTime $endPeriod, $types): int
    {
        // get period dates between $startPeriod and $endPeriod
        $periodDates = collect(CarbonPeriod::create($startPeriod, $endPeriod))->map(fn($period) => $period->format('Y-m-d'))->toArray();

        // get events of its period & types
        $events = Event::whereBetween('start_at', [$startPeriod, $endPeriod])->whereIn('type', $types);
        if ($user->company_id) {
            $events = $events->where(function ($q) use ($user) {
                $q->whereNull('company_id');
                $q->orWhere('company_id', $user->company_id);
            });
        } else {
            $events = $events->whereNull('company_id');
        }
        $events = $events->get();
        // end get events

        // push event dates to new array
        $eventDates = [];
        foreach ($events as $event) {
            array_push($eventDates, ...collect(CarbonPeriod::create($event->start_at, $event->end_at))->map(fn($period) => $period->format('Y-m-d'))->toArray());
        }

        // count event dates within $periodDates
        $count = count(collect($periodDates)->intersect($eventDates)->toArray());

        return $count;
    }

    public static function getDates(EventType $type, string $startAt, string $endAt): array
    {
        $events = Event::select('id', 'start_at', 'end_at')->where('type', $type)->whereDateBetween($startAt, $endAt)->get();

        $dates = $events->flatMap(function ($item) {
            $start = Carbon::parse($item['start_at']);
            $end = Carbon::parse($item['end_at']);

            if ($start->equalTo($end)) {
                // Jika start_at == end_at, hanya tambahkan satu tanggal
                return [$start->toDateString()];
            } else {
                // Jika end_at > start_at, ambil rentang tanggal
                return collect(range(0, $start->diffInDays($end)))
                    ->map(fn($day) => $start->copy()->addDays($day)->toDateString());
            }
        })->unique()->values()->toArray();

        return $dates;
    }
}

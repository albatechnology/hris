<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
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
        $periodDates = collect(CarbonPeriod::create($startPeriod, $endPeriod))->map(fn ($period) => $period->format('Y-m-d'))->toArray();

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
            array_push($eventDates, ...collect(CarbonPeriod::create($event->start_at, $event->end_at))->map(fn ($period) => $period->format('Y-m-d'))->toArray());
        }

        // count event dates within $periodDates
        $count = count(collect($periodDates)->intersect($eventDates)->toArray());

        return $count;
    }
}

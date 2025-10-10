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
        $events = Event::select('id', 'start_at', 'end_at')->tenanted()->where('type', $type)->whereDateBetween($startAt, $endAt)->get();

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

    public static function getCalendarDate(): array
    {
        return [
            ['name' => 'Tahun Baru Masehi', 'date' => '2025-01-01'],
            ['name' => 'Isra Mikraj Nabi Muhammad SAW', 'date' => '2025-01-27'],
            ['name' => 'Tahun Baru Imlek 2576 Kongzili', 'date' => '2025-01-29'],
            ['name' => 'Hari Suci Nyepi (Tahun Baru Saka 1947)', 'date' => '2025-03-29'],
            ['name' => 'Hari Raya Idulfitri 1446 Hijriah', 'date' => '2025-03-31'],
            ['name' => 'Hari Raya Idulfitri 1446 Hijriah', 'date' => '2025-04-01'],
            ['name' => 'Wafat Yesus Kristus', 'date' => '2025-04-18'],
            ['name' => 'Hari Paskah', 'date' => '2025-04-20'],
            ['name' => 'Hari Buruh Internasional', 'date' => '2025-05-01'],
            ['name' => 'Hari Raya Waisak 2569 BE', 'date' => '2025-05-12'],
            ['name' => 'Kenaikan Yesus Kristus', 'date' => '2025-05-29'],
            ['name' => 'Hari Lahir Pancasila', 'date' => '2025-06-01'],
            ['name' => 'Hari Raya Idul Adha 1446 Hijriah', 'date' => '2025-06-06'],
            ['name' => 'Tahun Baru Islam 1447 Hijriah', 'date' => '2025-06-27'],
            ['name' => 'Hari Kemerdekaan Republik Indonesia', 'date' => '2025-08-17'],
            ['name' => 'Maulid Nabi Muhammad SAW', 'date' => '2025-09-05'],
            ['name' => 'Hari Raya Natal', 'date' => '2025-12-25'],
        ];
    }
}

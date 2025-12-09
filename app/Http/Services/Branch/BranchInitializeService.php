<?php

namespace App\Http\Services\Branch;

use App\Enums\EventType;
use App\Models\AbsenceReminder;
use App\Models\Branch;
use App\Models\Event;
use App\Models\Overtime;
use App\Services\EventService;

class BranchInitializeService
{
    public function __invoke(Branch $branch): void
    {
        if (config('app.name') == 'SYNTEGRA') {
            AbsenceReminder::create([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'minutes_before' => 60,
                'minutes_repeat' => 60,
            ]);

            $dates = EventService::getCalendarDate();

            collect($dates)->each(function ($date) use ($branch) {
                $date['company_id'] = $branch->company_id;
                $date['branch_id'] = $branch->id;
                $date['start_at'] = $date['date'];
                $date['type'] = EventType::NATIONAL_HOLIDAY->value;
                unset($date['date']);
                Event::create($date);
            });

            Overtime::create([
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
                'compensation_rate_per_day' => 0,
                'name' => "Default",
                'rate_amount' => 0,
                'rate_type' => "amount",
            ]);

            $branch->createPayrollSetting();
        }
    }
}

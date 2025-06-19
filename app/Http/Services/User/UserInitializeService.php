<?php

namespace App\Http\Services\User;

use App\Enums\BankName;
use App\Enums\EventType;
use App\Enums\TimeoffPolicyType;
use App\Models\User;
use App\Services\EventService;
use App\Services\SettingService;

class UserInitializeService
{
    public function __invoke(User $user): void
    {
        $user->absenceReminder()->create();
        $user->banks()->create([
            'name' => BankName::BCA,
            'account_no' => '0000000000',
            'account_holder' => $user->name,
            'code' => '0000000000',
            'branch' => $user->city,
        ]);

        $user->branches()->create([
            'name' => $user->name,
            'country' => $user->country,
            'province' => $user->province,
            'city' => $user->city,
            'zip_code' => $user->zip_code,
            'lat' => $user->lat,
            'lng' => $user->lng,
            'address' => $user->address,
        ]);

        $division = $user->divisions()->create([
            'name' => 'Operational',
        ]);
        $division = $division->departments()->create([
            'name' => 'HR'
        ]);
        $user->positions()->create([
            'name' => 'Manager'
        ]);

        $dates = collect(EventService::getCalendarDate())->map(function ($date) {
            $date['start_at'] = $date['date'];
            $date['type'] = EventType::NATIONAL_HOLIDAY->value;
            unset($date['date']);
            return $date;
        });
        $user->events()->createMany($dates);

        $liveAttendance = $user->liveAttendances()->create([
            'name' => 'Live Attendance ' . $user->name,
            'is_flexible' => true,
        ]);
        $liveAttendance->locations()->create([
            'name' => 'Head Office',
            'radius' => 100,
            'lat' => "0",
            'lng' => "0",
        ]);

        $user->overtimes()->create([
            'compensation_rate_per_day' => 0,
            'name' => "Default",
            'rate_amount' => 0,
            'rate_type' => "amount",
        ]);

        $user->createPayrollSetting();

        // Role::create([
        //     'group_id' => $user->group_id,
        //     'name' => 'User ' . $user->group_id,
        //     'guard_name' => 'web',
        // ]);

        $schedule = $user->schedules()->create([
            'name' => 'Default Schedule',
            'effective_date' => "2025-01-06",
            'description' => "Default Schedule",
        ]);
        $shift = $user->shifts()->create([
            'name' => 'Default Shift',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);
        for ($i = 1; $i < 8; $i++) {
            if ($i < 6) {
                $schedule->shifts()->attach($shift->id, ['order' => $i]);
            } else {
                $schedule->shifts()->attach(1, ['order' => $i]);
            }
        }

        // TimeoffRegulationService::create($user, TimeoffRenewType::PERIOD);
        \App\Models\RequestChangeDataAllowes::createForUser($user);

        SettingService::create($user);

        $user->timeoffPolicies()->create([
            'name' => 'Annual Leave',
            'code' => 'AL',
            'type' => TimeoffPolicyType::ANNUAL_LEAVE,
            'effective_date' => date('Y-m-d'),
            'is_allow_halfday' => true,
            'max_consecutively_day' => 5,
        ]);
    }
}

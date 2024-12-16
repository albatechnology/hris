<?php

namespace App\Services;

use App\Models\Shift;
use Carbon\Carbon;

class ShiftService
{
    public static function getIntervalHours(Shift $shift): int
    {
        $shiftClockIn = $shift->clock_in;
        $shiftClockOut = $shift->clock_out;

        if (strtotime($shiftClockOut) < strtotime($shiftClockIn)) {
            $shiftClockIn = new Carbon(date('Y-m-d ' . $shift->clock_in));
            $shiftClockOut = new Carbon(date('Y-m-d ' . $shift->clock_out, strtotime('+1 day')));
        } else {
            $shiftClockIn = new Carbon(date('Y-m-d ' . $shift->clock_in));
            $shiftClockOut = new Carbon(date('Y-m-d ' . $shift->clock_out));
        }

        $interval = max($shiftClockIn->diffInHours($shiftClockOut), 0);
        return ceil($interval);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AttendanceHelper;
use App\Jobs\AnnualLeave\NewEmployee;
use Illuminate\Http\Request;

class TestController extends BaseController
{
    // public function generateTimeoff()
    // {
    //     NewEmployee::dispatch();

    //     return "success";
    // }

    public function getAttendance(Request $request)
    {

        NewEmployee::dispatch();
        return "done";

        $getAttendance = AttendanceHelper::getTotalAttendance($request->user_id, $request->start_date, $request->end_date);

        return $getAttendance;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Jobs\AnnualLeave\NewEmployee;

class TestController extends BaseController
{
    public function generateTimeoff()
    {
        NewEmployee::dispatch();

        return "success";
    }
}

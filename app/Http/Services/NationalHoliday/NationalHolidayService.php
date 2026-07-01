<?php

namespace App\Http\Services\NationalHoliday;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\NationalHoliday\NationalHolidayRepositoryInterface;
use App\Interfaces\Services\NationalHoliday\NationalHolidayServiceInterface;

class NationalHolidayService extends BaseService implements NationalHolidayServiceInterface
{
    public function __construct(protected NationalHolidayRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
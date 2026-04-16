<?php

namespace App\Http\Repositories\NationalHoliday;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\NationalHoliday\NationalHolidayRepositoryInterface;
use App\Models\NationalHoliday;

class NationalHolidayRepository extends BaseRepository implements NationalHolidayRepositoryInterface
{
    public function __construct(NationalHoliday $model)
    {
        parent::__construct($model);
    }
}
<?php

namespace App\Policies;

use App\Models\NationalHoliday;
use App\Models\User;

class NationalHolidayPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('national_holiday_read');
    }

    public function view(User $user, NationalHoliday $nationalHoliday): bool
    {
        return $user->can('national_holiday_read');
    }

    public function create(User $user): bool
    {
        return $user->can('national_holiday_create');
    }

    public function update(User $user, NationalHoliday $nationalHoliday): bool
    {
        return $user->can('national_holiday_edit');
    }

    public function delete(User $user, NationalHoliday $nationalHoliday): bool
    {
        return $user->can('national_holiday_delete');
    }
}
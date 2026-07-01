<?php

namespace App\Policies;

use App\Models\NationalHoliday;
use App\Models\User;

class PayrollComponentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payroll_setting_read');
    }

    public function view(User $user, NationalHoliday $nationalHoliday): bool
    {
        return $user->can('payroll_setting_read');
    }

    public function create(User $user): bool
    {
        return $user->can('payroll_setting_create');
    }

    public function update(User $user, NationalHoliday $nationalHoliday): bool
    {
        return $user->can('payroll_setting_edit');
    }

    public function delete(User $user, NationalHoliday $nationalHoliday): bool
    {
        return $user->can('payroll_setting_delete');
    }
}
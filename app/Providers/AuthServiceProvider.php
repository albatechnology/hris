<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Enums\UserType;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Announcement::class => \App\Policies\AnnouncementPolicy::class,
        \App\Models\Bank::class => \App\Policies\BankPolicy::class,
        \App\Models\Branch::class => \App\Policies\BranchPolicy::class,
        \App\Models\BranchLocation::class => \App\Policies\BranchLocationPolicy::class,
        \App\Models\Loan::class => \App\Policies\LoanPolicy::class,
        \App\Models\LockAttendance::class => \App\Policies\LockAttendancePolicy::class,
        \App\Models\Company::class => \App\Policies\CompanyPolicy::class,
        \App\Models\CustomField::class => \App\Policies\CustomFieldPolicy::class,
        \App\Models\Department::class => \App\Policies\DepartmentPolicy::class,
        \App\Models\Position::class => \App\Policies\PositionPolicy::class,
        \App\Models\Division::class => \App\Policies\DivisionPolicy::class,
        \App\Models\Group::class => \App\Policies\GroupPolicy::class,
        \App\Models\Incident::class => \App\Policies\IncidentPolicy::class,
        \App\Models\LiveAttendance::class => \App\Policies\LiveAttendancePolicy::class,
        \App\Models\LiveAttendanceLocation::class => \App\Policies\LiveAttendanceLocationPolicy::class,
        \App\Models\GuestBook::class => \App\Policies\GuestBookPolicy::class,
        \App\Models\NationalHoliday::class => \App\Policies\NationalHolidayPolicy::class,
        \App\Models\PayrollComponent::class => \App\Policies\PayrollComponentPolicy::class,
        \App\Models\Reimbursement::class => \App\Policies\ReimbursementPolicy::class,
        \App\Models\ReimbursementCategory::class => \App\Policies\ReimbursementCategoryPolicy::class,
        \App\Models\Role::class => \App\Policies\RolePolicy::class,
        \App\Models\Schedule::class => \App\Policies\SchedulePolicy::class,
        \App\Models\Shift::class => \App\Policies\ShiftPolicy::class,
        \App\Models\Subscription::class => \App\Policies\SubscriptionPolicy::class,
        \App\Models\SupervisorType::class => \App\Policies\SupervisorTypePolicy::class,
        \App\Models\Task::class => \App\Policies\TaskPolicy::class,
        \App\Models\TaskHour::class => \App\Policies\TaskHourPolicy::class,
        \App\Models\TimeoffPolicy::class => \App\Policies\TimeoffPolicyPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->type->in([UserType::SUPER_ADMIN, UserType::ADMIN]) ? true : null;
        });
    }
}

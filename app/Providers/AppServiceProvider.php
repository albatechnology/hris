<?php

namespace App\Providers;

use App\Broadcasting\FcmChannel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        \App\Interfaces\Repositories\Announcement\AnnouncementRepositoryInterface::class => \App\Http\Repositories\Announcement\AnnouncementRepository::class,
        \App\Interfaces\Services\Announcement\AnnouncementServiceInterface::class => \App\Http\Services\Announcement\AnnouncementService::class,

        \App\Interfaces\Repositories\Bank\BankRepositoryInterface::class => \App\Http\Repositories\Bank\BankRepository::class,
        \App\Interfaces\Services\Bank\BankServiceInterface::class => \App\Http\Services\Bank\BankService::class,

        \App\Interfaces\Repositories\Branch\BranchRepositoryInterface::class => \App\Http\Repositories\Branch\BranchRepository::class,
        \App\Interfaces\Services\Branch\BranchServiceInterface::class => \App\Http\Services\Branch\BranchService::class,

        \App\Interfaces\Repositories\Loan\LoanRepositoryInterface::class => \App\Http\Repositories\Loan\LoanRepository::class,
        \App\Interfaces\Services\Loan\LoanServiceInterface::class => \App\Http\Services\Loan\LoanService::class,

        \App\Interfaces\Repositories\LockAttendance\LockAttendanceRepositoryInterface::class => \App\Http\Repositories\LockAttendance\LockAttendanceRepository::class,
        \App\Interfaces\Services\LockAttendance\LockAttendanceServiceInterface::class => \App\Http\Services\LockAttendance\LockAttendanceService::class,

        \App\Interfaces\Repositories\BranchLocation\BranchLocationRepositoryInterface::class => \App\Http\Repositories\BranchLocation\BranchLocationRepository::class,
        \App\Interfaces\Services\BranchLocation\BranchLocationServiceInterface::class => \App\Http\Services\BranchLocation\BranchLocationService::class,

        \App\Interfaces\Repositories\Company\CompanyRepositoryInterface::class => \App\Http\Repositories\Company\CompanyRepository::class,
        \App\Interfaces\Services\Company\CompanyServiceInterface::class => \App\Http\Services\Company\CompanyService::class,

        \App\Interfaces\Repositories\Group\GroupRepositoryInterface::class => \App\Http\Repositories\Group\GroupRepository::class,
        \App\Interfaces\Services\Group\GroupServiceInterface::class => \App\Http\Services\Group\GroupService::class,

        \App\Interfaces\Repositories\Incident\IncidentRepositoryInterface::class => \App\Http\Repositories\Incident\IncidentRepository::class,
        \App\Interfaces\Services\Incident\IncidentServiceInterface::class => \App\Http\Services\Incident\IncidentService::class,

        \App\Interfaces\Repositories\LiveAttendance\LiveAttendanceRepositoryInterface::class => \App\Http\Repositories\LiveAttendance\LiveAttendanceRepository::class,
        \App\Interfaces\Services\LiveAttendance\LiveAttendanceServiceInterface::class => \App\Http\Services\LiveAttendance\LiveAttendanceService::class,

        \App\Interfaces\Repositories\LiveAttendanceLocation\LiveAttendanceLocationRepositoryInterface::class => \App\Http\Repositories\LiveAttendanceLocation\LiveAttendanceLocationRepository::class,
        \App\Interfaces\Services\LiveAttendanceLocation\LiveAttendanceLocationServiceInterface::class => \App\Http\Services\LiveAttendanceLocation\LiveAttendanceLocationService::class,

        \App\Interfaces\Repositories\CustomField\CustomFieldRepositoryInterface::class => \App\Repositories\CustomField\CustomFieldRepository::class,
        \App\Interfaces\Services\CustomField\CustomFieldServiceInterface::class => \App\Services\CustomField\CustomFieldService::class,

        \App\Interfaces\Repositories\Department\DepartmentRepositoryInterface::class => \App\Repositories\Department\DepartmentRepository::class,
        \App\Interfaces\Services\Department\DepartmentServiceInterface::class => \App\Services\Department\DepartmentService::class,

        \App\Interfaces\Repositories\Position\PositionRepositoryInterface::class => \App\Http\Repositories\Position\PositionRepository::class,
        \App\Interfaces\Services\Position\PositionServiceInterface::class => \App\Http\Services\Position\PositionService::class,

        \App\Interfaces\Repositories\Division\DivisionRepositoryInterface::class => \App\Repositories\Division\DivisionRepository::class,
        \App\Interfaces\Services\Division\DivisionServiceInterface::class => \App\Services\Division\DivisionService::class,

        \App\Interfaces\Repositories\DailyActivity\DailyActivityRepositoryInterface::class => \App\Http\Repositories\DailyActivity\DailyActivityRepository::class,
        \App\Interfaces\Services\DailyActivity\DailyActivityServiceInterface::class => \App\Http\Services\DailyActivity\DailyActivityService::class,

        \App\Interfaces\Repositories\GuestBook\GuestBookRepositoryInterface::class => \App\Http\Repositories\GuestBook\GuestBookRepository::class,
        \App\Interfaces\Services\GuestBook\GuestBookServiceInterface::class => \App\Http\Services\GuestBook\GuestBookService::class,

        \App\Interfaces\Repositories\NationalHoliday\NationalHolidayRepositoryInterface::class => \App\Http\Repositories\NationalHoliday\NationalHolidayRepository::class,
        \App\Interfaces\Services\NationalHoliday\NationalHolidayServiceInterface::class => \App\Http\Services\NationalHoliday\NationalHolidayService::class,

        \App\Interfaces\Repositories\ReimbursementCategory\ReimbursementCategoryRepositoryInterface::class => \App\Http\Repositories\ReimbursementCategory\ReimbursementCategoryRepository::class,
        \App\Interfaces\Services\ReimbursementCategory\ReimbursementCategoryServiceInterface::class => \App\Http\Services\ReimbursementCategory\ReimbursementCategoryService::class,

        \App\Interfaces\Repositories\Role\RoleRepositoryInterface::class => \App\Http\Repositories\Role\RoleRepository::class,
        \App\Interfaces\Services\Role\RoleServiceInterface::class => \App\Http\Services\Role\RoleService::class,

        \App\Interfaces\Repositories\Reimbursement\ReimbursementRepositoryInterface::class => \App\Http\Repositories\Reimbursement\ReimbursementRepository::class,
        \App\Interfaces\Services\Reimbursement\ReimbursementServiceInterface::class => \App\Http\Services\Reimbursement\ReimbursementService::class,

        \App\Interfaces\Repositories\Subscription\SubscriptionRepositoryInterface::class => \App\Http\Repositories\Subscription\SubscriptionRepository::class,
        \App\Interfaces\Services\Subscription\SubscriptionServiceInterface::class => \App\Http\Services\Subscription\SubscriptionService::class,

        \App\Interfaces\Repositories\User\UserRepositoryInterface::class => \App\Http\Repositories\User\UserRepository::class,
        \App\Interfaces\Services\User\UserServiceInterface::class => \App\Http\Services\User\UserService::class,

        \App\Interfaces\Repositories\Attendance\AttendanceRepositoryInterface::class => \App\Repositories\AttendanceRepository::class,
        \App\Interfaces\Services\Attendance\AttendanceServiceInterface::class => \App\Http\Services\Attendance\AttendanceService::class,

        \App\Interfaces\Repositories\JobPositionRepositoryInterface::class => \App\Http\Repositories\JobPositionRepository::class,
        \App\Interfaces\Services\JobPositionServiceInterface::class => \App\Http\Services\JobPositionService::class,

        \App\Interfaces\Repositories\JobLevelRepositoryInterface::class => \App\Http\Repositories\JobLevelRepository::class,
        \App\Interfaces\Services\JobLevelServiceInterface::class => \App\Http\Services\JobLevelService::class,

        \App\Interfaces\Repositories\Payroll\RunPayrollRepositoryInterface::class => \App\Http\Repositories\Payroll\RunPayrollRepository::class,
        \App\Interfaces\Services\Payroll\RunPayrollServiceInterface::class => \App\Http\Services\Payroll\RunPayrollService::class,

        \App\Interfaces\Repositories\Shift\ShiftRepositoryInterface::class => \App\Http\Repositories\Shift\ShiftRepository::class,
        \App\Interfaces\Services\Shift\ShiftServiceInterface::class => \App\Http\Services\Shift\ShiftService::class,

        \App\Interfaces\Repositories\SupervisorRequestSchedule\SupervisorRequestScheduleRepositoryInterface::class => \App\Http\Repositories\SupervisorRequestSchedule\SupervisorRequestScheduleRepository::class,
        \App\Interfaces\Services\SupervisorRequestSchedule\SupervisorRequestScheduleServiceInterface::class => \App\Http\Services\SupervisorRequestSchedule\SupervisorRequestScheduleService::class,

        \App\Interfaces\Repositories\SupervisorType\SupervisorTypeRepositoryInterface::class => \App\Http\Repositories\SupervisorType\SupervisorTypeRepository::class,
        \App\Interfaces\Services\SupervisorType\SupervisorTypeServiceInterface::class => \App\Http\Services\SupervisorType\SupervisorTypeService::class,

        \App\Interfaces\Repositories\Task\TaskRepositoryInterface::class => \App\Http\Repositories\Task\TaskRepository::class,
        \App\Interfaces\Services\Task\TaskServiceInterface::class => \App\Http\Services\Task\TaskService::class,

        \App\Interfaces\Repositories\TaskHour\TaskHourRepositoryInterface::class => \App\Http\Repositories\TaskHour\TaskHourRepository::class,
        \App\Interfaces\Services\TaskHour\TaskHourServiceInterface::class => \App\Http\Services\TaskHour\TaskHourService::class,

        \App\Interfaces\Repositories\TimeoffPolicy\TimeoffPolicyRepositoryInterface::class => \App\Http\Repositories\TimeoffPolicy\TimeoffPolicyRepository::class,
        \App\Interfaces\Services\TimeoffPolicy\TimeoffPolicyServiceInterface::class => \App\Http\Services\TimeoffPolicy\TimeoffPolicyService::class,

        \App\Interfaces\Repositories\UserCustomField\UserCustomFieldRepositoryInterface::class => \App\Http\Repositories\UserCustomField\UserCustomFieldRepository::class,
        \App\Interfaces\Services\UserCustomField\UserCustomFieldServiceInterface::class => \App\Http\Services\UserCustomField\UserCustomFieldService::class,

        \App\Interfaces\Repositories\UserEducation\UserEducationRepositoryInterface::class => \App\Http\Repositories\UserEducation\UserEducationRepository::class,
        \App\Interfaces\Services\UserEducation\UserEducationServiceInterface::class => \App\Http\Services\UserEducation\UserEducationService::class,

        \App\Interfaces\Repositories\UserExperience\UserExperienceRepositoryInterface::class => \App\Http\Repositories\UserExperience\UserExperienceRepository::class,
        \App\Interfaces\Services\UserExperience\UserExperienceServiceInterface::class => \App\Http\Services\UserExperience\UserExperienceService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Notification::extend('fcm', function ($app) {
            return new FcmChannel();
        });
    }
}

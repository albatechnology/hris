<?php

namespace App\Providers;

use App\Broadcasting\FcmChannel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        \App\Interfaces\Repositories\BankRepositoryInterface::class => \App\Http\Repositories\BankRepository::class,
        \App\Interfaces\Services\BankServiceInterface::class => \App\Http\Services\BankService::class,

        \App\Interfaces\Repositories\Branch\BranchRepositoryInterface::class => \App\Http\Repositories\Branch\BranchRepository::class,
        \App\Interfaces\Services\Branch\BranchServiceInterface::class => \App\Http\Services\Branch\BranchService::class,

        \App\Interfaces\Repositories\Company\CompanyRepositoryInterface::class => \App\Http\Repositories\Company\CompanyRepository::class,
        \App\Interfaces\Services\Company\CompanyServiceInterface::class => \App\Http\Services\Company\CompanyService::class,

        \App\Interfaces\Repositories\DailyActivity\DailyActivityRepositoryInterface::class => \App\Http\Repositories\DailyActivity\DailyActivityRepository::class,
        \App\Interfaces\Services\DailyActivity\DailyActivityServiceInterface::class => \App\Http\Services\DailyActivity\DailyActivityService::class,

        \App\Interfaces\Repositories\ReimbursementCategory\ReimbursementCategoryRepositoryInterface::class => \App\Http\Repositories\ReimbursementCategory\ReimbursementCategoryRepository::class,
        \App\Interfaces\Services\ReimbursementCategory\ReimbursementCategoryServiceInterface::class => \App\Http\Services\ReimbursementCategory\ReimbursementCategoryService::class,

        \App\Interfaces\Repositories\Reimbursement\ReimbursementRepositoryInterface::class => \App\Http\Repositories\Reimbursement\ReimbursementRepository::class,
        \App\Interfaces\Services\Reimbursement\ReimbursementServiceInterface::class => \App\Http\Services\Reimbursement\ReimbursementService::class,

        \App\Interfaces\Repositories\Subscription\SubscriptionRepositoryInterface::class => \App\Http\Repositories\Subscription\SubscriptionRepository::class,
        \App\Interfaces\Services\Subscription\SubscriptionServiceInterface::class => \App\Http\Services\Subscription\SubscriptionService::class,

        \App\Interfaces\Repositories\User\UserRepositoryInterface::class => \App\Http\Repositories\User\UserRepository::class,
        \App\Interfaces\Services\User\UserServiceInterface::class => \App\Http\Services\User\UserService::class,

        \App\Interfaces\Repositories\Attendance\AttendanceRepositoryInterface::class => \App\Repositories\AttendanceRepository::class,
        \App\Interfaces\Services\Attendance\AttendanceServiceInterface::class => \App\Http\Services\Attendance\AttendanceService::class,

        \App\Interfaces\Repositories\Level\LevelRepositoryInterface::class => \App\Http\Repositories\Level\LevelRepository::class,
        \App\Interfaces\Services\Level\LevelServiceInterface::class => \App\Http\Services\Level\LevelService::class,
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

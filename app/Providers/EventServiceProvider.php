<?php

namespace App\Providers;

use App\Events\Attendance\AttendanceRequested;
use App\Events\Subscription\SubscriptionCreated;
use App\Listeners\Attendance\RequestAttendanceNotification;
use App\Listeners\Subscription\SendSubscriptionEmail;
use App\Models\Company;
use App\Models\Loan;
use App\Models\OvertimeRequest;
use App\Models\RunPayroll;
use App\Models\RunThr;
use App\Models\User;
use App\Observers\CompanyObserver;
use App\Observers\LoanObserver;
use App\Observers\OvertimeRequestObserver;
use App\Observers\RunPayrollObserver;
use App\Observers\RunThrObserver;
use App\Observers\UserObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        AttendanceRequested::class => [
            RequestAttendanceNotification::class,
        ],
        SubscriptionCreated::class => [
            SendSubscriptionEmail::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        OvertimeRequest::observe(OvertimeRequestObserver::class);
        Company::observe(CompanyObserver::class);
        // TimeoffRegulation::observe(TimeoffRegulationObserver::class);
        User::observe(UserObserver::class);
        RunPayroll::observe(RunPayrollObserver::class);
        RunThr::observe(RunThrObserver::class);
        Loan::observe(LoanObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

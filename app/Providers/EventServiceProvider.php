<?php

namespace App\Providers;

use App\Events\Attendance\AttendanceRequested;
use App\Listeners\Attendance\RequestAttendanceNotification;
use App\Models\Company;
use App\Models\OvertimeRequest;
use App\Models\TimeoffRegulation;
use App\Models\User;
use App\Observers\CompanyObserver;
use App\Observers\OvertimeRequestObserver;
use App\Observers\TimeoffRegulationObserver;
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
            RequestAttendanceNotification::class
        ]
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        OvertimeRequest::observe(OvertimeRequestObserver::class);
        Company::observe(CompanyObserver::class);
        TimeoffRegulation::observe(TimeoffRegulationObserver::class);
        User::observe(UserObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

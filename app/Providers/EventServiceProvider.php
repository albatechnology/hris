<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\OvertimeRequest;
use App\Models\TimeoffRegulation;
use App\Observers\CompanyObserver;
use App\Observers\OvertimeRequestObserver;
use App\Observers\TimeoffRegulationObserver;
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
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        OvertimeRequest::observe(OvertimeRequestObserver::class);
        Company::observe(CompanyObserver::class);
        TimeoffRegulation::observe(TimeoffRegulationObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

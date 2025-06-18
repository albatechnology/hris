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

        \App\Interfaces\Repositories\Subscription\SubscriptionRepositoryInterface::class => \App\Http\Repositories\Subscription\SubscriptionRepository::class,
        \App\Interfaces\Services\Subscription\SubscriptionServiceInterface::class => \App\Http\Services\Subscription\SubscriptionService::class,

        \App\Interfaces\Repositories\Company\CompanyRepositoryInterface::class => \App\Http\Repositories\Company\CompanyRepository::class,
        \App\Interfaces\Services\Company\CompanyServiceInterface::class => \App\Http\Services\Company\CompanyService::class,
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

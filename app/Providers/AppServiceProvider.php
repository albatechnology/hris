<?php

namespace App\Providers;

use App\Broadcasting\FcmChannel;
use App\Http\Repositories\BankRepository;
use App\Http\Repositories\SubscriptionRepository;
use App\Http\Services\BankService;
use App\Http\Services\SubscriptionService;
use App\Interfaces\Repositories\BankRepositoryInterface;
use App\Interfaces\Repositories\SubscriptionRepositoryInterface;
use App\Interfaces\Services\BankServiceInterface;
use App\Interfaces\Services\SubscriptionServiceInterface;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        BankRepositoryInterface::class => BankRepository::class,
        BankServiceInterface::class => BankService::class,
        SubscriptionRepositoryInterface::class => SubscriptionRepository::class,
        SubscriptionServiceInterface::class => SubscriptionService::class,
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

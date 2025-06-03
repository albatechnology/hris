<?php

namespace App\Providers;

use App\Broadcasting\FcmChannel;
use App\Http\Repositories\BankRepository;
use App\Http\Services\BankService;
use App\Interfaces\Repositories\BankRepositoryInterface;
use App\Interfaces\Services\BankServiceInterface;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BankRepositoryInterface::class, BankRepository::class);
        $this->app->bind(BankServiceInterface::class, BankService::class);
    }

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

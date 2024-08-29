<?php

namespace App\Providers;

use App\Broadcasting\FcmChannel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (file_exists(config('firebase.projects.' . config('firebase.default') . '.credentials'))) {
            Notification::extend('fcm', function ($app) {
                return new FcmChannel();
            });
        } else {
            abort(500, 'Invalid firebase credentials');
        }
    }
}

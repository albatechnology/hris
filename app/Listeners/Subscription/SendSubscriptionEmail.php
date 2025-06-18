<?php

namespace App\Listeners\Subscription;

use App\Events\Subscription\SubscriptionCreated;
use App\Mail\SubscriptionCreatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendSubscriptionEmail implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SubscriptionCreated $event): void
    {
        Mail::to($event->subscription->user)->send(new SubscriptionCreatedMail($event->subscription));
    }
}

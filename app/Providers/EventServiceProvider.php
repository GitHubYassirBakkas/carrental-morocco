<?php

namespace App\Providers;

use App\Events\BookingCompleted;
use App\Listeners\LogSentEmail;
use App\Listeners\SendCouponRewardNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSent;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MessageSent::class => [
            LogSentEmail::class,
        ],
        BookingCompleted::class => [
            SendCouponRewardNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}

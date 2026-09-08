<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSent;
use App\Listeners\LogSentEmail;
use App\Events\BookingCompleted;
use App\Listeners\SendCouponRewardNotification;

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
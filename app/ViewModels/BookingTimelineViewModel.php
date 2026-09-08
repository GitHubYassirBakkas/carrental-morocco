<?php

namespace App\ViewModels;

final readonly class BookingTimelineViewModel
{
    /**
     * @param  TimelineEventViewModel[]  $events
     */
    public function __construct(
        public array $events
    ) {}
}

<?php

namespace App\ViewModels;

final readonly class TimelineEventViewModel
{
    public function __construct(
        public string $title,
        public string $description,
        public \Carbon\Carbon $date,
        public string $status,
        public string $icon,
        public bool $completed
    ) {}
}

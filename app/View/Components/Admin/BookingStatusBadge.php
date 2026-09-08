<?php

namespace App\View\Components\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class BookingStatusBadge extends Component
{
    public function __construct(public readonly string $status) {}

    public function render(): View
    {
        return view('components.admin.booking-status-badge');
    }

    public function label(): string
    {
        $key = 'messages.statuses.'.$this->status;
        $label = __($key);

        if ($label === $key) {
            return Str::of($this->status)
                ->replace('_', ' ')
                ->headline()
                ->toString();
        }

        return $label;
    }

    public function isCancelled(): bool
    {
        return in_array($this->status, ['cancelled', 'canceled'], true);
    }
}

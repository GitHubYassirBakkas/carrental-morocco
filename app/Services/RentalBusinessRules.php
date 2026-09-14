<?php

namespace App\Services;

use App\Models\Car;
use App\Models\User;
use Carbon\Carbon;

class RentalBusinessRules
{
    public function bookingMinDays(): int
    {
        return max(1, (int) setting('booking_min_days', config('rental.min_days', 1)));
    }

    public function bookingMaxDays(): int
    {
        return max($this->bookingMinDays(), (int) setting('booking_max_days', config('rental.max_days', 30)));
    }

    public function maxAdvanceBookingDays(): int
    {
        return max(0, (int) setting('max_advance_booking_days', config('rental.max_advance_booking_days', 90)));
    }

    public function maxAdvancePickupDate(): Carbon
    {
        return now()->copy()->startOfDay()->addDays($this->maxAdvanceBookingDays());
    }

    public function exceedsMaxAdvanceDate(Carbon $start): bool
    {
        return $start->copy()->startOfDay()->gt($this->maxAdvancePickupDate());
    }

    public function advancePaymentDeadlineHours(): int
    {
        return max(1, (int) setting(
            'advance_payment_deadline_hours',
            config('rental.advance_payment_deadline_hours', 24)
        ));
    }

    public function advancePaymentDueAt(?Carbon $from = null): Carbon
    {
        return ($from ?? now())->copy()->addHours($this->advancePaymentDeadlineHours());
    }

    public function globalMinimumDriverAge(): int
    {
        return max(18, (int) setting('min_driver_age', config('rental.min_driver_age', 21)));
    }

    public function effectiveMinimumDriverAge(Car $car): int
    {
        return max($this->globalMinimumDriverAge(), (int) ($car->minimum_age ?? 0));
    }

    public function driverAgeFor(User $user): ?int
    {
        $user->loadMissing('customerProfile');

        return $user->customerProfile?->age;
    }

    public function driverMeetsMinimumAge(User $user, Car $car): bool
    {
        $age = $this->driverAgeFor($user);

        return $age !== null && $age >= $this->effectiveMinimumDriverAge($car);
    }
}

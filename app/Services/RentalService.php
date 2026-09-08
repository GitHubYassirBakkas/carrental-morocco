<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;

class RentalService
{
    public function start(Booking $booking)
    {
        if (!$booking->canStart()) {
            throw new \Exception('Cannot start rental.');
        }

        $booking->update([
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    public function complete(Booking $booking)
    {
        if (!$booking->canComplete()) {
            throw new \Exception('Cannot complete rental.');
        }

        $booking->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $booking->car->update([
            'is_available' => true
        ]);
    }

    public function calculateCheckout(Booking $booking, int $returnFuel)
    {
        $pickupFuel = $booking->fuel_at_pickup_percent ?? 0;
        $fuelUsed = max(0, $pickupFuel - $returnFuel);

        $fuelCharge = $fuelUsed * config('rental.fuel_price_per_percent');

        $lateMinutes = now()->greaterThan($booking->end_date)
            ? $booking->end_date->diffInMinutes(now())
            : 0;

        $lateFee = ceil($lateMinutes / 60) * config('rental.late_fee_per_hour');

        return [
            'fuel_used' => $fuelUsed,
            'fuel_charge' => $fuelCharge,
            'late_minutes' => $lateMinutes,
            'late_fee' => $lateFee,
        ];
    }
}








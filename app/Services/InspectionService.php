<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InspectionService
{
    public function handle(Booking $booking, array $data)
    {
        return DB::transaction(function () use ($booking, $data) {

            if ($booking->inspections()->where('type', $data['type'])->exists()) {
                throw new \Exception('Inspection already exists.');
            }

            $inspection = $booking->inspections()->create([
                'type' => $data['type'],
                'mileage' => $data['mileage'],
                'fuel_level' => $data['fuel_level'],
                'has_damage' => !empty($data['damage_notes']),
                'damage_notes' => $data['damage_notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            if ($data['type'] === 'checkin') {
                $this->handleCheckin($booking, $data);
            }

                if ($data['type'] === 'checkout') {

            if ($data['mileage'] < $booking->checkinInspection->mileage) {
                throw new \Exception('Mileage cannot be less than check-in mileage.');
            }
            $this->handleCheckout($booking, $data); // ← هادي كانت ناقصة
        }

            return $inspection;
        });
    }

    private function handleCheckin(Booking $booking, array $data)
    {
        $booking->update([
            'fuel_at_pickup_percent' => $data['fuel_level'],
            'status' => 'active'
        ]);
    }

    private function handleCheckout(Booking $booking, array $data)
    {
        $pickupFuel = $booking->fuel_at_pickup_percent ?? 0;
        $returnFuel = $data['fuel_level'];

        $fuelUsed = max(0, $pickupFuel - $returnFuel);

        $fuelCharge = $fuelUsed * config('rental.fuel_price_per_percent');

        $lateData = $this->calculateLateFee($booking);

        $booking->update([
            'fuel_at_return_percent' => $returnFuel,
            'fuel_used' => $fuelUsed,
            'fuel_charge' => $fuelCharge,
            'late_minutes' => $lateData['minutes'],
            'late_fee' => $lateData['fee'],
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    private function calculateLateFee(Booking $booking)
    {
        $now = Carbon::now();

        if ($now->lessThanOrEqualTo($booking->end_date)) {
            return ['minutes' => 0, 'fee' => 0];
        }

        $lateMinutes = $booking->end_date->diffInMinutes($now);

        $hoursLate = ceil($lateMinutes / 60);

        $fee = $hoursLate * config('rental.late_fee_per_hour');

        return [
            'minutes' => $lateMinutes,
            'fee' => $fee,
        ];
    }
}

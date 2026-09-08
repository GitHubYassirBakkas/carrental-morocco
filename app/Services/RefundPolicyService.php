<?php

namespace App\Services;

use App\Models\Booking;
use Carbon\Carbon;

class RefundPolicyService
{
    public function calculateRefund(Booking $booking): float
    {
        $invoice = $booking->invoice;

        if (!$invoice || $invoice->paid_amount <= 0) {
            return 0;
        }

        $paidAmount = $invoice->paid_amount;

        // 🟢 DURING RENTAL (Early Return)
        if ($booking->status === 'active') {

            $totalDays = max(1, $booking->start_date->diffInDays($booking->end_date));
            $usedDays = ceil($booking->start_date->diffInHours(now()) / 24);
            $usedDays = min($usedDays, $totalDays);

            $dailyRate = $booking->total_amount / $totalDays;
            $usedAmount = $usedDays * $dailyRate;

            return max(0, $paidAmount - $usedAmount);
        }

        // 🟢 BEFORE PICKUP
        $hoursBeforePickup = now()
            ->utc()
            ->diffInHours($booking->start_date->utc(), false);

        if ($hoursBeforePickup > 48) {
            return $paidAmount; // Full refund
        }

        if ($hoursBeforePickup >= 0) {
            $deposit = min($booking->deposit_amount, $paidAmount);
            return max(0, $paidAmount - $deposit);
        }

        return 0; // No-show
    }
}
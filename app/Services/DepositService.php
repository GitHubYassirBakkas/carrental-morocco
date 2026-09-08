<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;

class DepositService
{
    /**
     * Check if booking has reached minimum deposit
     *
     * @param Booking $booking
     * @return bool
     */
    public function checkMinimumDeposit(Booking $booking): bool
    {
        if (!$booking->invoice) {
            return false;
        }

        $minimumDeposit = $this->calculateMinimumDeposit($booking);
        $paidAmount = $booking->invoice->paid_amount;

        return $paidAmount >= $minimumDeposit;
    }

    /**
     * Calculate minimum deposit amount
     *
     * @param Booking $booking
     * @return float
     */
    public function calculateMinimumDeposit(Booking $booking): float
    {
        $percentage = setting('deposit_percentage', 30);
        return ($booking->total_amount * $percentage) / 100;
    }

    /**
     * Calculate deposit progress percentage
     *
     * @param Booking $booking
     * @return float
     */
    public function calculateDepositProgress(Booking $booking): float
    {
        if (!$booking->invoice) {
            return 0;
        }

        $minimumDeposit = $this->calculateMinimumDeposit($booking);
        
        if ($minimumDeposit == 0) {
            return 0;
        }

        $paidAmount = $booking->invoice->paid_amount;
        
        return min(100, ($paidAmount / $minimumDeposit) * 100);
    }

    /**
     * Check if deposit deadline has passed
     *
     * @param Booking $booking
     * @return bool
     */
    public function isDepositOverdue(Booking $booking): bool
    {
        return !$booking->deposit_paid 
            && $booking->deposit_due_at 
            && now()->isAfter($booking->deposit_due_at);
    }

    /**
     * Confirm booking if minimum deposit reached
     *
     * @param Booking $booking
     * @param float $newPaidAmount
     * @return bool Returns true if booking was confirmed
     */
    public function confirmBookingIfDepositReached(Booking $booking, float $newPaidAmount): bool
    {
        // Only process if booking is pending
        if ($booking->status !== 'pending') {
            return false;
        }

        $minimumDeposit = $this->calculateMinimumDeposit($booking);

        // Check if minimum deposit reached
        if ($newPaidAmount >= $minimumDeposit) {
            $booking->update([
                'deposit_paid' => true,
                'deposit_paid_at' => now(),
                'status' => 'confirmed'
            ]);

            Log::info('Booking confirmed via deposit', [
                'booking_id' => $booking->id,
                'paid_amount' => $newPaidAmount,
                'minimum_deposit' => $minimumDeposit
            ]);

            return true;
        }

        return false;
    }

    /**
     * Get remaining deposit amount needed
     *
     * @param Booking $booking
     * @return float
     */
    public function getRemainingDeposit(Booking $booking): float
    {
        $minimumDeposit = $this->calculateMinimumDeposit($booking);
        $paidAmount = $booking->invoice ? $booking->invoice->paid_amount : 0;
        
        return max(0, $minimumDeposit - $paidAmount);
    }
}

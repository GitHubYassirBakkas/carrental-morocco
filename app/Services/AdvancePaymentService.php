<?php

namespace App\Services;

use App\Models\Booking;
use App\Services\Pricing\BookingPricingService;
use Illuminate\Support\Facades\Log;

class AdvancePaymentService
{
    public function __construct(private readonly BookingPricingService $pricingService)
    {
    }

    /**
     * Check if booking has reached the required advance payment.
     *
     * @param Booking $booking
     * @return bool
     */
    public function checkMinimumAdvancePayment(Booking $booking): bool
    {
        if (!$booking->invoice) {
            return false;
        }

        $minimumAdvancePayment = $this->calculateMinimumAdvancePayment($booking);
        $paidAmount = $booking->invoice->paid_amount;

        return $paidAmount >= $minimumAdvancePayment;
    }

    /**
     * Calculate minimum advance payment amount.
     *
     * @param Booking $booking
     * @return float
     */
    public function calculateMinimumAdvancePayment(Booking $booking): float
    {
        return $this->pricingService->calculateAdvancePayment($booking);
    }

    /**
     * Calculate advance payment progress percentage.
     *
     * @param Booking $booking
     * @return float
     */
    public function calculateAdvancePaymentProgress(Booking $booking): float
    {
        if (!$booking->invoice) {
            return 0;
        }

        $minimumAdvancePayment = $this->calculateMinimumAdvancePayment($booking);
        
        $paidAmount = $booking->invoice->paid_amount;

        return $this->pricingService->calculateAdvancePaymentProgress(
            (float) $paidAmount,
            (float) $minimumAdvancePayment
        );
    }

    /**
     * Check if advance payment deadline has passed.
     *
     * @param Booking $booking
     * @return bool
     */
    public function isAdvancePaymentOverdue(Booking $booking): bool
    {
        return !$booking->isAdvancePaymentPaid()
            && $booking->advance_payment_due_at
            && now()->isAfter($booking->advance_payment_due_at);
    }

    /**
     * Confirm booking if minimum advance payment reached.
     *
     * @param Booking $booking
     * @param float $newPaidAmount
     * @return bool Returns true if booking was confirmed
     */
    public function confirmBookingIfAdvancePaymentReached(Booking $booking, float $newPaidAmount): bool
    {
        // Only process if booking is pending
        if (!$booking->isPending()) {
            return false;
        }

        $minimumAdvancePayment = $this->calculateMinimumAdvancePayment($booking);

        if ($newPaidAmount >= $minimumAdvancePayment) {
            $booking->update([
                'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PAID,
                'advance_payment_paid_at' => now(),
                'status' => Booking::STATUS_CONFIRMED
            ]);

            Log::info('Booking confirmed via advance payment', [
                'booking_id' => $booking->id,
                'paid_amount' => $newPaidAmount,
                'minimum_advance_payment' => $minimumAdvancePayment
            ]);

            return true;
        }

        return false;
    }

    /**
     * Get remaining advance payment amount needed.
     *
     * @param Booking $booking
     * @return float
     */
    public function getRemainingAdvancePayment(Booking $booking): float
    {
        $minimumAdvancePayment = $this->calculateMinimumAdvancePayment($booking);
        $paidAmount = $booking->invoice ? $booking->invoice->paid_amount : 0;
        
        return $this->pricingService->calculateRemainingAdvancePayment(
            (float) $minimumAdvancePayment,
            (float) $paidAmount
        );
    }
}

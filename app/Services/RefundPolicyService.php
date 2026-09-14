<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;

class RefundPolicyService
{
    public const FALLBACK_REFUND_METHODS = ['cash', 'bank_transfer'];

    /**
     * Determine if a customer can cancel a booking.
     */
    public function canCustomerCancel(Booking $booking): bool
    {
        $customerCancellableStatuses = array_intersect(
            Booking::CANCELLABLE_STATUSES,
            [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED]
        );

        return in_array($booking->status, $customerCancellableStatuses, true);
    }

    /**
     * Determine if an admin can cancel a booking.
     */
    public function canAdminCancel(Booking $booking): bool
    {
        // Admin can cancel any cancellable status
        return in_array($booking->status, Booking::CANCELLABLE_STATUSES, true);
    }

    /**
     * Determine if a booking is eligible for any refund.
     */
    public function isRefundEligible(Booking $booking): bool
    {
        if (! $booking->invoice) {
            return false;
        }

        $paidAmount = $this->calculatePaidAmount($booking->invoice);

        return $paidAmount > 0;
    }

    /**
     * Determine the refund type based on the refund amount.
     *
     * @return string 'full'|'partial'|'none'
     */
    public function determineRefundType(Invoice $invoice, float $refundAmount): string
    {
        if ($refundAmount <= 0) {
            return 'none';
        }

        $originalAmount = $invoice->payments()
            ->where('type', Payment::TYPE_PAYMENT)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        if ($refundAmount >= $originalAmount) {
            return 'full';
        }

        return 'partial';
    }

    /**
     * Calculate the refund amount based on business rules.
     */
    public function calculateRefundAmount(Booking $booking, float $paidAmount): float
    {
        if ($paidAmount <= 0) {
            return 0.0;
        }

        // Rule 5: Early termination (rental already active)
        if ($booking->isActive()) {
            return $this->calculateEarlyTerminationRefund($booking, $paidAmount);
        }

        $hoursBeforePickup = $this->calculateHoursBeforePickup($booking);

        if ($hoursBeforePickup >= $this->fullRefundBeforePickupHours()) {
            return $this->calculateFullRefund($booking, $paidAmount);
        }

        if ($hoursBeforePickup >= $this->partialRefundUntilPickupHours()) {
            return $this->calculatePartialRefund($booking, $paidAmount);
        }

        return $this->calculateNoRefund();
    }

    /**
     * Determine the refund method.
     *
     * Priority: Original payment method > Fallback method > null
     *
     * @return string|null 'cash'|'card'|'bank_transfer'|null
     */
    public function determineRefundMethod(Booking $booking): ?string
    {
        if (! $booking->invoice) {
            return null;
        }

        // Priority 1: Original payment method
        $payment = $booking->invoice->payments()
            ->where('type', Payment::TYPE_PAYMENT)
            ->oldest()
            ->first();

        if ($payment && $payment->method) {
            return $payment->method;
        }

        // Priority 2: Manual fallback method when the original channel is unknown.
        $fallback = (string) Setting::get('refund_default_method', 'cash');

        return in_array($fallback, self::FALLBACK_REFUND_METHODS, true)
            ? $fallback
            : 'cash';
    }

    /**
     * Evaluate the complete cancellation policy for a booking.
     *
     * @param  string  $actor  'customer'|'admin'
     */
    public function evaluateCancellation(Booking $booking, string $actor = 'customer'): array
    {
        $canCancel = $actor === 'customer'
            ? $this->canCustomerCancel($booking)
            : $this->canAdminCancel($booking);

        $refundEligible = $this->isRefundEligible($booking);

        $paidAmount = $booking->invoice ? $this->calculatePaidAmount($booking->invoice) : 0;
        $refundAmount = $refundEligible ? $this->calculateRefundAmount($booking, $paidAmount) : 0.0;

        $refundType = $booking->invoice ? $this->determineRefundType($booking->invoice, $refundAmount) : 'none';
        $refundMethod = $this->determineRefundMethod($booking);
        $hoursBeforePickup = $this->calculateHoursBeforePickup($booking);
        $fullRefundDeadline = $booking->start_date?->copy()->subHours($this->fullRefundBeforePickupHours());

        return [
            'can_cancel' => $canCancel,
            'refund_eligible' => $refundEligible,
            'refund_type' => $refundType,
            'refund_amount' => $refundAmount,
            'refund_method' => $refundMethod,
            'policy_reason' => $this->generatePolicyReason($booking, $canCancel, $refundType, $refundAmount),
            'hours_before_pickup' => $hoursBeforePickup,
            'cancellation_deadline' => $fullRefundDeadline,
            'refund_window_started_at' => null,
            'refund_window_ends_at' => $fullRefundDeadline,
            'hours_since_confirmation' => null,
            'within_full_refund_window' => ! $booking->isActive()
                && $hoursBeforePickup >= $this->fullRefundBeforePickupHours(),
        ];
    }

    /**
     * Calculate hours before pickup (can be negative if after pickup).
     *
     * @return int
     */
    private function calculateHoursBeforePickup(Booking $booking): int|float
    {
        // Use UTC for consistent timezone handling (app timezone is UTC)
        return now()->utc()->diffInHours($booking->start_date->copy()->utc(), false);
    }

    /**
     * Calculate paid amount for an invoice.
     */
    private function calculatePaidAmount(Invoice $invoice): float
    {
        return (float) $invoice->paid_amount;
    }

    /**
     * Calculate full refund amount.
     *
     * Rule 1: Full refund before the configured pickup cutoff.
     */
    private function calculateFullRefund(Booking $booking, float $paidAmount): float
    {
        return $paidAmount;
    }

    /**
     * Calculate partial refund amount.
     *
     * Rule 2: Partial refund using configured percentage.
     */
    private function calculatePartialRefund(Booking $booking, float $paidAmount): float
    {
        $percentage = min(100, max(0, (float) Setting::get('refund_partial_percentage', 50)));

        return round(max(0, $paidAmount) * ($percentage / 100), 2);
    }

    /**
     * Rule 3: No refund inside the configured pickup cutoff.
     */
    private function calculateNoRefund(): float
    {
        return 0.0;
    }

    /**
     * Calculate early termination refund amount.
     *
     * Rule 5: Early termination when rental is active.
     * Formula: paidAmount - (usedDays × dailyRate)
     */
    private function calculateEarlyTerminationRefund(Booking $booking, float $paidAmount): float
    {
        $totalDays = max(1, $booking->start_date->diffInDays($booking->end_date));
        $usedDays = ceil($booking->start_date->diffInHours(now()) / 24);
        $usedDays = min($usedDays, $totalDays);
        $averageAmountPerDay = (float) $booking->total_amount / $totalDays;
        $usedAmount = $usedDays * $averageAmountPerDay;

        return max(0, $paidAmount - $usedAmount);
    }

    /**
     * Generate a human-readable policy reason.
     */
    private function generatePolicyReason(Booking $booking, bool $canCancel, string $refundType, float $refundAmount): string
    {
        if (! $canCancel) {
            return 'Cancellation not permitted: booking status or time restriction.';
        }

        if ($refundAmount <= 0) {
            return 'No refund: booking not eligible for refund.';
        }

        if ($booking->isActive()) {
            return 'Early termination: refund based on actual rental usage.';
        }

        $fullRefundHours = $this->fullRefundBeforePickupHours();
        $partialRefundHours = $this->partialRefundUntilPickupHours();
        $hoursBeforePickup = $this->calculateHoursBeforePickup($booking);

        if ($hoursBeforePickup >= $fullRefundHours) {
            return "Full refund: cancelled at least {$fullRefundHours} hours before pickup.";
        }

        if ($hoursBeforePickup >= $partialRefundHours) {
            return "Partial refund: cancelled between {$partialRefundHours} and {$fullRefundHours} hours before pickup.";
        }

        return "No refund: cancellation made less than {$partialRefundHours} hours before pickup.";
    }

    private function fullRefundBeforePickupHours(): int
    {
        return max(1, (int) Setting::get('refund_cancellation_window_hours', 48));
    }

    private function partialRefundUntilPickupHours(): int
    {
        return max(0, (int) Setting::get('refund_partial_refund_cutoff_hours', 24));
    }
}

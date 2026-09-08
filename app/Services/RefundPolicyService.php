<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingStateTransition;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use Carbon\Carbon;

class RefundPolicyService
{
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

        // Rule 1: Full refund during the payment confirmation grace period.
        if ($this->isWithinFullRefundGracePeriod($booking)) {
            return $this->calculateFullRefund($booking, $paidAmount);
        }

        // Rule 2: Partial refund after the payment confirmation grace period.
        return $this->calculatePartialRefund($booking, $paidAmount);
    }

    /**
     * Determine the refund method.
     *
     * Priority: Original payment method > Fallback method > null
     *
     * @return string|null 'cash'|'card'|null
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

        // Priority 2: Fallback method
        return Setting::get('refund_default_method', 'cash');
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
        $refundWindowStart = $this->resolveRefundWindowStart($booking);
        $refundWindowEnd = $this->calculateRefundWindowEnd($refundWindowStart);

        return [
            'can_cancel' => $canCancel,
            'refund_eligible' => $refundEligible,
            'refund_type' => $refundType,
            'refund_amount' => $refundAmount,
            'refund_method' => $refundMethod,
            'policy_reason' => $this->generatePolicyReason($booking, $canCancel, $refundType, $refundAmount),
            'hours_before_pickup' => $this->calculateHoursBeforePickup($booking),
            'cancellation_deadline' => $refundWindowEnd,
            'refund_window_started_at' => $refundWindowStart,
            'refund_window_ends_at' => $refundWindowEnd,
            'hours_since_confirmation' => $this->calculateHoursSinceConfirmation($refundWindowStart),
            'within_full_refund_window' => $this->isWithinFullRefundGracePeriod($booking, $refundWindowStart),
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
     * Resolve when the 48-hour full-refund grace period starts.
     */
    private function resolveRefundWindowStart(Booking $booking): Carbon
    {
        $completedPaymentPaidAt = $this->completedRentalPaymentsQuery($booking)
            ?->whereNotNull('paid_at')
            ->orderBy('paid_at')
            ->orderBy('created_at')
            ->value('paid_at');

        if ($completedPaymentPaidAt) {
            return Carbon::parse($completedPaymentPaidAt);
        }

        if ($booking->advance_payment_paid_at) {
            return $booking->advance_payment_paid_at->copy();
        }

        $completedPaymentCreatedAt = $this->completedRentalPaymentsQuery($booking)
            ?->whereNotNull('created_at')
            ->oldest('created_at')
            ->value('created_at');

        if ($completedPaymentCreatedAt) {
            return Carbon::parse($completedPaymentCreatedAt);
        }

        $confirmedTransitionCreatedAt = BookingStateTransition::query()
            ->where('booking_id', $booking->id)
            ->where('from_status', Booking::STATUS_PENDING)
            ->where('to_status', Booking::STATUS_CONFIRMED)
            ->where('accepted', true)
            ->oldest('created_at')
            ->value('created_at');

        if ($confirmedTransitionCreatedAt) {
            return Carbon::parse($confirmedTransitionCreatedAt);
        }

        return $booking->created_at?->copy() ?? now();
    }

    private function completedRentalPaymentsQuery(Booking $booking)
    {
        if (! $booking->invoice) {
            return null;
        }

        return $booking->invoice->payments()
            ->where('type', Payment::TYPE_PAYMENT)
            ->where('status', Payment::STATUS_COMPLETED);
    }

    private function calculateRefundWindowEnd(Carbon $refundWindowStart): Carbon
    {
        return $refundWindowStart->copy()
            ->addHours((int) Setting::get('refund_cancellation_window_hours', 48));
    }

    private function isWithinFullRefundGracePeriod(Booking $booking, ?Carbon $refundWindowStart = null): bool
    {
        $refundWindowStart ??= $this->resolveRefundWindowStart($booking);

        return now()->lessThanOrEqualTo($this->calculateRefundWindowEnd($refundWindowStart));
    }

    private function calculateHoursSinceConfirmation(Carbon $refundWindowStart): int|float
    {
        return $refundWindowStart->copy()->utc()->diffInHours(now()->utc(), false);
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
     * Rule 1: Full refund during the payment confirmation grace period.
     */
    private function calculateFullRefund(Booking $booking, float $paidAmount): float
    {
        return $paidAmount;
    }

    /**
     * Calculate partial refund amount.
     *
     * Rule 2 & 3: Partial refund using existing advance payment model.
     * Formula: paidAmount - advancePayment
     */
    private function calculatePartialRefund(Booking $booking, float $paidAmount): float
    {
        $advancePayment = min((float) $booking->advance_payment_amount, $paidAmount);

        return max(0, $paidAmount - $advancePayment);
    }

    /**
     * Calculate refund amount for after pickup / no-show.
     *
     * Rule 4: No refund after pickup time.
     */
    private function calculateAfterPickupRefund(Booking $booking, float $paidAmount): float
    {
        $noRefundEnabled = Setting::get('refund_no_refund_enabled', true);

        if ($noRefundEnabled) {
            return 0.0;
        }

        // If no-refund is disabled, keep existing behavior (no refund)
        // Document: Setting disabled does not change behavior in Phase 3.6
        // Future enhancement could enable partial refunds after pickup
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

        $cancellationWindow = (int) Setting::get('refund_cancellation_window_hours', 48);

        if ($this->isWithinFullRefundGracePeriod($booking)) {
            return "Full refund: cancelled within the {$cancellationWindow}-hour payment confirmation grace period.";
        }

        return "Partial refund: cancellation made after the {$cancellationWindow}-hour payment confirmation grace period; advance payment retained.";
    }
}

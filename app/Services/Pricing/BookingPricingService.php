<?php

namespace App\Services\Pricing;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Invoice;
use Carbon\CarbonInterface;

class BookingPricingService
{
    public function calculateRentalDays(mixed $startDate, mixed $endDate): int
    {
        if ($startDate instanceof CarbonInterface && $endDate instanceof CarbonInterface) {
            return max(1, $startDate->diffInDays($endDate));
        }

        $start = strtotime((string) $startDate);
        $end = strtotime((string) $endDate);

        if ($start === false || $end === false) {
            return 1;
        }

        return max(1, (int) ceil(($end - $start) / 86400));
    }

    public function calculateRentalAmount(float $rentalPricePerDay, int $days): float
    {
        return round($rentalPricePerDay * max(1, $days), 2);
    }

    public function calculateInsuranceAmount(?float $fixedProtectionPrice): float
    {
        return round((float) ($fixedProtectionPrice ?? 0), 2);
    }

    public function calculateDropoffFee(mixed $pickupLocationId, mixed $dropoffLocationId): float
    {
        return $pickupLocationId != $dropoffLocationId
            ? (float) setting('dropoff_fee', config('rental.dropoff_fee'))
            : 0.0;
    }

    public function calculateExtras(array $extras = []): float
    {
        return round(array_sum(array_map(static fn ($amount) => (float) $amount, $extras)), 2);
    }

    public function calculateTotal(
        float $rentalAmount,
        float $insuranceAmount,
        float $extrasAmount = 0,
        float $discountAmount = 0,
        float $taxAmount = 0
    ): float {
        return round($rentalAmount + $insuranceAmount + $extrasAmount + $taxAmount - $discountAmount, 2);
    }

    public function calculateCouponDiscount(Coupon $coupon, float $amount): float
    {
        if ($coupon->discount_type === 'percentage') {
            return ($amount * (float) $coupon->discount_value) / 100;
        }

        return min((float) $coupon->discount_value, $amount);
    }

    public function calculateDiscountedTotal(float $amount, float $discountAmount): float
    {
        return $amount - $discountAmount;
    }

    public function calculateCouponFinalAmount(float $amount, float $discountAmount): float
    {
        return $this->calculateDiscountedTotal($amount, $discountAmount);
    }

    public function calculateAdvancePayment(float|Booking $amountOrBooking, bool $round = false): float
    {
        $totalAmount = $amountOrBooking instanceof Booking
            ? (float) $amountOrBooking->total_amount
            : (float) $amountOrBooking;

        $advanceAmount = ($totalAmount * (float) setting(
            'advance_payment_percentage',
            config('rental.advance_payment_percentage', 30)
        )) / 100;

        return $round ? round($advanceAmount, 2) : $advanceAmount;
    }

    public function calculateAdvancePaymentProgress(float $paidAmount, float $minimumAdvancePayment): float
    {
        if ($minimumAdvancePayment == 0.0) {
            return 0.0;
        }

        return min(100, ($paidAmount / $minimumAdvancePayment) * 100);
    }

    public function calculateRemainingAdvancePayment(float $minimumAdvancePayment, float $paidAmount): float
    {
        return max(0, $minimumAdvancePayment - $paidAmount);
    }

    public function calculateInvoiceRemainingAmount(float $totalAmount, float $paidAmount): float
    {
        return max(0, $totalAmount - $paidAmount);
    }

    public function calculateInvoiceRemainingCents(float $totalAmount, float $paidAmount): int
    {
        return $this->amountToCents($this->calculateInvoiceRemainingAmount($totalAmount, $paidAmount));
    }

    public function calculateHalfAmount(float $amount): float
    {
        return $amount / 2;
    }

    public function calculateSecurityDeposit(Booking $booking): float
    {
        return (float) ($booking->security_deposit_amount ?: ($booking->car->security_deposit_amount ?? 0));
    }

    public function calculateSecurityDepositPenalty(?float $amount, float $depositAmount): float
    {
        return min(max(0, (float) ($amount ?? 0)), $depositAmount);
    }

    public function calculateSecurityDepositRefundAmount(float $chargedAmount, float $refundedAmount = 0, float $penaltyAmount = 0): float
    {
        return max(0, $chargedAmount - $penaltyAmount - $refundedAmount);
    }

    public function calculateRemainingRefundableSecurityDeposit(float $chargedAmount, float $refundedAmount = 0): float
    {
        return max(0, $chargedAmount - $refundedAmount);
    }

    public function calculateTargetSecurityDepositRefund(float $chargedAmount, float $penaltyAmount): float
    {
        return max(0, $chargedAmount - min($penaltyAmount, $chargedAmount));
    }

    public function calculateRecordedSecurityDepositRefundedAmount(
        string $effectiveState,
        float $chargedAmount,
        float $penaltyAmount,
        ?float $recordedRefundedAmount
    ): float {
        if ($recordedRefundedAmount !== null) {
            return (float) $recordedRefundedAmount;
        }

        return in_array($effectiveState, [Booking::SECURITY_DEPOSIT_STATUS_REFUNDED, Booking::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED], true)
            ? max(0, $chargedAmount - $penaltyAmount)
            : 0.0;
    }

    public function calculatePendingSecurityDepositRefund(
        float $chargedAmount,
        float $penaltyAmount,
        float $refundedAmount
    ): float {
        return max(0, $chargedAmount - $penaltyAmount - $refundedAmount);
    }

    public function calculateTax(float $amount, ?float $taxPercentage = null): float
    {
        $taxPercentage ??= (float) setting('tax_percentage', config('rental.tax_percentage', 0));

        return $taxPercentage > 0
            ? ($amount * $taxPercentage) / 100
            : 0.0;
    }

    public function calculateTaxBreakdown(float $subtotalBeforeTax, ?float $taxPercentage = null): array
    {
        $taxPercentage ??= (float) setting('tax_percentage', config('rental.tax_percentage', 0));

        if ($taxPercentage > 0) {
            $taxMultiplier = 1 + ($taxPercentage / 100);
            $totalAmount = $subtotalBeforeTax * $taxMultiplier;
            $subtotal = $totalAmount / $taxMultiplier;
            $taxAmount = $totalAmount - $subtotal;
        } else {
            $totalAmount = $subtotalBeforeTax;
            $subtotal = $subtotalBeforeTax;
            $taxAmount = 0;
        }

        return [
            'subtotal_amount' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'tax_percentage' => $taxPercentage,
        ];
    }

    public function calculateDamageCharge(Booking $booking): float
    {
        return (float) $booking->checkoutDamages()
            ->where('is_chargeable', true)
            ->sum('estimated_cost');
    }

    public function calculateFuelUsed(?float $pickupFuel, ?float $returnFuel): float
    {
        return max(0, (float) ($pickupFuel ?? 0) - (float) ($returnFuel ?? 0));
    }

    public function calculateFuelMissingLiters(?float $tankCapacity, float $missingPercent): float
    {
        return (float) $tankCapacity * ($missingPercent / 100);
    }

    public function calculateFuelCharge(float $fuelUsed): float
    {
        return $fuelUsed * (float) setting(
            'fuel_price_per_percent',
            config('rental.fuel_price_per_percent')
        );
    }

    public function calculateFuelChargeFromLevels(?float $pickupFuel, ?float $returnFuel): array
    {
        $fuelUsed = $this->calculateFuelUsed($pickupFuel, $returnFuel);

        return [
            'fuel_used' => $fuelUsed,
            'fuel_charge' => $this->calculateFuelCharge($fuelUsed),
        ];
    }

    public function calculateFuelChargeFromTankPercentage(
        ?float $pickupFuel,
        ?float $returnFuel,
        ?float $tankCapacity,
        ?float $pricePerLiter
    ): float {
        if ($pickupFuel === null || $returnFuel === null) {
            return 0.0;
        }

        $missingPercent = (float) $pickupFuel - (float) $returnFuel;

        if ($missingPercent <= 0) {
            return 0.0;
        }

        $missingLiters = $this->calculateFuelMissingLiters($tankCapacity, $missingPercent);

        return round($missingLiters * (float) $pricePerLiter, 2);
    }

    public function calculateLateCharge(Booking $booking, mixed $returnAt = null): array
    {
        $returnAt ??= now();

        if (!$booking->end_date || $returnAt->lessThanOrEqualTo($booking->end_date)) {
            return ['minutes' => 0, 'fee' => 0.0];
        }

        $lateMinutes = $booking->end_date->diffInMinutes($returnAt);
        $chargeableMinutes = max(
            0,
            $lateMinutes - (int) setting('late_grace_minutes', config('rental.late_grace_minutes'))
        );

        $fee = ceil($chargeableMinutes / 60) * (float) setting(
            'late_fee_per_hour',
            config('rental.late_fee_per_hour')
        );

        return [
            'minutes' => $lateMinutes,
            'fee' => $fee,
        ];
    }

    public function calculateLegacyLateCharge(Booking $booking, mixed $returnAt = null): array
    {
        $returnAt ??= now();

        if ($booking->isCompleted()) {
            return [
                'minutes' => (int) $booking->late_minutes,
                'fee' => (float) $booking->late_fee,
            ];
        }

        if (!$booking->end_date || $returnAt->lessThanOrEqualTo($booking->end_date)) {
            return ['minutes' => 0, 'fee' => 0];
        }

        $minutes = $returnAt->diffInMinutes($booking->end_date);

        return [
            'minutes' => $minutes,
            'fee' => $minutes * 5,
        ];
    }

    public function calculateCheckoutCharges(Booking $booking, int|float $returnFuel): array
    {
        $fuel = $this->calculateFuelChargeFromLevels($booking->fuel_at_pickup_percent ?? 0, $returnFuel);
        $late = $this->calculateLateCharge($booking);

        return [
            'fuel_used' => $fuel['fuel_used'],
            'fuel_charge' => $fuel['fuel_charge'],
            'late_minutes' => $late['minutes'],
            'late_fee' => $late['fee'],
        ];
    }

    public function calculateFinalTotalWithExtras(Booking $booking): float
    {
        return (float) $booking->total_amount
            + (float) ($booking->fuel_charge ?? 0)
            + (float) ($booking->late_fee ?? 0)
            + (float) $booking->damages()->sum('estimated_cost');
    }

    public function calculateInvoiceTotalsForBooking(Booking $booking): array
    {
        $pricingBreakdown = $this->breakdownForBooking($booking);
        $damageCharge = $this->calculateDamageCharge($booking);
        $subtotalBeforeTax = $pricingBreakdown['total_amount'] + $damageCharge;
        $taxBreakdown = $this->calculateTaxBreakdown($subtotalBeforeTax);

        return array_merge($taxBreakdown, [
            'base_booking_amount' => $pricingBreakdown['total_amount'],
            'damage_amount' => $damageCharge,
            'subtotal_before_tax' => $subtotalBeforeTax,
        ]);
    }

    public function calculateRefundAmount(Booking $booking, float $paidAmount): float
    {
        if ($paidAmount <= 0) {
            return 0.0;
        }

        if ($booking->isActive()) {
            $totalDays = max(1, $booking->start_date->diffInDays($booking->end_date));
            $usedDays = ceil($booking->start_date->diffInHours(now()) / 24);
            $usedDays = min($usedDays, $totalDays);
            $averageAmountPerDay = (float) $booking->total_amount / $totalDays;
            $usedAmount = $usedDays * $averageAmountPerDay;

            return max(0, $paidAmount - $usedAmount);
        }

        $hoursBeforePickup = now()
            ->utc()
            ->diffInHours($booking->start_date->utc(), false);

        if ($hoursBeforePickup > 48) {
            return $paidAmount;
        }

        if ($hoursBeforePickup >= 0) {
            $advancePayment = min((float) $booking->advance_payment_amount, $paidAmount);

            return max(0, $paidAmount - $advancePayment);
        }

        return 0.0;
    }

    public function calculatePaidAmount(float $payments, float $refunds): float
    {
        return $payments - $refunds;
    }

    public function calculateBalance(float $totalAmount, float $paidAmount): float
    {
        return $this->calculateInvoiceRemainingAmount($totalAmount, $paidAmount);
    }

    public function calculateInvoiceStatus(float $paidAmount, float $totalAmount): string
    {
        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            return Invoice::STATUS_PAID;
        }

        if ($paidAmount > 0) {
            return Invoice::STATUS_PARTIAL;
        }

        return Invoice::STATUS_PENDING;
    }

    public function amountToCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public function centsToAmount(int|float $amount): float
    {
        return ((float) $amount) / 100;
    }

    public function breakdown(
        float $rentalAmount,
        float $insuranceAmount,
        float $extrasAmount = 0,
        float $discountAmount = 0,
        float $taxAmount = 0
    ): array {
        $rentalAmount = round($rentalAmount, 2);
        $insuranceAmount = round($insuranceAmount, 2);
        $extrasAmount = round($extrasAmount, 2);
        $discountAmount = round($discountAmount, 2);
        $taxAmount = round($taxAmount, 2);

        return [
            'rental_amount' => $rentalAmount,
            'rental_price' => $rentalAmount,
            'insurance_amount' => $insuranceAmount,
            'insurance_price' => $insuranceAmount,
            'protection_plan_amount' => $insuranceAmount,
            'extras_amount' => $extrasAmount,
            'discount_amount' => $discountAmount,
            'coupon_discount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'tax' => $taxAmount,
            'subtotal_amount' => round($rentalAmount + $insuranceAmount + $extrasAmount, 2),
            'subtotal' => round($rentalAmount + $insuranceAmount + $extrasAmount, 2),
            'total_amount' => $this->calculateTotal(
                $rentalAmount,
                $insuranceAmount,
                $extrasAmount,
                $discountAmount,
                $taxAmount
            ),
            'grand_total' => $this->calculateTotal(
                $rentalAmount,
                $insuranceAmount,
                $extrasAmount,
                $discountAmount,
                $taxAmount
            ),
            'advance_payment_amount' => $this->calculateAdvancePayment(
                $this->calculateTotal($rentalAmount, $insuranceAmount, $extrasAmount, $discountAmount, $taxAmount),
                true
            ),
            'security_deposit_amount' => 0.0,
            'late_fee' => 0.0,
            'fuel_fee' => 0.0,
            'damage_fee' => 0.0,
        ];
    }

    public function breakdownForPreview(
        float $rentalPricePerDay,
        int $days,
        ?float $fixedProtectionPrice = 0,
        array $extras = [],
        float $discountAmount = 0
    ): array {
        return $this->breakdown(
            $this->calculateRentalAmount($rentalPricePerDay, $days),
            $this->calculateInsuranceAmount($fixedProtectionPrice),
            $this->calculateExtras($extras),
            $discountAmount
        );
    }

    public function breakdownForBooking(Booking $booking): array
    {
        $rentalAmount = $this->calculateRentalAmount(
            (float) $booking->rental_price_per_day,
            (int) $booking->total_days
        );
        $insuranceAmount = $this->calculateInsuranceAmount($booking->insurance_fixed_price);
        $discountAmount = round((float) ($booking->discount_amount ?? 0), 2);
        $storedTotal = round((float) $booking->total_amount, 2);

        // Backward compatibility: old bookings only have total_amount, so infer extras.
        $extrasAmount = max(0, round($storedTotal + $discountAmount - $rentalAmount - $insuranceAmount, 2));

        $breakdown = $this->breakdown($rentalAmount, $insuranceAmount, $extrasAmount, $discountAmount);
        $breakdown['total_amount'] = $storedTotal;

        return $breakdown;
    }

    public function breakdownForInvoice(Invoice $invoice): array
    {
        $invoice->loadMissing('booking');

        if (!$invoice->booking) {
            $breakdown = $this->breakdown(
                (float) $invoice->subtotal,
                0,
                0,
                (float) ($invoice->discount_amount ?? 0),
                (float) ($invoice->tax_amount ?? 0)
            );
            $breakdown['total_amount'] = round((float) $invoice->total_amount, 2);

            return $breakdown;
        }

        $bookingBreakdown = $this->breakdownForBooking($invoice->booking);
        $discountAmount = round((float) ($invoice->discount_amount ?? $bookingBreakdown['discount_amount']), 2);
        $taxAmount = round((float) ($invoice->tax_amount ?? 0), 2);
        $storedTotal = round((float) $invoice->total_amount, 2);

        $extrasAmount = max(0, round(
            $storedTotal + $discountAmount - $taxAmount - $bookingBreakdown['rental_amount'] - $bookingBreakdown['insurance_amount'],
            2
        ));

        $breakdown = $this->breakdown(
            $bookingBreakdown['rental_amount'],
            $bookingBreakdown['insurance_amount'],
            $extrasAmount,
            $discountAmount,
            $taxAmount
        );
        $breakdown['total_amount'] = $storedTotal;

        return $breakdown;
    }

    public function lineItems(array $breakdown): array
    {
        return [
            ['key' => 'rental', 'label' => 'Rental', 'amount' => $breakdown['rental_amount']],
            ['key' => 'protection_plan', 'label' => 'Protection Plan', 'amount' => $breakdown['protection_plan_amount']],
            ['key' => 'extras', 'label' => 'Extras', 'amount' => $breakdown['extras_amount']],
            ['key' => 'discount', 'label' => 'Discount', 'amount' => -abs($breakdown['discount_amount'])],
            ['key' => 'tax', 'label' => 'Tax', 'amount' => $breakdown['tax_amount']],
            ['key' => 'total', 'label' => 'Total', 'amount' => $breakdown['total_amount']],
        ];
    }
}

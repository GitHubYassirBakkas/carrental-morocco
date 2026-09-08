<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Events\BookingCompleted;
use App\Mail\AdminPaymentConfirmedMail;
use Illuminate\Support\Facades\Mail;
class BookingService
{
    private DepositService $depositService;

    public function __construct(DepositService $depositService)
    {
        $this->depositService = $depositService;
    }

    /**
     * Confirm a booking and create invoice
     *
     * @param Booking $booking
     * @return Booking
     * @throws Exception
     */
    public function confirmBooking(Booking $booking): Booking
    {
        if ($booking->status !== 'pending') {
            throw new Exception('Only pending bookings can be confirmed');
        }

        return DB::transaction(function () use ($booking) {
            // Update booking status
            $booking->update(['status' => 'confirmed']);

            // Create invoice if doesn't exist
            if (!$booking->invoice) {
                $this->createInvoiceForBooking($booking);
            }

            // Set deposit deadline
            $deadlineHours = setting('deposit_deadline_hours', 24);
            $booking->update([
                'deposit_amount' => $this->depositService->calculateMinimumDeposit($booking),
                'deposit_due_at' => now()->addHours(24)
            ]);

            Log::info('Booking confirmed', ['booking_id' => $booking->id]);

              $freshBooking = $booking->fresh(['car', 'user', 'insurance', 'pickupLocation']);

        // ✅ زيد هنا
        try {
            Mail::to($freshBooking->user->email)
                ->send(new AdminPaymentConfirmedMail($freshBooking));
        } catch (Exception $e) {
            Log::error('Failed to send confirmation email: ' . $e->getMessage());
        }
        try {
    Log::info('Sending email to: ' . $freshBooking->user->email);
    Mail::to($freshBooking->user->email)
        ->send(new AdminPaymentConfirmedMail($freshBooking));
    Log::info('Email sent successfully!');
} catch (Exception $e) {
    Log::error('Failed to send email: ' . $e->getMessage());
}

Log::info('=== CONFIRM BOOKING CALLED ===', [
    'booking_id' => $booking->id,
    'status' => $booking->status
]);
            return $freshBooking;
        });
    }

    /**
     * Create invoice for booking with correct tax calculation
     *
     * @param Booking $booking
     * @return Invoice
     */
  private function createInvoiceForBooking(Booking $booking): Invoice
{
    // 1️⃣ Get tax percentage from settings
    $taxPercentage = setting('tax_percentage', 0);  // Default 0% (no tax)
    
    // 2️⃣ Base rental amount
    $rentalAmount = $booking->total_amount;
    
    // 3️⃣ Get checkout damage costs (if any)
    // Note: This will be 0 at booking confirmation (no damages yet)
    // Damages are added later during checkout
    $damagesCost = $booking->checkoutDamages()
        ->where('is_chargeable', true)
        ->sum('estimated_cost');
    
    // 4️⃣ Calculate subtotal (rental + damages)
    $subtotalBeforeTax = $rentalAmount + $damagesCost;
    
    // 5️⃣ Calculate tax (if tax percentage > 0)
    if ($taxPercentage > 0) {
        // With tax
        $taxMultiplier = 1 + ($taxPercentage / 100);
        $totalAmount = $subtotalBeforeTax * $taxMultiplier;
        $subtotal = $totalAmount / $taxMultiplier;
        $taxAmount = $totalAmount - $subtotal;
    } else {
        // No tax (0%)
        $totalAmount = $subtotalBeforeTax;
        $subtotal = $subtotalBeforeTax;
        $taxAmount = 0;
    }
    
    // 6️⃣ Create invoice
    return Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => round($subtotal, 2),
        'tax_amount' => round($taxAmount, 2),
        'total_amount' => round($totalAmount, 2),
        'status' => 'unpaid',
        'issued_at' => now(),
        'due_date' => $booking->start_date ?? now()->addDays(7),
    ]);
}
    /**
     * Start rental (move from confirmed to active)
     *
     * @param Booking $booking
     * @return Booking
     * @throws Exception
     */
    public function startRental(Booking $booking): Booking
    {
        if ($booking->status !== 'confirmed') {
            throw new Exception('Only confirmed bookings can be started');
        }

        if (!$booking->checkinInspection) {
            throw new Exception('Check-in inspection required before starting rental');
        }

        $booking->update(['status' => 'active']);

        Log::info('Rental started', ['booking_id' => $booking->id]);

        return $booking;
    }

    /**
     * Complete rental (move from active to completed)
     *
     * @param Booking $booking
     * @return Booking
     * @throws Exception
     */
    public function completeRental(Booking $booking): Booking
    {
        if ($booking->status !== 'active') {
            throw new Exception('Only active rentals can be completed');
        }

        if (!$booking->checkoutInspection) {
            throw new Exception('Check-out inspection required before completing rental');
        }

        $booking->update(['status' => 'completed']);
        // ✅ TRIGGER EVENT HERE!
        event(new BookingCompleted($booking));

        Log::info('Rental completed', ['booking_id' => $booking->id]);

        return $booking;
    }

    /**
     * Cancel a booking
     *
     * @param Booking $booking
     * @param string|null $reason
     * @return Booking
     * @throws Exception
     */
   public function cancelBooking(Booking $booking, ?string $reason = null): Booking
{
    if (!in_array($booking->status, ['pending', 'confirmed', 'active'])) {
        throw new Exception('This booking cannot be cancelled');
    }

    return DB::transaction(function () use ($booking, $reason) {

        $invoice = $booking->invoice;
        $refundAmount = 0;

        if ($invoice) {
            $refundService = app(\App\Services\RefundService::class);

            $paidAmount = $refundService->calculatePaidAmount($invoice);

            if ($paidAmount > 0) {

                // 🔹 DURING RENTAL (Early return)
                if ($booking->status === 'active') {

                    $totalDays = max(1, $booking->start_date->diffInDays($booking->end_date));
                    $usedDays = ceil($booking->start_date->diffInHours(now()) / 24);
                    $usedDays = min($usedDays, $totalDays);

                    $dailyRate = $booking->total_amount / $totalDays;
                    $usedAmount = $usedDays * $dailyRate;

                    $refundAmount = max(0, $paidAmount - $usedAmount);

                } else {

                    $hoursBeforePickup = now()
                        ->utc()
                        ->diffInHours($booking->start_date->utc(), false);

                    if ($hoursBeforePickup > 48) {
                        $refundAmount = $paidAmount;
                    } elseif ($hoursBeforePickup >= 0) {
                        $deposit = min($booking->deposit_amount, $paidAmount);
                        $refundAmount = max(0, $paidAmount - $deposit);
                    } else {
                        $refundAmount = 0; // no-show
                    }
                }

                $refundAmount = min($refundAmount, $paidAmount);

                if ($refundAmount > 0) {
                    $refundService->processRefund(
                        $invoice,
                        $refundAmount,
                        $reason ?? 'Booking cancelled'
                    );
                }
            }
        }

        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason
        ]);

        if ($booking->car) {
            $booking->car->update(['is_available' => true]);
        }

        return $booking;
    });
}

    /**
     * Cancel all overdue bookings (for cron job)
     *
     * @return int Number of bookings cancelled
     */
   public function cancelOverdueBookings(): int
{
    $cancelledCount = 0;

    Booking::where('status', 'pending')
        ->where('deposit_paid', false)
        ->where('deposit_due_at', '<', now())
        ->chunkById(100, function ($bookings) use (&$cancelledCount) {

            foreach ($bookings as $booking) {

                try {
                    $this->cancelBooking($booking, 'Deposit not paid within deadline');
                    $cancelledCount++;
                } catch (Exception $e) {
                    Log::error('Failed to cancel overdue booking', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage()
                    ]);
                }

            }
        });

    return $cancelledCount;
}
}
<?php

namespace App\Services;

use App\Events\BookingCompleted;
use App\Mail\AdminPaymentConfirmedMail;
use App\Models\Booking;
use App\Services\Pricing\BookingPricingService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingService
{
    private AdvancePaymentService $advancePaymentService;
    private BookingPricingService $pricingService;

    public function __construct(
        AdvancePaymentService $advancePaymentService,
        BookingPricingService $pricingService,
        private readonly InvoiceService $invoiceService
    ) {
        $this->advancePaymentService = $advancePaymentService;
        $this->pricingService = $pricingService;
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
        if (!$booking->isPending()) {
            throw new Exception('Only pending bookings can be confirmed');
        }

        return DB::transaction(function () use ($booking) {
            // Update booking status
            $booking->update(['status' => Booking::STATUS_CONFIRMED]);

            // Create invoice if doesn't exist
            if (!$booking->invoice) {
                $this->invoiceService->createForConfirmedBooking($booking);
            }

            // Set advance payment deadline.
            $deadlineHours = setting(
                'advance_payment_deadline_hours',
                config('rental.advance_payment_deadline_hours', 24)
            );
            $booking->update([
                'advance_payment_amount' => $this->advancePaymentService->calculateMinimumAdvancePayment($booking),
                'advance_payment_due_at' => now()->addHours($deadlineHours)
            ]);

            Log::info('Booking confirmed', ['booking_id' => $booking->id]);

            $freshBooking = $booking->fresh(['car', 'user', 'insurance', 'pickupLocation']);

            try {
                Mail::to($freshBooking->user->email)
                    ->send(new AdminPaymentConfirmedMail($freshBooking));
            } catch (Exception $e) {
                Log::error('Failed to send confirmation email', [
                    'booking_id' => $freshBooking->id,
                    'message' => $e->getMessage(),
                ]);
            }

            return $freshBooking;
        });
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
        if (!$booking->isConfirmed()) {
            throw new Exception('Only confirmed bookings can be started');
        }

        if (!$booking->checkinInspection) {
            throw new Exception('Check-in inspection required before starting rental');
        }

        $booking->update(['status' => Booking::STATUS_ACTIVE]);

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
        if (!$booking->isActive()) {
            throw new Exception('Only active rentals can be completed');
        }

        if (!$booking->checkoutInspection) {
            throw new Exception('Check-out inspection required before completing rental');
        }

        $booking->update(['status' => Booking::STATUS_COMPLETED]);
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
        if (!in_array($booking->status, Booking::CANCELLABLE_STATUSES, true)) {
            throw new Exception('This booking cannot be cancelled');
        }

        $cancelledBooking = DB::transaction(function () use ($booking, $reason) {
            $invoice = $booking->invoice;
            $refundAmount = 0;

            if ($invoice) {
                $refundService = app(RefundService::class);

                $paidAmount = $refundService->calculatePaidAmount($invoice);

                if ($paidAmount > 0) {
                    $refundAmount = min(
                        $this->pricingService->calculateRefundAmount($booking, (float) $paidAmount),
                        $paidAmount
                    );

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
                'status' => Booking::STATUS_CANCELLED,
                'cancellation_reason' => $reason
            ]);

            if ($booking->car) {
                $booking->car->update(['is_available' => true]);
            }

            return $booking;
        });

        try {
            $freshBooking = $cancelledBooking->fresh();

            if ($freshBooking->isSecurityDepositSafeToRelease()) {
                app(SecurityDepositService::class)->release($freshBooking);
            }
        } catch (Exception $e) {
            Log::error('Failed to auto-release security deposit after booking cancellation', [
                'booking_id' => $cancelledBooking->id,
                'intent_id' => $cancelledBooking->security_deposit_intent_id,
                'message' => $e->getMessage(),
            ]);
        }

        return $cancelledBooking;
    }

    /**
     * Cancel all overdue bookings (for cron job)
     *
     * @return int Number of bookings cancelled
     */
    public function cancelOverdueBookings(): int
    {
        $cancelledCount = 0;

        Booking::where('status', Booking::STATUS_PENDING)
            ->where('advance_payment_status', '!=', Booking::ADVANCE_PAYMENT_STATUS_PAID)
            ->where('advance_payment_due_at', '<', now())
            ->chunkById(100, function ($bookings) use (&$cancelledCount) {
                foreach ($bookings as $booking) {
                    try {
                        $this->cancelBooking($booking, 'Advance payment not paid within deadline');
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

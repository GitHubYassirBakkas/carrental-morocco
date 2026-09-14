<?php

namespace App\Services;

use App\Events\BookingCompleted;
use App\Mail\AdminPaymentConfirmedMail;
use App\Models\Booking;
use App\Models\Notification;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingService
{
    private AdvancePaymentService $advancePaymentService;

    public function __construct(
        AdvancePaymentService $advancePaymentService,
        private readonly InvoiceService $invoiceService,
        private readonly RefundService $refundService,
        private readonly SecurityDepositService $securityDepositService,
        private readonly NotificationService $notificationService,
        private readonly RentalBusinessRules $rentalRules
    ) {
        $this->advancePaymentService = $advancePaymentService;
    }

    /**
     * Confirm a booking and create invoice
     *
     * @throws Exception
     */
    public function confirmBooking(Booking $booking): Booking
    {
        if (! $booking->isPending()) {
            throw new Exception('Only pending bookings can be confirmed');
        }

        $freshBooking = DB::transaction(function () use ($booking) {
            // Update booking status
            $booking->update(['status' => Booking::STATUS_CONFIRMED]);

            // Create invoice if doesn't exist
            if (! $booking->invoice) {
                $this->invoiceService->createForConfirmedBooking($booking);
            }

            // Store the required advance amount for this booking.
            $booking->update([
                'advance_payment_amount' => $this->advancePaymentService->calculateMinimumAdvancePayment($booking),
            ]);

            Log::info('Booking confirmed', ['booking_id' => $booking->id]);

            $freshBooking = $booking->fresh(['car', 'user', 'insurance', 'pickupLocation']);

            // ✅ CREATE NOTIFICATION FOR BOOKING APPROVED
            $notificationExists = Notification::where('user_id', $freshBooking->user_id)
                ->where('type', 'booking_approved')
                ->whereJsonContains('data->booking_id', $freshBooking->id)
                ->exists();

            if (! $notificationExists) {
                $this->notificationService->create(
                    $freshBooking->user_id,
                    'booking_approved',
                    __('messages.notification_booking_approved'),
                    __('messages.notification_booking_approved_message', ['reference' => $freshBooking->reference]),
                    [
                        'booking_id' => $freshBooking->id,
                        'booking_reference' => $freshBooking->reference,
                        'car_id' => $freshBooking->car_id,
                        'car_name' => $freshBooking->car->name,
                        'pickup_date' => $freshBooking->start_date,
                        'return_date' => $freshBooking->end_date,
                        'approved_at' => now(),
                    ]
                );
            }

            return $freshBooking;
        });

        try {
            Mail::to($freshBooking->user->email)
                ->send(new AdminPaymentConfirmedMail($freshBooking));
        } catch (\Throwable $e) {
            Log::error('Failed to send confirmation email', [
                'booking_id' => $freshBooking->id,
                'user_id' => $freshBooking->user_id,
                'mailable' => AdminPaymentConfirmedMail::class,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }

        return $freshBooking;
    }

    /**
     * Start rental (move from confirmed to active)
     *
     * @throws Exception
     */
    public function startRental(Booking $booking): Booking
    {
        if (! $booking->isConfirmed()) {
            throw new Exception('Only confirmed bookings can be started');
        }

        if (! $booking->hasVerifiedDriverProfile()) {
            throw new \DomainException('Driver verification must be completed before starting the rental.');
        }

        $booking->loadMissing('car', 'user.customerProfile');
        if ($booking->car && ! $this->rentalRules->driverMeetsMinimumAge($booking->user, $booking->car)) {
            throw new \DomainException(
                'Driver must be at least '.$this->rentalRules->effectiveMinimumDriverAge($booking->car).' years old for this vehicle.'
            );
        }

        if (! $booking->checkinInspection) {
            throw new Exception('Check-in inspection required before starting rental');
        }

        $booking->update(['status' => Booking::STATUS_ACTIVE]);

        Log::info('Rental started', ['booking_id' => $booking->id]);

        // ✅ CREATE NOTIFICATION FOR RENTAL STARTED
        $booking->load('car');
        $this->notificationService->create(
            $booking->user_id,
            'rental_started',
            __('messages.notification_rental_started'),
            __('messages.notification_rental_started_message'),
            [
                'booking_id' => $booking->id,
                'car_id' => $booking->car_id,
                'car_name' => $booking->car->name,
                'pickup_date' => $booking->start_date,
                'return_date' => $booking->end_date,
            ]
        );

        return $booking;
    }

    /**
     * Complete rental (move from active to completed)
     *
     * @throws Exception
     */
    public function completeRental(Booking $booking): Booking
    {
        $booking = DB::transaction(function () use ($booking) {
            $lockedBooking = Booking::with('checkoutInspection')
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedBooking->isActive()) {
                throw new Exception('Only active rentals can be completed');
            }

            if (! $lockedBooking->checkoutInspection) {
                throw new Exception('Check-out inspection required before completing rental');
            }

            $updates = ['status' => Booking::STATUS_COMPLETED];

            if ($lockedBooking->completed_at === null) {
                $updates['completed_at'] = now();
            }

            $lockedBooking->forceFill($updates)->save();

            return $lockedBooking->fresh();
        });

        event(new BookingCompleted($booking));

        Log::info('Rental completed', ['booking_id' => $booking->id]);

        // ✅ CREATE NOTIFICATION FOR RENTAL COMPLETED
        $booking->load('car');
        $this->notificationService->create(
            $booking->user_id,
            'rental_completed',
            __('messages.notification_rental_completed'),
            __('messages.notification_rental_completed_message'),
            [
                'booking_id' => $booking->id,
                'car_id' => $booking->car_id,
                'car_name' => $booking->car->name,
                'pickup_date' => $booking->start_date,
                'return_date' => $booking->end_date,
            ]
        );

        // ✅ CREATE REVIEW REMINDER NOTIFICATION
        $reviewExists = \App\Models\Notification::where('user_id', $booking->user_id)
            ->where('type', 'review_reminder')
            ->whereJsonContains('data->booking_id', $booking->id)
            ->exists();

        if (! $reviewExists) {
            $this->notificationService->create(
                $booking->user_id,
                'review_reminder',
                __('messages.notification_review_reminder'),
                __('messages.notification_review_reminder_message'),
                [
                    'booking_id' => $booking->id,
                    'car_id' => $booking->car_id,
                    'car_name' => $booking->car->name,
                    'review_url' => route('cars.details', $booking->car),
                    'completed_at' => now(),
                ]
            );
        }

        return $booking;
    }

    /**
     * Cancel a booking
     *
     * @throws Exception
     */
    public function cancelBooking(Booking $booking, ?string $reason = null): Booking
    {
        if (! in_array($booking->status, Booking::CANCELLABLE_STATUSES, true)) {
            throw new Exception('This booking cannot be cancelled');
        }

        $cancelledBooking = DB::transaction(function () use ($booking, $reason) {
            $invoice = $booking->invoice;

            // Delegate all refund logic to RefundService
            $this->refundService->processBookingCancellationRefund(
                $booking,
                $invoice,
                $reason
            );

            $booking->update([
                'status' => Booking::STATUS_CANCELLED,
                'cancellation_reason' => $reason,
            ]);

            if ($booking->car) {
                $booking->car->update(['is_available' => true]);
            }

            return $booking;
        });

        try {
            $freshBooking = $cancelledBooking->fresh();

            if ($freshBooking->isSecurityDepositSafeToRelease()) {
                $this->securityDepositService->release($freshBooking);
            }
        } catch (Exception $e) {
            Log::error('Failed to auto-release security deposit after booking cancellation', [
                'booking_id' => $cancelledBooking->id,
                'intent_id' => $cancelledBooking->security_deposit_intent_id,
                'message' => $e->getMessage(),
            ]);
        }

        // ✅ CREATE NOTIFICATION FOR BOOKING CANCELLED
        $freshBooking = $cancelledBooking->fresh(['car']);
        $notificationExists = Notification::where('user_id', $freshBooking->user_id)
            ->where('type', 'booking_cancelled')
            ->whereJsonContains('data->booking_id', $freshBooking->id)
            ->exists();

        if (! $notificationExists) {
            $this->notificationService->create(
                $freshBooking->user_id,
                'booking_cancelled',
                __('messages.notification_booking_cancelled'),
                __('messages.notification_booking_cancelled_message', ['reference' => $freshBooking->reference]),
                [
                    'booking_id' => $freshBooking->id,
                    'booking_reference' => $freshBooking->reference,
                    'car_id' => $freshBooking->car_id,
                    'car_name' => $freshBooking->car ? $freshBooking->car->name : null,
                    'cancelled_at' => $freshBooking->updated_at,
                    'cancellation_reason' => $freshBooking->cancellation_reason,
                ]
            );
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
            ->whereHas('invoice.payments', function ($query) {
                $query->where('method', 'cash')
                    ->where('type', \App\Models\Payment::TYPE_PAYMENT);
            })
            ->chunkById(100, function ($bookings) use (&$cancelledCount) {
                foreach ($bookings as $booking) {
                    try {
                        $this->cancelBooking($booking, 'Advance payment not paid within deadline');
                        $cancelledCount++;
                    } catch (Exception $e) {
                        Log::error('Failed to cancel overdue booking', [
                            'booking_id' => $booking->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $cancelledCount;
    }
}

<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Pricing\BookingPricingService;
use Exception;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    private AdvancePaymentService $advancePaymentService;

    public function __construct(
        AdvancePaymentService $advancePaymentService,
        private readonly InvoiceService $invoiceService,
        private readonly BookingPricingService $pricingService,
        private readonly PaymentStateTransitionValidator $transitionValidator,
        private readonly RentalBusinessRules $rentalRules
    ) {
        $this->advancePaymentService = $advancePaymentService;
    }

    /**
     * Record a completed payment for an invoice.
     *
     * @throws Exception
     */
    public function recordPayment(
        Invoice $invoice,
        float $amount,
        string $method,
        int $userId,
        ?string $notes = null
    ): Payment {
        return $this->recordPaymentWithBookingSync($invoice, $amount, $method, $userId, $notes)['payment'];
    }

    public function recordPaymentWithBookingSync(
        Invoice $invoice,
        float $amount,
        string $method,
        int $userId,
        ?string $notes = null
    ): array {
        return DB::transaction(function () use ($invoice, $amount, $method, $userId, $notes) {
            $invoice = Invoice::whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($amount <= 0) {
                throw new Exception('Payment amount must be greater than zero');
            }

            $balance = (float) $invoice->balance;
            if ($amount > $balance) {
                throw new Exception("Payment amount ({$amount} MAD) exceeds balance ({$balance} MAD)");
            }

            $payment = $this->createPaymentRecord($invoice, [
                'invoice_id' => $invoice->id,
                'user_id' => $userId,
                'amount' => $amount,
                'method' => $method,
                'type' => Payment::TYPE_PAYMENT,
                'status' => Payment::STATUS_COMPLETED,
                'transaction_id' => $this->generateTransactionId('PAY'),
                'paid_at' => now(),
                'notes' => $notes,
            ]);

            $this->updateInvoiceStatus($invoice);

            $booking = $invoice->booking;
            $confirmed = false;

            if ($booking && $booking->isPending()) {
                $confirmed = $this->advancePaymentService->confirmBookingIfAdvancePaymentReached(
                    $booking,
                    (float) $invoice->fresh()->paid_amount
                );
            }

            \Log::info('Payment recorded', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'method' => $method,
            ]);

            return [
                'payment' => $payment,
                'booking_confirmed' => $confirmed,
            ];
        });
    }

    public function recordPendingPayment(Invoice $invoice, int $userId, float $amount, string $method, ?string $transactionId, ?string $notes): Payment
    {
        return $this->createPaymentRecord($invoice, [
            'user_id' => $userId,
            'amount' => $amount,
            'method' => $method,
            'type' => Payment::TYPE_PAYMENT,
            'status' => Payment::STATUS_PENDING,
            'paid_at' => null,
            'transaction_id' => $transactionId,
            'notes' => $notes,
        ]);
    }

    public function recordPendingCashRequestForBooking(Booking $booking, int $userId, ?string $notes = null): array
    {
        return DB::transaction(function () use ($booking, $userId, $notes) {
            $booking = Booking::whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            $invoice = $this->invoiceService->firstOrCreateForPaymentProcessing($booking);
            $invoice = Invoice::whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingPayment = $invoice->payments()
                ->where('user_id', $userId)
                ->where('method', 'cash')
                ->where('type', Payment::TYPE_PAYMENT)
                ->where('status', Payment::STATUS_PENDING)
                ->whereNull('transaction_id')
                ->lockForUpdate()
                ->oldest('id')
                ->first();

            if ($existingPayment) {
                if ($booking->isPending()) {
                    $this->markBookingPendingPayment($booking);
                }

                return [
                    'payment' => $existingPayment,
                    'invoice' => $invoice->fresh(),
                    'booking' => $booking->fresh(),
                    'amount' => (float) $existingPayment->amount,
                    'created' => false,
                    'already_pending' => true,
                    'already_paid' => false,
                ];
            }

            $amount = (float) $invoice->balance;

            if ($amount <= 0.0) {
                $this->updateInvoiceStatus($invoice);

                return [
                    'payment' => null,
                    'invoice' => $invoice->fresh(),
                    'booking' => $booking->fresh(),
                    'amount' => 0.0,
                    'created' => false,
                    'already_pending' => false,
                    'already_paid' => true,
                ];
            }

            $payment = $this->createPaymentRecord($invoice, [
                'invoice_id' => $invoice->id,
                'user_id' => $userId,
                'amount' => $amount,
                'method' => 'cash',
                'type' => Payment::TYPE_PAYMENT,
                'status' => Payment::STATUS_PENDING,
                'transaction_id' => null,
                'paid_at' => null,
                'notes' => $notes ?? 'Cash on delivery',
            ]);

            $this->updateInvoiceStatus($invoice);

            if ($booking->isPending()) {
                $this->markBookingPendingPayment($booking);
            }

            return [
                'payment' => $payment,
                'invoice' => $invoice->fresh(),
                'booking' => $booking->fresh(),
                'amount' => $amount,
                'created' => true,
                'already_pending' => false,
                'already_paid' => false,
            ];
        }, 3);
    }

    public function recordCompletedPayment(Invoice $invoice, int $userId, float $amount, string $method, ?string $transactionId, ?string $notes): Payment
    {
        return $this->createPaymentRecord($invoice, [
            'user_id' => $userId,
            'amount' => $amount,
            'method' => $method,
            'type' => Payment::TYPE_PAYMENT,
            'status' => Payment::STATUS_COMPLETED,
            'paid_at' => now(),
            'transaction_id' => $transactionId,
            'notes' => $notes,
        ]);
    }

    public function recordRemainingCashPaymentForBooking(Booking $booking, int $userId, ?string $notes = null): array
    {
        return DB::transaction(function () use ($booking, $userId, $notes) {
            $booking = Booking::whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            $invoice = $this->invoiceService->firstOrCreateForPaymentProcessing($booking);
            $invoice = Invoice::whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            $amount = (float) $invoice->balance;

            if ($amount <= 0.0) {
                $this->updateInvoiceStatus($invoice);

                $hasCompletedCashRentalPayment = $invoice->payments()
                    ->where('method', 'cash')
                    ->where('type', Payment::TYPE_PAYMENT)
                    ->where('status', Payment::STATUS_COMPLETED)
                    ->exists();
                $bookingWasPending = $booking->isPending();

                if ($hasCompletedCashRentalPayment && $bookingWasPending) {
                    $this->markBookingPaidByCash($booking);
                }

                $freshBooking = $booking->fresh();

                return [
                    'payment' => null,
                    'invoice' => $invoice->fresh(),
                    'booking' => $freshBooking,
                    'amount' => 0.0,
                    'already_paid' => true,
                    'booking_confirmed' => $bookingWasPending && ! $freshBooking->isPending(),
                ];
            }

            $payment = $this->createPaymentRecord($invoice, [
                'invoice_id' => $invoice->id,
                'user_id' => $userId,
                'amount' => $amount,
                'method' => 'cash',
                'type' => Payment::TYPE_PAYMENT,
                'status' => Payment::STATUS_COMPLETED,
                'transaction_id' => $this->generateTransactionId('CASH'),
                'paid_at' => now(),
                'notes' => $notes ?? 'Cash payment recorded by admin',
            ]);

            $this->updateInvoiceStatus($invoice);
            $this->markBookingPaidByCash($booking);

            return [
                'payment' => $payment,
                'invoice' => $invoice->fresh(),
                'booking' => $booking->fresh(),
                'amount' => $amount,
                'already_paid' => false,
                'booking_confirmed' => true,
            ];
        }, 3);
    }

    public function recordRefund(Invoice $invoice, float $amount, ?string $reason = null, ?string $method = null): Payment
    {
        $payment = $this->createPaymentRecord($invoice, [
            'invoice_id' => $invoice->id,
            'user_id' => auth()->id() ?? $invoice->user_id,
            'amount' => $amount,
            'method' => $method ?? 'bank_transfer',
            'type' => Payment::TYPE_REFUND,
            'status' => Payment::STATUS_COMPLETED,
            'transaction_id' => $method === 'stripe' ? null : $this->generateTransactionId('REF'),
            'paid_at' => now(),
            'notes' => $reason ?? 'Refund processed',
        ]);

        $this->invoiceService->syncRefundStatus($invoice);

        return $payment;
    }

    /**
     * Record a cash refund (for Cash on Delivery bookings)
     */
    public function recordCashRefund(Invoice $invoice, float $amount, ?string $reason = null): Payment
    {
        $payment = $this->createPaymentRecord($invoice, [
            'invoice_id' => $invoice->id,
            'user_id' => auth()->id() ?? $invoice->user_id,
            'amount' => $amount,
            'method' => 'cash',
            'type' => Payment::TYPE_REFUND,
            'status' => Payment::STATUS_COMPLETED,
            'transaction_id' => $this->generateTransactionId('CASH-REF'),
            'paid_at' => now(),
            'notes' => $reason ?? 'Cash refund processed',
        ]);

        $this->invoiceService->syncRefundStatus($invoice);

        return $payment;
    }

    public function markBookingPendingPayment(Booking $booking): void
    {
        if ($booking->isPending()) {
            $updates = [
                'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PENDING,
            ];

            if (! $booking->advance_payment_due_at) {
                $updates['advance_payment_due_at'] = $this->rentalRules->advancePaymentDueAt();
            }

            $booking->update($updates);
        }
    }

    public function markBookingPaidByCash(Booking $booking): void
    {
        $booking->update([
            'payment_method' => 'cash',
            'status' => Booking::STATUS_CONFIRMED,
            'advance_payment_due_at' => null,
            'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PAID,
            'advance_payment_paid_at' => now(),
        ]);
    }

    public function confirmBookingAfterRentalPayment(Booking $booking): void
    {
        $booking->refresh();

        $updates = [
            'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PAID,
            'advance_payment_paid_at' => $booking->advance_payment_paid_at ?? now(),
            'advance_payment_due_at' => null,
        ];

        if ($booking->isPending()) {
            if ($this->transitionValidator->validateBookingTransition($booking->status, Booking::STATUS_CONFIRMED, [
                'booking_id' => $booking->id,
                'action' => 'rental_payment_confirm_booking',
            ])) {
                $updates['status'] = Booking::STATUS_CONFIRMED;
            }
        }

        $booking->update($updates);

        \Log::info('Booking synchronized after rental payment success', [
            'booking' => $booking->fresh()->only([
                'id',
                'status',
                'advance_payment_status',
                'advance_payment_paid_at',
                'advance_payment_due_at',
                'security_deposit_status',
            ]),
        ]);
    }

    public function updateInvoiceStatus(Invoice $invoice): void
    {
        $this->invoiceService->syncPaymentStatus($invoice);
    }

    public function calculateInvoiceStatus(float $paidAmount, float $totalAmount): string
    {
        return $this->pricingService->calculateInvoiceStatus($paidAmount, $totalAmount);
    }

    private function createPaymentRecord(Invoice $invoice, array $attributes): Payment
    {
        return $invoice->payments()->create($attributes);
    }

    private function generateTransactionId(string $prefix): string
    {
        return $prefix.'-'.strtoupper(uniqid());
    }
}

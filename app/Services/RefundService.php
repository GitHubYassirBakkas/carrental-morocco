<?php

namespace App\Services;

use App\Mail\CashRefundProcessedMail;
use App\Mail\StripeRefundProcessedMail;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\ViewModels\RefundViewModel;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class RefundService
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly PaymentService $paymentService,
        private readonly RefundReceiptService $refundReceiptService,
        private readonly RefundPolicyService $refundPolicyService,
        private readonly StripeRefundGateway $stripeRefundGateway,
        private readonly PaymentIdempotencyService $idempotencyService,
        private readonly NotificationService $notificationService
    ) {}

    public function processRefund(Invoice $invoice, float $amount, ?string $reason = null): Payment
    {
        if ($amount <= 0) {
            throw new Exception('Refund amount must be greater than zero');
        }

        $paidAmount = $this->calculatePaidAmount($invoice);

        if ($amount > $paidAmount) {
            $existingRefund = Payment::where('invoice_id', $invoice->id)
                ->where('type', Payment::TYPE_REFUND)
                ->where('amount', $amount)
                ->where('status', Payment::STATUS_COMPLETED)
                ->first();

            if ($existingRefund) {
                return $existingRefund;
            }

            throw new Exception('Refund exceeds refundable balance');
        }

        // Detect payment method from the first payment record
        $paymentMethod = $this->detectPaymentMethod($invoice);

        // Card payments go through Stripe
        if ($paymentMethod === 'card' || $paymentMethod === 'stripe') {
            return $this->processStripeRefund($invoice, $amount, $reason);
        }

        // Other methods use the existing bank_transfer fallback
        return DB::transaction(function () use ($invoice, $amount, $reason) {
            return $this->paymentService->recordRefund($invoice, $amount, $reason);
        });
    }

    /**
     * Process a Stripe card refund
     * This handles real Stripe refund API calls with idempotency and transaction safety
     *
     * Transaction safety approach:
     * - Stripe API call happens BEFORE DB transaction
     * - If Stripe succeeds but DB fails, we can reconcile via webhook
     * - If Stripe fails, we never create a local refund record
     * - Idempotency key prevents duplicate Stripe refunds on retry
     */
    private function processStripeRefund(Invoice $invoice, float $amount, ?string $reason = null): Payment
    {
        $booking = $invoice->booking;

        if (! $booking) {
            throw new Exception('Cannot process Stripe refund: no booking found');
        }

        $paymentIntentId = $booking->rental_payment_intent_id;

        if (! $paymentIntentId) {
            throw new Exception('Cannot process Stripe refund: no rental PaymentIntent ID found');
        }

        // Generate idempotency key for this refund request
        $idempotencyKey = 'stripe_refund:'.$paymentIntentId.':'.(int) round($amount * 100);

        // Check if refund already exists locally
        $existingRefund = Payment::where('invoice_id', $invoice->id)
            ->where('type', Payment::TYPE_REFUND)
            ->where('amount', $amount)
            ->where('status', Payment::STATUS_COMPLETED)
            ->first();

        if ($existingRefund) {
            Log::info('Refund already exists locally, returning existing record', [
                'payment_id' => $existingRefund->id,
                'stripe_refund_id' => $existingRefund->stripe_refund_id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
            ]);

            return $existingRefund;
        }

        // Begin idempotency tracking
        $begin = $this->idempotencyService->begin($idempotencyKey, 'stripe_refund', $paymentIntentId);
        $idempotencyRecord = $begin['record'] ?? null;

        if ($begin['status'] === PaymentIdempotencyService::RESULT_COMPLETED) {
            // Refund was already processed, find and return it
            $completedRefund = Payment::where('invoice_id', $invoice->id)
                ->where('type', Payment::TYPE_REFUND)
                ->where('amount', $amount)
                ->where('status', Payment::STATUS_COMPLETED)
                ->first();

            if ($completedRefund) {
                return $completedRefund;
            }

            throw new Exception('Refund was marked as completed but no local record found');
        }

        if ($begin['status'] === PaymentIdempotencyService::RESULT_PROCESSING_TIMEOUT) {
            // Check if the processing record is stale (older than 5 minutes)
            // This handles cases where the previous attempt failed before Stripe API call
            if ($idempotencyRecord && $idempotencyRecord->created_at->lt(now()->subMinutes(5))) {
                // Mark as failed and allow retry via existing retry mechanism
                $this->idempotencyService->markFailed($idempotencyRecord);

                Log::warning('Stale idempotency record marked as failed, retrying', [
                    'idempotency_key' => $idempotencyKey,
                    'payment_intent_id' => $paymentIntentId,
                    'record_age_minutes' => $idempotencyRecord->created_at->diffInMinutes(now()),
                ]);

                // Retry with a new idempotency attempt using the same parameters
                // PaymentIdempotencyService::begin() will handle the failed->processing transition
                $begin = $this->idempotencyService->begin($idempotencyKey, 'stripe_refund', $paymentIntentId);
                $idempotencyRecord = $begin['record'] ?? null;
            } else {
                throw new Exception('Refund is currently being processed, please wait');
            }
        }

        // Call Stripe Refund API (outside DB transaction for safety)
        try {
            $stripeRefund = $this->stripeRefundGateway->create(
                $paymentIntentId,
                (int) round($amount * 100), // Convert to cents
                $idempotencyKey,
                [
                    'booking_id' => (string) $booking->id,
                    'invoice_id' => (string) $invoice->id,
                    'type' => 'rental_refund',
                ]
            );
        } catch (\Throwable $e) {
            // Stripe refund failed - mark idempotency as failed
            if ($idempotencyRecord) {
                $this->idempotencyService->markFailed($idempotencyRecord);
            }

            Log::error('Stripe refund API call failed', [
                'payment_intent_id' => $paymentIntentId,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Stripe refund failed: '.$e->getMessage());
        }

        // Stripe succeeded - now create local record in transaction
        $payment = DB::transaction(function () use ($invoice, $amount, $reason, $stripeRefund) {
            $payment = $this->paymentService->recordRefund($invoice, $amount, $reason, 'stripe');

            // Update with Stripe-specific information
            $payment->update([
                'transaction_id' => $stripeRefund->id ?? null,
                'stripe_refund_id' => $stripeRefund->id ?? null,
            ]);

            return $payment;
        });

        // Mark idempotency as completed (outside transaction)
        if ($idempotencyRecord) {
            $this->idempotencyService->markCompleted($idempotencyRecord);
        }

        Log::info('Stripe refund processed successfully', [
            'payment_id' => $payment->id,
            'stripe_refund_id' => $payment->stripe_refund_id,
            'payment_intent_id' => $paymentIntentId,
            'invoice_id' => $invoice->id,
            'amount' => $amount,
        ]);

        // Send refund email (outside transaction)
        // Email failure should not affect the refund record
        try {
            $this->sendStripeRefundEmail($payment, $invoice, $amount);
        } catch (\Throwable $e) {
            Log::error('Failed to send Stripe refund email', [
                'payment_id' => $payment->id,
                'stripe_refund_id' => $payment->stripe_refund_id,
                'mailable' => StripeRefundProcessedMail::class,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }

        // Create refund_success notification (outside transaction)
        // Notification failure should not affect the refund record
        try {
            $this->createRefundSuccessNotification($payment);
        } catch (Exception $e) {
            Log::error('Failed to create refund success notification', [
                'payment_id' => $payment->id,
                'stripe_refund_id' => $payment->stripe_refund_id,
                'error' => $e->getMessage(),
            ]);
        }

        return $payment;
    }

    /**
     * Send Stripe refund email
     */
    private function sendStripeRefundEmail(Payment $payment, Invoice $invoice, float $refundAmount): void
    {
        // Check if email already sent (idempotency)
        if ($payment->email_sent_at) {
            Log::info('Stripe refund email already sent, skipping', [
                'payment_id' => $payment->id,
                'stripe_refund_id' => $payment->stripe_refund_id,
                'email_sent_at' => $payment->email_sent_at,
            ]);

            return;
        }

        $booking = $invoice->booking;
        $customerEmail = $booking->user->email;

        if (! $customerEmail) {
            return;
        }

        // Calculate refund type using Invoice context
        $refundType = $this->calculateRefundType($invoice, $refundAmount);

        // Calculate original amount
        $originalAmount = $invoice->payments()
            ->where('type', Payment::TYPE_PAYMENT)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        Mail::to($customerEmail)->send(new StripeRefundProcessedMail(
            $payment,
            $refundType,
            $originalAmount
        ));

        // Mark email as sent
        $payment->update(['email_sent_at' => now()]);

        Log::info('Stripe refund email sent successfully', [
            'payment_id' => $payment->id,
            'stripe_refund_id' => $payment->stripe_refund_id,
            'customer_email' => $customerEmail,
        ]);
    }

    /**
     * Create refund_success notification (idempotent)
     */
    private function createRefundSuccessNotification(Payment $payment): void
    {
        $invoice = $payment->invoice;
        $booking = $invoice->booking;

        // Check if notification already exists (idempotency)
        $notificationExists = \App\Models\Notification::where('user_id', $booking->user_id)
            ->where('type', 'refund_success')
            ->whereJsonContains('data->payment_id', $payment->id)
            ->exists();

        if ($notificationExists) {
            return;
        }

        // Calculate refund type
        $originalAmount = $invoice->payments()
            ->where('type', Payment::TYPE_PAYMENT)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        $refundType = $payment->amount >= $originalAmount ? 'full' : 'partial';

        $this->notificationService->create(
            $booking->user_id,
            'refund_success',
            __('messages.notification_refund_success'),
            __('messages.notification_refund_success_message', [
                'amount' => number_format($payment->amount, 2),
                'currency' => 'MAD',
                'booking_id' => $booking->id,
            ]),
            [
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
                'invoice_id' => $invoice->id,
                'amount' => $payment->amount,
                'currency' => 'MAD',
                'refund_type' => $refundType,
                'stripe_refund_id' => $payment->stripe_refund_id,
                'payment_method' => $payment->method,
            ]
        );
    }

    /**
     * Process a cash refund (for Cash on Delivery bookings)
     */
    public function processCashRefund(Invoice $invoice, float $amount, ?string $reason = null): Payment
    {
        if ($amount <= 0) {
            throw new Exception('Refund amount must be greater than zero');
        }

        $paidAmount = $this->calculatePaidAmount($invoice);

        if ($amount > $paidAmount) {
            throw new Exception('Refund exceeds refundable balance');
        }

        // Record refund in database transaction
        $payment = DB::transaction(function () use ($invoice, $amount, $reason) {
            return $this->paymentService->recordCashRefund($invoice, $amount, $reason);
        });

        // Send refund email with PDF attachment (outside transaction)
        // Email failure should not affect the refund record
        try {
            $this->sendCashRefundEmail($payment, $invoice, $amount);
        } catch (\Throwable $e) {
            Log::error('Failed to send cash refund email', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'mailable' => CashRefundProcessedMail::class,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }

        return $payment;
    }

    /**
     * Send cash refund email with PDF receipt attachment
     */
    private function sendCashRefundEmail(Payment $payment, Invoice $invoice, float $refundAmount): void
    {
        // Check if email already sent (idempotency)
        if ($payment->email_sent_at) {
            Log::info('Cash refund email already sent, skipping', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'email_sent_at' => $payment->email_sent_at,
            ]);

            return;
        }

        $booking = $invoice->booking;
        $customerEmail = $booking->user->email;

        if (! $customerEmail) {
            return;
        }

        // Calculate refund type using Invoice context
        $refundType = $this->calculateRefundType($invoice, $refundAmount);

        // Calculate original amount
        $originalAmount = $invoice->payments()
            ->where('type', Payment::TYPE_PAYMENT)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        // Generate PDF for email attachment
        $pdfPath = $this->refundReceiptService->generateRefundReceiptForEmail($payment);

        try {
            // Send email with PDF attachment
            Mail::to($customerEmail)->send(new CashRefundProcessedMail(
                $payment,
                $refundType,
                $originalAmount,
                $pdfPath
            ));

            // Mark email as sent
            $payment->update(['email_sent_at' => now()]);

            Log::info('Cash refund email sent successfully', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'customer_email' => $customerEmail,
            ]);
        } finally {
            // Clean up temporary PDF file regardless of success or failure
            if (file_exists($pdfPath)) {
                @unlink($pdfPath);
            }
        }
    }

    public function calculatePaidAmount(Invoice $invoice): float
    {
        return $this->invoiceService->calculatePaidAmount($invoice);
    }

    /**
     * Process refund for a booking cancellation
     * This is the main entry point for all booking cancellation refunds
     *
     * @param  Booking  $booking  The booking being cancelled
     * @param  Invoice|null  $invoice  The booking's invoice
     * @param  string|null  $reason  Cancellation reason
     * @return Payment|null Returns the refund payment if refund was processed, null if no refund
     */
    public function processBookingCancellationRefund(Booking $booking, ?Invoice $invoice, ?string $reason = null): ?Payment
    {
        if (! $invoice) {
            return null;
        }

        $paidAmount = $this->calculatePaidAmount($invoice);

        if ($paidAmount <= 0) {
            return null;
        }

        // Get refund policy decision from RefundPolicyService (policy layer)
        $policy = $this->refundPolicyService->evaluateCancellation($booking, 'customer');

        if (! $policy['refund_eligible'] || $policy['refund_amount'] <= 0) {
            return null;
        }

        $refundAmount = min($policy['refund_amount'], $paidAmount);

        if ($refundAmount <= 0) {
            return null;
        }

        $paymentMethod = $policy['refund_method'] ?? $this->detectPaymentMethod($invoice);

        if ($paymentMethod === 'cash') {
            return $this->processCashRefund(
                $invoice,
                $refundAmount,
                $reason ?? $policy['policy_reason'] ?? 'Booking cancelled - Cash on Delivery refund'
            );
        } else {
            return $this->processRefund(
                $invoice,
                $refundAmount,
                $reason ?? $policy['policy_reason'] ?? 'Booking cancelled'
            );
        }
    }

    /**
     * Detect the payment method from the first payment record
     * This determines HOW the booking was intended to be paid (cash or card)
     *
     * Rule 6: Priority - Original payment method > Fallback method > null
     *
     * @return string|null Returns 'cash', 'card', or null if no payment exists
     */
    private function detectPaymentMethod(Invoice $invoice): ?string
    {
        // Priority 1: Original payment method
        $payment = $invoice->payments()
            ->where('type', Payment::TYPE_PAYMENT)
            ->oldest()
            ->first();

        if ($payment && $payment->method) {
            return $payment->method;
        }

        // Priority 2: Manual fallback method from settings.
        $fallback = (string) \App\Models\Setting::get('refund_default_method', 'cash');

        return in_array($fallback, RefundPolicyService::FALLBACK_REFUND_METHODS, true)
            ? $fallback
            : 'cash';
    }

    /**
     * Calculate refund type based on invoice context and refund amount
     *
     * @return string 'full', 'partial', or 'none'
     */
    public function calculateRefundType(Invoice $invoice, float $refundAmount): string
    {
        $originalAmount = $invoice->payments()
            ->where('type', Payment::TYPE_PAYMENT)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        if ($refundAmount >= $originalAmount) {
            return 'full';
        } elseif ($refundAmount <= 0) {
            return 'none';
        }

        return 'partial';
    }

    /**
     * Get refund history with filters
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getRefundHistory(array $filters = [])
    {
        $query = Payment::with(['invoice.booking.user', 'invoice.booking.car', 'user'])
            ->where('type', Payment::TYPE_REFUND)
            ->latest();

        // Apply filters
        if (! empty($filters['customer'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['customer'].'%')
                    ->orWhere('email', 'like', '%'.$filters['customer'].'%');
            });
        }

        if (! empty($filters['booking_reference'])) {
            $query->whereHas('invoice.booking', function ($q) use ($filters) {
                $q->where('id', $filters['booking_reference']);
            });
        }

        if (! empty($filters['method'])) {
            $query->where('method', $filters['method']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('paid_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('paid_at', '<=', $filters['to_date']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('transaction_id', 'like', '%'.$filters['search'].'%')
                    ->orWhere('notes', 'like', '%'.$filters['search'].'%');
            });
        }

        $refunds = $query->paginate(20);

        // Transform to ViewModels using mapper
        $refunds->setCollection(
            $refunds->getCollection()
                ->map(fn (Payment $payment) => $this->toRefundViewModel($payment))
        );

        // Apply refund type filter if needed
        if (! empty($filters['refund_type'])) {
            $refunds->setCollection(
                $refunds->getCollection()
                    ->filter(fn (RefundViewModel $viewModel) => $viewModel->refundType === $filters['refund_type'])
            );
        }

        return $refunds;
    }

    /**
     * Map Payment to RefundViewModel
     */
    private function toRefundViewModel(Payment $payment): RefundViewModel
    {
        $refundType = $this->calculateRefundType($payment->invoice, $payment->amount);

        return new RefundViewModel(
            refundReference: $payment->transaction_id ?? 'N/A',
            customerName: $payment->user->name ?? '—',
            bookingReference: '#'.($payment->invoice->booking->id ?? '—'),
            carName: $payment->invoice->booking->car->name ?? '—',
            refundAmount: $payment->amount,
            refundMethod: ucfirst($payment->method),
            refundStatus: ucfirst($payment->status),
            refundDate: $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : '—',
            refundType: $refundType,
            paymentId: $payment->id,
            bookingId: $payment->invoice->booking->id ?? 0
        );
    }

    /**
     * Get refund status for a booking
     *
     * @return string|null Returns 'refunded', 'partial', 'none', or null if not cancelled
     */
    public function getRefundStatus(Booking $booking): ?string
    {
        // If booking is not cancelled, return null immediately (performance optimization)
        if ($booking->status !== Booking::STATUS_CANCELLED) {
            return null;
        }

        $invoice = $booking->invoice;
        if (! $invoice) {
            return 'none';
        }

        // Calculate original paid amount
        $originalAmount = $invoice->payments()
            ->where('type', Payment::TYPE_PAYMENT)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        // Calculate total refunded amount
        $refundedAmount = $invoice->payments()
            ->where('type', Payment::TYPE_REFUND)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        // No refund exists
        if ($refundedAmount <= 0) {
            return 'none';
        }

        // Full refund
        if ($refundedAmount >= $originalAmount) {
            return 'refunded';
        }

        // Partial refund
        return 'partial';
    }

    /**
     * Prepare bookings for customer view with refund status
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $bookings
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function prepareBookingsForCustomer($bookings)
    {
        return $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'reference' => $booking->reference ?? str_pad($booking->id, 6, '0', STR_PAD_LEFT),
                'status' => $booking->status,
                'car_brand' => $booking->car->brand ?? '',
                'car_model' => $booking->car->model ?? '',
                'car_image_url' => $booking->car->image_url ?? '',
                'has_review' => $booking->review !== null,
                'start_date' => $booking->start_date,
                'end_date' => $booking->end_date,
                'total_amount' => $booking->total_amount,
                'pickup_location_name' => $booking->pickupLocation->name ?? '',
                'dropoff_location_name' => $booking->dropoffLocation->name ?? '',
                'refund_status' => $this->getRefundStatus($booking),
                'can_cancel' => $this->refundPolicyService->canCustomerCancel($booking),
            ];
        });
    }

    /**
     * Get refund statistics for dashboard
     */
    public function getRefundStatistics(): array
    {
        // Use SQL aggregates for efficient counting and summing
        $stats = Payment::where('type', Payment::TYPE_REFUND)
            ->selectRaw('
                COUNT(*) as total_refunds,
                SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as total_refunded_amount,
                SUM(CASE WHEN method = ? THEN 1 ELSE 0 END) as cash_refunds,
                SUM(CASE WHEN method IN (?, ?) THEN 1 ELSE 0 END) as stripe_refunds
            ', [Payment::STATUS_COMPLETED, 'cash', 'stripe', 'card'])
            ->first();

        // Calculate partial refunds using subquery for efficiency
        $partialRefunds = Payment::where('type', Payment::TYPE_REFUND)
            ->whereHas('invoice', function ($query) {
                $query->whereHas('payments', function ($q) {
                    $q->where('type', Payment::TYPE_PAYMENT)
                        ->where('status', Payment::STATUS_COMPLETED);
                });
            })
            ->where('amount', '<', function ($query) {
                $query->selectRaw('COALESCE(SUM(amount), 0)')
                    ->from('payments as p')
                    ->whereColumn('p.invoice_id', 'payments.invoice_id')
                    ->where('p.type', Payment::TYPE_PAYMENT)
                    ->where('p.status', Payment::STATUS_COMPLETED);
            })
            ->count();

        return [
            'total_refunds' => (int) $stats->total_refunds,
            'total_refunded_amount' => (float) ($stats->total_refunded_amount ?? 0),
            'cash_refunds' => (int) $stats->cash_refunds,
            'stripe_refunds' => (int) $stats->stripe_refunds,
            'partial_refunds' => $partialRefunds,
        ];
    }

    /**
     * Download refund receipt (wrapper for RefundReceiptService)
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadRefundReceipt(Payment $payment)
    {
        if ($payment->type !== Payment::TYPE_REFUND) {
            throw new InvalidArgumentException('Payment is not a refund.');
        }

        return $this->refundReceiptService->downloadRefundReceipt($payment);
    }

    /**
     * Resend refund email
     */
    public function resendRefundEmail(Payment $payment): void
    {
        if ($payment->type !== Payment::TYPE_REFUND) {
            throw new InvalidArgumentException('Payment is not a refund.');
        }

        $invoice = $payment->invoice;
        $booking = $invoice->booking;
        $customerEmail = $booking->user->email;

        if (! $customerEmail) {
            Log::warning('Cannot resend refund email - no customer email', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
            ]);

            return;
        }

        // Calculate refund type using Invoice context
        $refundType = $this->calculateRefundType($invoice, $payment->amount);

        // Calculate original amount
        $originalAmount = $invoice->payments()
            ->where('type', Payment::TYPE_PAYMENT)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        if ($payment->method === 'stripe' || $payment->method === 'card' || $payment->stripe_refund_id) {
            try {
                Mail::to($customerEmail)->send(new StripeRefundProcessedMail(
                    $payment,
                    $refundType,
                    $originalAmount
                ));

                Log::info('Stripe refund email resent successfully', [
                    'payment_id' => $payment->id,
                    'stripe_refund_id' => $payment->stripe_refund_id,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to resend Stripe refund email', [
                    'payment_id' => $payment->id,
                    'stripe_refund_id' => $payment->stripe_refund_id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }

            return;
        }

        // Generate PDF for cash refund email attachment
        $pdfPath = $this->refundReceiptService->generateRefundReceiptForEmail($payment);

        try {
            // Send email with PDF attachment
            Mail::to($customerEmail)->send(new CashRefundProcessedMail(
                $payment,
                $refundType,
                $originalAmount,
                $pdfPath
            ));

            Log::info('Cash refund email resent successfully', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to resend cash refund email', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            // Clean up temporary PDF file regardless of success or failure
            if (file_exists($pdfPath)) {
                @unlink($pdfPath);
            }
        }
    }
}

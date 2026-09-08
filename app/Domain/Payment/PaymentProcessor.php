<?php

namespace App\Domain\Payment;

use App\Domain\Booking\BookingStateMachine;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\OutboxEvent;
use App\Models\Payment;
use App\Models\PaymentIdempotencyKey;
use App\Services\InvoiceService;
use App\Services\NotificationService;
use App\Services\PaymentIdempotencyService;
use App\Services\PaymentService;
use App\Services\PaymentWebhookLockService;
use App\Services\PaymentWebhookMetrics;
use App\Services\SecurityDepositService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PaymentProcessor
{
    public function __construct(
        private readonly PaymentIdempotencyService $idempotency,
        private readonly PaymentWebhookLockService $locks,
        private readonly PaymentWebhookMetrics $metrics,
        private readonly BookingStateMachine $bookingStateMachine,
        private readonly SecurityDepositService $securityDepositService,
        private readonly PaymentService $paymentService,
        private readonly InvoiceService $invoiceService,
        private readonly NotificationService $notificationService,
    ) {}

    public function processWebhook(
        object $event,
        ?int $bookingId,
        ?string $paymentIntentId,
        ?string $metadataType,
        array $metadata,
        ?string $paymentIntentStatus,
        ?object $object
    ): string {
        $eventId = $event->id ?? null;
        $eventType = $event->type ?? 'unknown';
        $idempotencyRecord = null;
        $idempotencyStatus = 'not_applicable';
        $needsRefundReconciliation = $this->needsRentalRefundReconciliation($eventType, $metadataType, $object);

        try {
            if ($eventId) {
                $begin = $this->idempotency->begin($eventId, $eventId, $paymentIntentId);
                $idempotencyStatus = $begin['status'];

                if ($idempotencyStatus === PaymentIdempotencyService::RESULT_COMPLETED && ! $needsRefundReconciliation) {
                    $this->metrics->duplicate($this->structuredContext($eventId, $bookingId, $paymentIntentId, 'not_required', $idempotencyStatus));

                    Log::info('payment.webhook.duplicate_event', $this->structuredContext(
                        $eventId,
                        $bookingId,
                        $paymentIntentId,
                        'not_required',
                        $idempotencyStatus
                    ));

                    return 'duplicate';
                }

                if ($idempotencyStatus === PaymentIdempotencyService::RESULT_COMPLETED && $needsRefundReconciliation) {
                    Log::info('payment.webhook.completed_event_reopened_for_refund_reconciliation', [
                        'event_id' => $eventId,
                        'event_type' => $eventType,
                        'booking_id' => $bookingId,
                        'payment_intent_id' => $paymentIntentId,
                        'refund_id' => $object->id ?? null,
                    ]);
                }

                if ($idempotencyStatus === PaymentIdempotencyService::RESULT_PROCESSING_TIMEOUT) {
                    throw new RuntimeException('Webhook idempotency key is still processing.');
                }

                $idempotencyRecord = $begin['record'] ?? null;
            }

            $outcome = $bookingId
                ? $this->locks->withBookingLock($bookingId, fn () => $this->processWebhookDecision(
                    $event,
                    $eventType,
                    $paymentIntentId,
                    $bookingId,
                    $metadataType,
                    $metadata,
                    $paymentIntentStatus,
                    $object
                ))
                : $this->processWebhookDecision(
                    $event,
                    $eventType,
                    $paymentIntentId,
                    $bookingId,
                    $metadataType,
                    $metadata,
                    $paymentIntentStatus,
                    $object
                );

            $lockStatus = $bookingId ? $this->locks->lastStatus() : 'not_required';

            if ($outcome === null) {
                $this->metrics->lockTimeout($this->structuredContext($eventId, $bookingId, $paymentIntentId, $lockStatus, $idempotencyStatus));

                throw new RuntimeException('Webhook booking lock was not acquired before timeout.');
            }

            if ($idempotencyRecord instanceof PaymentIdempotencyKey) {
                $this->idempotency->markCompleted($idempotencyRecord);
            }

            $this->metrics->success($this->structuredContext($eventId, $bookingId, $paymentIntentId, $lockStatus, $idempotencyStatus));

            Log::info('payment.webhook.processed', array_merge(
                $this->structuredContext($eventId, $bookingId, $paymentIntentId, $lockStatus, $idempotencyStatus),
                ['outcome' => $outcome ?: 'ignored']
            ));

            return $outcome ?: 'ignored';
        } catch (Throwable $e) {
            if ($idempotencyRecord instanceof PaymentIdempotencyKey) {
                $this->idempotency->markFailed($idempotencyRecord);
            }

            throw $e;
        }
    }

    private function processWebhookDecision(
        object $event,
        string $eventType,
        ?string $paymentIntentId,
        ?int $bookingId,
        ?string $metadataType,
        array $metadata,
        ?string $paymentIntentStatus,
        ?object $object
    ): string {
        if (str_starts_with($eventType, 'charge.')) {
            Log::info('Stripe webhook ignored charge event.', [
                'event_id' => $event->id ?? null,
                'event_type' => $eventType,
                'payment_intent_id' => $paymentIntentId,
            ]);

            return 'ignored';
        }

        if (in_array($eventType, [
            'payment_intent.amount_capturable_updated',
            'payment_intent.canceled',
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
        ], true) && $metadataType === 'security_deposit') {
            if (! $bookingId || ! $object) {
                Log::error('invalid security deposit metadata', [
                    'event_type' => $eventType,
                    'event_id' => $event->id ?? null,
                    'payment_intent_id' => $paymentIntentId,
                    'booking_id' => $bookingId,
                    'status' => $paymentIntentStatus,
                ]);

                return 'ignored';
            }

            $syncResult = $this->securityDepositService->syncFromStripeIntent(
                $object,
                $eventType,
                $event->id ?? null
            );

            Log::info('Security deposit webhook synchronized.', [
                'event_type' => $eventType,
                'event_id' => $event->id ?? null,
                'booking_id' => $bookingId,
                'payment_intent_id' => $paymentIntentId,
                'previous_status' => $syncResult['previous_status'] ?? null,
                'new_status' => $syncResult['new_status'] ?? null,
                'action' => $syncResult['action'] ?? null,
            ]);

            $action = $syncResult['action'] ?? 'ignore';

            return str_starts_with($action, 'ignore') ? 'ignored' : 'processed';
        }

        if (str_starts_with($eventType, 'payment_intent.') && $eventType !== 'payment_intent.succeeded') {
            Log::info('Stripe webhook ignored unrelated payment intent event.', [
                'event_id' => $event->id ?? null,
                'event_type' => $eventType,
                'payment_intent_id' => $paymentIntentId,
                'booking_id' => $bookingId,
                'type' => $metadataType,
                'payment_intent_status' => $paymentIntentStatus,
            ]);

            return 'ignored';
        }

        if ($eventType === 'payment_intent.succeeded' && $metadataType !== 'rental') {
            Log::info('Stripe webhook ignored non-rental success event.', [
                'event_id' => $event->id ?? null,
                'payment_intent_id' => $paymentIntentId,
                'booking_id' => $bookingId,
                'type' => $metadataType,
                'payment_intent_status' => $paymentIntentStatus,
            ]);

            return 'ignored';
        }

        if ($eventType === 'payment_intent.succeeded' && $paymentIntentId && $bookingId && $metadataType === 'rental' && $object) {
            $syncResult = $this->syncSuccessfulRentalPaymentFromWebhook(
                $bookingId,
                isset($metadata['invoice_id']) ? (int) $metadata['invoice_id'] : null,
                $paymentIntentId,
                $object,
                $event->id ?? null
            );

            Log::info('Stripe rental webhook synchronized.', [
                'event_id' => $event->id ?? null,
                'payment_intent_id' => $paymentIntentId,
                'booking_id' => $bookingId,
                'invoice_id' => $syncResult['invoice_id'] ?? null,
                'payment_created' => $syncResult['payment_created'] ?? false,
                'booking_status' => $syncResult['booking_status'] ?? null,
                'invoice_status' => $syncResult['invoice_status'] ?? null,
            ]);

            return 'processed';
        }

        // Handle rental refund webhooks
        if (in_array($eventType, ['refund.created', 'refund.updated', 'refund.succeeded', 'refund.failed'], true) && $metadataType === 'rental_refund') {
            if (! $object) {
                Log::warning('Stripe rental refund webhook ignored: missing refund object.', [
                    'event_id' => $event->id ?? null,
                    'event_type' => $eventType,
                    'payment_intent_id' => $paymentIntentId,
                ]);

                return 'ignored';
            }

            $syncResult = $this->syncRentalRefundFromWebhook(
                $eventType,
                $object,
                $event->id ?? null
            );

            Log::info('Stripe rental refund webhook synchronized.', [
                'event_id' => $event->id ?? null,
                'event_type' => $eventType,
                'refund_id' => $object->id ?? null,
                'payment_intent_id' => $paymentIntentId,
                'action' => $syncResult['action'] ?? 'ignore',
            ]);

            return $syncResult['action'] ?? 'ignored';
        }

        return 'ignored';
    }

    private function syncSuccessfulRentalPaymentFromWebhook(
        int $bookingId,
        ?int $invoiceId,
        string $paymentIntentId,
        object $paymentIntent,
        ?string $eventId
    ): array {
        return DB::transaction(function () use ($bookingId, $invoiceId, $paymentIntentId, $paymentIntent, $eventId) {
            $traceId = (string) Str::uuid();
            $booking = Booking::whereKey($bookingId)->lockForUpdate()->first();

            if (! $booking) {
                Log::warning('Stripe webhook booking not found.', [
                    'event_id' => $eventId,
                    'payment_intent_id' => $paymentIntentId,
                    'booking_id' => $bookingId,
                    'invoice_id' => $invoiceId,
                ]);

                return [
                    'payment_created' => false,
                    'booking_status' => null,
                    'invoice_status' => null,
                    'invoice_id' => $invoiceId,
                ];
            }

            if (in_array($booking->status, [Booking::STATUS_COMPLETED, Booking::STATUS_CANCELLED], true)) {
                Log::warning('Stripe rental webhook rejected: terminal booking state.', [
                    'event_id' => $eventId,
                    'payment_intent_id' => $paymentIntentId,
                    'booking_id' => $bookingId,
                    'invoice_id' => $invoiceId,
                    'previous_status' => $booking->status,
                    'new_status' => $booking->status,
                    'action' => 'reject_invalid_transition',
                ]);

                throw new RuntimeException('Cannot mutate rental payment state after terminal booking state.');
            }

            $invoice = null;

            if ($invoiceId) {
                $invoice = Invoice::whereKey($invoiceId)
                    ->where('booking_id', $booking->id)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $invoice) {
                $invoice = $booking->invoice()
                    ->lockForUpdate()
                    ->first();
            }

            if (! $invoice) {
                $invoice = $this->invoiceService->firstOrCreateForPaymentProcessing($booking);
            }

            $payment = Payment::where('transaction_id', $paymentIntentId)
                ->lockForUpdate()
                ->first();

            $paymentCreated = false;
            $paymentAmount = (($paymentIntent->amount_received ?? $paymentIntent->amount ?? ((float) $booking->total_amount * 100)) / 100);

            if (! $payment) {
                $payment = $invoice->payments()->create([
                    'user_id' => $booking->user_id,
                    'amount' => $paymentAmount,
                    'method' => 'card',
                    'type' => Payment::TYPE_PAYMENT,
                    'status' => Payment::STATUS_COMPLETED,
                    'paid_at' => now(),
                    'transaction_id' => $paymentIntentId,
                    'notes' => 'Stripe webhook payment confirmation',
                ]);

                $paymentCreated = true;
            } elseif ((int) $payment->invoice_id !== (int) $invoice->id) {
                Log::error('Stripe webhook payment intent is already linked to a different invoice.', [
                    'event_id' => $eventId,
                    'payment_intent_id' => $paymentIntentId,
                    'expected_invoice_id' => $invoice->id,
                    'actual_invoice_id' => $payment->invoice_id,
                    'booking_id' => $booking->id,
                ]);
            } elseif ($payment->status !== Payment::STATUS_COMPLETED || ! $payment->paid_at) {
                $payment->update([
                    'status' => Payment::STATUS_COMPLETED,
                    'paid_at' => $payment->paid_at ?? now(),
                    'amount' => $paymentAmount,
                    'method' => 'card',
                    'type' => Payment::TYPE_PAYMENT,
                ]);
            }

            $this->paymentService->updateInvoiceStatus($invoice);

            $this->confirmBookingAfterRentalPayment($booking, $eventId, $paymentIntentId, $traceId);

            // ✅ CREATE NOTIFICATION FOR NEW PAYMENT
            if ($paymentCreated) {
                $this->notificationService->create(
                    $booking->user_id,
                    'payment_success',
                    __('messages.notification_payment_success'),
                    __('messages.notification_payment_success_message', [
                        'amount' => number_format($paymentAmount, 2),
                        'currency' => 'MAD',
                    ]),
                    [
                        'payment_id' => $payment->id,
                        'booking_id' => $booking->id,
                        'invoice_id' => $invoice->id,
                        'amount' => $paymentAmount,
                        'currency' => 'MAD',
                        'payment_method' => 'card',
                        'transaction_id' => $paymentIntentId,
                    ]
                );
            }

            $freshInvoice = $invoice->fresh();
            $freshBooking = $booking->fresh();

            OutboxEvent::create([
                'event_type' => 'rental_payment_synchronized',
                'trace_id' => $traceId,
                'source_event_id' => $eventId,
                'payload' => [
                    'event_id' => $eventId,
                    'trace_id' => $traceId,
                    'correlation_id' => $traceId,
                    'source_event_id' => $eventId,
                    'booking_id' => $freshBooking?->id,
                    'invoice_id' => $freshInvoice?->id,
                    'payment_intent_id' => $paymentIntentId,
                    'payment_created' => $paymentCreated,
                    'booking_status' => $freshBooking?->status,
                    'invoice_status' => $freshInvoice?->status,
                ],
            ]);

            return [
                'payment_created' => $paymentCreated,
                'booking_status' => $freshBooking?->status,
                'invoice_status' => $freshInvoice?->status,
                'invoice_id' => $freshInvoice?->id,
            ];
        }, 3);
    }

    private function confirmBookingAfterRentalPayment(Booking $booking, ?string $eventId, ?string $paymentIntentId, string $traceId): void
    {
        $booking->refresh();

        $updates = [
            'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PAID,
            'advance_payment_paid_at' => $booking->advance_payment_paid_at ?? now(),
            'advance_payment_due_at' => null,
        ];

        if ($booking->isPending()) {
            $this->bookingStateMachine->transition($booking, Booking::STATUS_CONFIRMED, [
                'source' => 'payment_processor',
                'source_event_id' => $eventId,
                'trace_id' => $traceId,
                'booking_id' => $booking->id,
                'payment_intent_id' => $paymentIntentId,
                'action' => 'rental_payment_confirm_booking',
            ]);
        }

        $booking->update($updates);

        Log::info('Booking synchronized after rental payment success', [
            'event_id' => $eventId,
            'payment_intent_id' => $paymentIntentId,
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

    public function syncRentalRefundFromWebhook(
        string $eventType,
        object $refund,
        ?string $eventId
    ): array {
        return DB::transaction(function () use ($eventType, $refund, $eventId) {
            $refundId = $refund->id ?? null;

            if (! $refundId) {
                Log::error('Stripe refund webhook missing refund ID', [
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                ]);

                return ['action' => 'ignore'];
            }

            $payment = Payment::where(function ($query) use ($refundId) {
                $query->where('stripe_refund_id', $refundId)
                    ->orWhere('transaction_id', $refundId);
            })
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                $metadata = $this->metadataFrom($refund);
                $metadataType = isset($metadata['type']) ? (string) $metadata['type'] : null;
                $bookingId = isset($metadata['booking_id']) ? (int) $metadata['booking_id'] : null;
                $invoiceId = isset($metadata['invoice_id']) ? (int) $metadata['invoice_id'] : null;
                $paymentIntentId = $this->stringValue($refund->payment_intent ?? null);
                $refundAmountCents = isset($refund->amount) ? (int) $refund->amount : 0;
                $refundAmount = $refundAmountCents / 100;

                if ($metadataType !== 'rental_refund' || ! $bookingId || ! $invoiceId || ! $paymentIntentId || $refundAmountCents <= 0) {
                    Log::warning('Stripe rental refund webhook ignored: invalid refund metadata.', [
                        'event_id' => $eventId,
                        'event_type' => $eventType,
                        'stripe_refund_id' => $refundId,
                        'booking_id' => $bookingId,
                        'invoice_id' => $invoiceId,
                        'metadata_type' => $metadataType,
                        'payment_intent_id' => $paymentIntentId,
                        'amount' => $refundAmountCents,
                    ]);

                    return ['action' => 'ignore'];
                }

                $booking = Booking::whereKey($bookingId)
                    ->lockForUpdate()
                    ->first();

                if (! $booking) {
                    Log::warning('Stripe rental refund webhook ignored: booking not found.', [
                        'event_id' => $eventId,
                        'event_type' => $eventType,
                        'stripe_refund_id' => $refundId,
                        'booking_id' => $bookingId,
                    ]);

                    return ['action' => 'ignore'];
                }

                $invoice = Invoice::whereKey($invoiceId)
                    ->lockForUpdate()
                    ->first();

                if (! $invoice || (int) $invoice->booking_id !== $bookingId) {
                    Log::warning('Stripe rental refund webhook ignored: invoice booking mismatch.', [
                        'event_id' => $eventId,
                        'event_type' => $eventType,
                        'stripe_refund_id' => $refundId,
                        'booking_id' => $bookingId,
                        'invoice_id' => $invoiceId,
                        'actual_booking_id' => $invoice?->booking_id,
                    ]);

                    return ['action' => 'ignore'];
                }

                if ((string) $booking->rental_payment_intent_id !== $paymentIntentId) {
                    Log::warning('Stripe rental refund webhook ignored: booking PaymentIntent mismatch.', [
                        'event_id' => $eventId,
                        'event_type' => $eventType,
                        'stripe_refund_id' => $refundId,
                        'booking_id' => $bookingId,
                        'invoice_id' => $invoiceId,
                        'expected_payment_intent_id' => $booking->rental_payment_intent_id,
                        'actual_payment_intent_id' => $paymentIntentId,
                    ]);

                    return ['action' => 'ignore'];
                }

                $originalPayment = Payment::where('invoice_id', $invoice->id)
                    ->where('type', Payment::TYPE_PAYMENT)
                    ->where('status', Payment::STATUS_COMPLETED)
                    ->where('transaction_id', $paymentIntentId)
                    ->lockForUpdate()
                    ->first();

                if (! $originalPayment) {
                    Log::warning('Stripe rental refund webhook ignored: original payment not found.', [
                        'event_id' => $eventId,
                        'event_type' => $eventType,
                        'stripe_refund_id' => $refundId,
                        'booking_id' => $bookingId,
                        'invoice_id' => $invoiceId,
                        'payment_intent_id' => $paymentIntentId,
                    ]);

                    return ['action' => 'ignore'];
                }

                $payment = Payment::where(function ($query) use ($refundId) {
                    $query->where('stripe_refund_id', $refundId)
                        ->orWhere('transaction_id', $refundId);
                })
                    ->lockForUpdate()
                    ->first();

                if ($payment) {
                    return $this->syncExistingRentalRefundPayment($payment, $eventType, $refund, $refundId, $eventId);
                }

                $refundableBalance = $this->refundableBalance($invoice);

                if ($refundAmount > $refundableBalance) {
                    Log::warning('Stripe rental refund webhook ignored: refund amount exceeds local refundable balance.', [
                        'event_id' => $eventId,
                        'event_type' => $eventType,
                        'stripe_refund_id' => $refundId,
                        'booking_id' => $bookingId,
                        'invoice_id' => $invoiceId,
                        'payment_intent_id' => $paymentIntentId,
                        'refund_amount' => $refundAmount,
                        'refundable_balance' => $refundableBalance,
                    ]);

                    return ['action' => 'ignore'];
                }

                $localStatus = $this->localRefundStatus($eventType, $refund);

                $payment = Payment::create([
                    'invoice_id' => $invoice->id,
                    'user_id' => $booking->user_id,
                    'amount' => $refundAmount,
                    'method' => 'stripe',
                    'type' => Payment::TYPE_REFUND,
                    'status' => $localStatus,
                    'transaction_id' => $refundId,
                    'stripe_refund_id' => $refundId,
                    'paid_at' => $localStatus === Payment::STATUS_COMPLETED ? now() : null,
                    'notes' => $localStatus === Payment::STATUS_FAILED
                        ? 'Stripe webhook refund reconciliation | Stripe refund failed: '.($refund->failure_reason ?? 'Unknown')
                        : 'Stripe webhook refund reconciliation',
                ]);

                if ($localStatus === Payment::STATUS_COMPLETED) {
                    $this->invoiceService->syncRefundStatus($invoice);
                    $this->createRefundSuccessNotification($payment);
                } elseif ($localStatus === Payment::STATUS_FAILED) {
                    $this->createRefundFailedNotification($payment, $refund->failure_reason ?? 'Unknown');
                }

                Log::info('Stripe rental refund webhook reconstructed missing local refund.', [
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                    'stripe_refund_id' => $refundId,
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'booking_id' => $booking->id,
                    'status' => $localStatus,
                    'amount' => $refundAmount,
                ]);

                return ['action' => 'processed', 'payment_created' => true, 'payment_id' => $payment->id];
            }

            return $this->syncExistingRentalRefundPayment($payment, $eventType, $refund, $refundId, $eventId);
        }, 3);
    }

    private function syncExistingRentalRefundPayment(Payment $payment, string $eventType, object $refund, string $refundId, ?string $eventId): array
    {
        $localStatus = $this->localRefundStatus($eventType, $refund);

        if ($localStatus === Payment::STATUS_COMPLETED) {
            if ($payment->status !== Payment::STATUS_COMPLETED) {
                $payment->update([
                    'status' => Payment::STATUS_COMPLETED,
                    'paid_at' => $payment->paid_at ?? now(),
                    'stripe_refund_id' => $payment->stripe_refund_id ?? $refundId,
                    'transaction_id' => $payment->transaction_id ?? $refundId,
                ]);

                Log::info('Stripe refund status updated to completed via webhook', [
                    'payment_id' => $payment->id,
                    'stripe_refund_id' => $refundId,
                    'event_id' => $eventId,
                ]);
            }

            $this->invoiceService->syncRefundStatus($payment->invoice);
            $this->createRefundSuccessNotification($payment);

            return ['action' => 'processed', 'payment_created' => false, 'payment_id' => $payment->id];
        }

        if ($localStatus === Payment::STATUS_FAILED) {
            if ($payment->status !== Payment::STATUS_COMPLETED && $payment->status !== Payment::STATUS_FAILED) {
                $payment->update([
                    'status' => Payment::STATUS_FAILED,
                    'stripe_refund_id' => $payment->stripe_refund_id ?? $refundId,
                    'transaction_id' => $payment->transaction_id ?? $refundId,
                    'notes' => trim(($payment->notes ?? '').' | Stripe refund failed: '.($refund->failure_reason ?? 'Unknown'), ' |'),
                ]);

                Log::info('Stripe refund status updated to failed via webhook', [
                    'payment_id' => $payment->id,
                    'stripe_refund_id' => $refundId,
                    'event_id' => $eventId,
                    'failure_reason' => $refund->failure_reason ?? null,
                ]);
            }

            if ($payment->status !== Payment::STATUS_COMPLETED) {
                $this->createRefundFailedNotification($payment, $refund->failure_reason ?? 'Unknown');
            }

            return ['action' => 'processed', 'payment_created' => false, 'payment_id' => $payment->id];
        }

        if (! $payment->stripe_refund_id || ! $payment->transaction_id) {
            $payment->update([
                'stripe_refund_id' => $payment->stripe_refund_id ?? $refundId,
                'transaction_id' => $payment->transaction_id ?? $refundId,
            ]);
        }

        return ['action' => 'processed', 'payment_created' => false, 'payment_id' => $payment->id];
    }

    private function needsRentalRefundReconciliation(string $eventType, ?string $metadataType, ?object $object): bool
    {
        if (! in_array($eventType, ['refund.created', 'refund.updated', 'refund.succeeded', 'refund.failed'], true)) {
            return false;
        }

        if ($metadataType !== 'rental_refund' || ! $object || empty($object->id)) {
            return false;
        }

        $refundId = (string) $object->id;

        return ! Payment::where('stripe_refund_id', $refundId)
            ->orWhere('transaction_id', $refundId)
            ->exists();
    }

    private function localRefundStatus(string $eventType, object $refund): string
    {
        $stripeStatus = isset($refund->status) ? (string) $refund->status : null;

        if ($eventType === 'refund.succeeded' || $stripeStatus === 'succeeded') {
            return Payment::STATUS_COMPLETED;
        }

        if ($eventType === 'refund.failed' || in_array($stripeStatus, ['failed', 'canceled'], true)) {
            return Payment::STATUS_FAILED;
        }

        return Payment::STATUS_PENDING;
    }

    private function refundableBalance(Invoice $invoice): float
    {
        $completedPayments = $invoice->payments()
            ->where('type', Payment::TYPE_PAYMENT)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        $completedRefunds = $invoice->payments()
            ->where('type', Payment::TYPE_REFUND)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        return max(0, round((float) $completedPayments - (float) $completedRefunds, 2));
    }

    private function metadataFrom(object $object): array
    {
        $metadata = $object->metadata ?? [];

        if (is_array($metadata)) {
            return $metadata;
        }

        if (is_object($metadata)) {
            return get_object_vars($metadata);
        }

        return [];
    }

    private function stringValue(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_object($value) && isset($value->id) && is_string($value->id)) {
            return $value->id;
        }

        return null;
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
     * Create refund_failed notification (idempotent)
     */
    private function createRefundFailedNotification(Payment $payment, string $failureReason): void
    {
        $invoice = $payment->invoice;
        $booking = $invoice->booking;

        // Check if notification already exists (idempotency)
        $notificationExists = \App\Models\Notification::where('user_id', $booking->user_id)
            ->where('type', 'refund_failed')
            ->whereJsonContains('data->payment_id', $payment->id)
            ->exists();

        if ($notificationExists) {
            return;
        }

        $this->notificationService->create(
            $booking->user_id,
            'refund_failed',
            __('messages.notification_refund_failed'),
            __('messages.notification_refund_failed_message', [
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
                'failure_reason' => $failureReason,
                'stripe_refund_id' => $payment->stripe_refund_id,
            ]
        );
    }

    private function structuredContext(
        ?string $eventId,
        ?int $bookingId,
        ?string $paymentIntentId,
        string $lockStatus,
        string $idempotencyStatus
    ): array {
        return [
            'event_id' => $eventId,
            'booking_id' => $bookingId,
            'payment_intent_id' => $paymentIntentId,
            'lock_status' => $lockStatus,
            'idempotency_status' => $idempotencyStatus,
        ];
    }
}

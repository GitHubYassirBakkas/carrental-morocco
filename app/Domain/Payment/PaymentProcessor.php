<?php

namespace App\Domain\Payment;

use App\Domain\Booking\BookingStateMachine;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\OutboxEvent;
use App\Models\Payment;
use App\Models\PaymentIdempotencyKey;
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

        try {
            if ($eventId) {
                $begin = $this->idempotency->begin($eventId, $eventId, $paymentIntentId);
                $idempotencyStatus = $begin['status'];

                if ($idempotencyStatus === PaymentIdempotencyService::RESULT_COMPLETED) {
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
                $invoice = Invoice::create([
                    'booking_id' => (string) $booking->id,
                    'user_id' => $booking->user_id,
                    'subtotal' => $booking->total_amount,
                    'tax_amount' => 0,
                    'total_amount' => $booking->total_amount,
                    'status' => Invoice::STATUS_PENDING,
                    'issued_at' => now(),
                ]);
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

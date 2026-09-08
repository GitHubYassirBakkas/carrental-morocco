<?php

namespace App\Jobs;

use App\Domain\Payment\PaymentProcessor;
use App\Models\Booking;
use App\Models\PaymentIdempotencyKey;
use App\Models\StripeWebhookEvent;
use App\Services\FailedWebhookEventService;
use App\Services\PaymentEventAuditService;
use App\Services\PaymentWebhookMetrics;
use App\Services\WorkerHeartbeatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessStripeWebhookEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $stripeWebhookEventId)
    {
        $this->onConnection(config('queue.webhook_connection', config('queue.default')));
        $this->onQueue(config('queue.webhook_queue', 'stripe-webhooks'));
    }

    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(PaymentProcessor $processor, PaymentEventAuditService $auditService, PaymentWebhookMetrics $metrics): void
    {
        $workerName = gethostname() . ':stripe-webhook:' . getmypid();
        $heartbeats = app(WorkerHeartbeatService::class);

        $heartbeats->beat(
            $workerName,
            config('queue.webhook_queue', 'stripe-webhooks'),
            static::class,
            $this->stripeWebhookEventId
        );

        try {
            $storedEvent = $this->claimStoredEvent();

            if (!$storedEvent) {
                return;
            }

            $event = json_decode(json_encode($this->toArraySafe($storedEvent->payload)), false, 512, JSON_THROW_ON_ERROR);
            $object = $this->value($this->value($event, 'data'), 'object');
            $eventType = (string) ($this->value($event, 'type') ?? $storedEvent->type);
            $paymentIntentId = $this->value($object, 'payment_intent') ?? $this->value($object, 'id');
            $metadata = $this->metadataFrom($object);
            $bookingId = isset($metadata['booking_id']) ? (int) $metadata['booking_id'] : null;
            $metadataType = isset($metadata['type']) ? (string) $metadata['type'] : null;
            $paymentIntentStatus = $this->value($object, 'status');
            $audit = null;

            Log::info('payment.webhook.lifecycle', [
                'state' => 'PROCESSING',
                'event_id' => $storedEvent->event_id,
                'event_type' => $eventType,
                'booking_id' => $bookingId,
                'payment_intent_id' => $paymentIntentId,
                'attempt' => $storedEvent->attempts,
            ]);

            $audit = $auditService->beginProcessing($event, $bookingId, $paymentIntentId, $storedEvent->payload);

            if (!$audit) {
                $storedEvent->forceFill([
                    'status' => StripeWebhookEvent::STATUS_PROCESSED,
                    'processed_at' => now(),
                    'failed_at' => null,
                    'error_message' => null,
                    'updated_at' => now(),
                ])->save();

                $metrics->duplicate([
                    'event_id' => $storedEvent->event_id,
                    'booking_id' => $bookingId,
                    'payment_intent_id' => $paymentIntentId,
                    'idempotency_status' => 'audit_duplicate',
                    'lock_status' => 'not_required',
                ]);

                Log::info('payment.webhook.final_decision', [
                    'event_id' => $storedEvent->event_id,
                    'event_type' => $eventType,
                    'booking_id' => $bookingId,
                    'payment_intent_id' => $paymentIntentId,
                    'decision' => 'ignored',
                    'reason' => 'duplicate_audit_event',
                    'ignore_reason' => 'duplicate_audit_event',
                ]);

                return;
            }

            $ignoreReason = $this->ignoreReason($eventType, $bookingId, $metadataType);

            if ($ignoreReason !== null) {
                $auditService->markProcessed($audit, 'ignored');

                $this->markIgnored($storedEvent, $metrics, [
                    'event_id' => $storedEvent->event_id,
                    'event_type' => $eventType,
                    'booking_id' => $bookingId,
                    'payment_intent_id' => $paymentIntentId,
                    'reason' => $ignoreReason,
                    'ignore_reason' => $ignoreReason,
                ]);

                return;
            }

            if (PaymentIdempotencyKey::where('idempotency_key', $storedEvent->event_id)
                ->where('status', PaymentIdempotencyKey::STATUS_COMPLETED)
                ->exists()) {
                $auditService->markProcessed($audit, 'duplicate');

                $storedEvent->forceFill([
                    'status' => StripeWebhookEvent::STATUS_PROCESSED,
                    'processed_at' => now(),
                    'error_message' => null,
                    'updated_at' => now(),
                ])->save();

                $metrics->duplicate([
                    'event_id' => $storedEvent->event_id,
                    'booking_id' => $bookingId,
                    'payment_intent_id' => $paymentIntentId,
                    'idempotency_status' => 'completed',
                    'lock_status' => 'not_required',
                ]);

                Log::info('payment.webhook.final_decision', [
                    'event_id' => $storedEvent->event_id,
                    'event_type' => $eventType,
                    'booking_id' => $bookingId,
                    'payment_intent_id' => $paymentIntentId,
                    'decision' => 'ignored',
                    'reason' => 'duplicate_completed_event',
                    'ignore_reason' => 'duplicate_completed_event',
                ]);

                return;
            }

            try {
                $outcome = $processor->processWebhook(
                    $event,
                    $bookingId,
                    $paymentIntentId,
                    $metadataType,
                    $metadata,
                    $paymentIntentStatus,
                    $object
                );

                if ($audit) {
                    $auditService->markProcessed($audit, $outcome ?: 'ignored');
                }

                $storedEvent->forceFill([
                    'status' => StripeWebhookEvent::STATUS_PROCESSED,
                    'processed_at' => now(),
                    'failed_at' => null,
                    'error_message' => null,
                    'updated_at' => now(),
                ])->save();

                $metrics->processed([
                    'event_id' => $storedEvent->event_id,
                    'booking_id' => $bookingId,
                    'payment_intent_id' => $paymentIntentId,
                ]);

                Log::info('payment.webhook.lifecycle', [
                    'state' => 'SUCCESS',
                    'event_id' => $storedEvent->event_id,
                    'event_type' => $eventType,
                    'booking_id' => $bookingId,
                    'payment_intent_id' => $paymentIntentId,
                    'outcome' => $outcome ?: 'ignored',
                ]);

                Log::info('payment.webhook.final_decision', [
                    'event_id' => $storedEvent->event_id,
                    'event_type' => $eventType,
                    'booking_id' => $bookingId,
                    'payment_intent_id' => $paymentIntentId,
                    'decision' => $outcome ?: 'ignored',
                    'reason' => null,
                ]);
            } catch (Throwable $e) {
                if ($audit) {
                    $auditService->markFailed($audit, $e);
                }

                $storedEvent->forceFill([
                    'status' => StripeWebhookEvent::STATUS_FAILED,
                    'failed_at' => now(),
                    'error_message' => substr($e->getMessage(), 0, 65000),
                    'updated_at' => now(),
                ])->save();

                $metrics->retried([
                    'event_id' => $storedEvent->event_id,
                    'booking_id' => $bookingId,
                    'payment_intent_id' => $paymentIntentId,
                    'attempt' => $storedEvent->attempts,
                ]);

                Log::warning('payment.webhook.lifecycle', [
                    'state' => 'FAILED',
                    'event_id' => $storedEvent->event_id,
                    'event_type' => $eventType,
                    'booking_id' => $bookingId,
                    'payment_intent_id' => $paymentIntentId,
                    'attempt' => $storedEvent->attempts,
                    'decision' => 'failed',
                    'reason' => $e->getMessage(),
                    'message' => $e->getMessage(),
                ]);

                if (config('queue.webhook_connection', config('queue.default')) === 'sync') {
                    app(FailedWebhookEventService::class)->record(
                        $event,
                        $bookingId,
                        $paymentIntentId,
                        $storedEvent->payload,
                        $e
                    );

                    $metrics->failed([
                        'event_id' => $storedEvent->event_id,
                        'booking_id' => $bookingId,
                        'payment_intent_id' => $paymentIntentId,
                        'attempts' => $storedEvent->attempts,
                    ]);

                    return;
                }

                throw $e;
            }
        } finally {
            $heartbeats->release($workerName, [
                'stripe_webhook_event_id' => $this->stripeWebhookEventId,
            ]);
        }
    }

    private function claimStoredEvent(): ?StripeWebhookEvent
    {
        return DB::transaction(function (): ?StripeWebhookEvent {
            $storedEvent = StripeWebhookEvent::whereKey($this->stripeWebhookEventId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($storedEvent->status === StripeWebhookEvent::STATUS_PROCESSED) {
                Log::info('payment.webhook.lifecycle', [
                    'state' => 'SUCCESS',
                    'event_id' => $storedEvent->event_id,
                    'status' => 'already_processed',
                ]);

                return null;
            }

            $processingIsFresh = $storedEvent->updated_at
                && $storedEvent->updated_at->greaterThan(now()->subSeconds($this->timeout));

            if ($storedEvent->status === StripeWebhookEvent::STATUS_PROCESSING && $processingIsFresh) {
                Log::info('payment.webhook.lifecycle', [
                    'state' => 'SKIPPED',
                    'event_id' => $storedEvent->event_id,
                    'status' => 'already_processing',
                ]);

                return null;
            }

            $storedEvent->forceFill([
                'status' => StripeWebhookEvent::STATUS_PROCESSING,
                'attempts' => $storedEvent->attempts + 1,
                'updated_at' => now(),
            ])->save();

            return $storedEvent->refresh();
        });
    }

    public function failed(Throwable $exception): void
    {
        $storedEvent = StripeWebhookEvent::find($this->stripeWebhookEventId);

        if (!$storedEvent) {
            return;
        }

        if ($storedEvent->status !== StripeWebhookEvent::STATUS_PROCESSED) {
            $storedEvent->forceFill([
                'status' => StripeWebhookEvent::STATUS_FAILED,
                'failed_at' => now(),
                'error_message' => substr($exception->getMessage(), 0, 65000),
                'updated_at' => now(),
            ])->save();
        }

        $event = json_decode(json_encode($this->toArraySafe($storedEvent->payload)), false);
        $object = $this->value($this->value($event, 'data'), 'object');
        $metadata = $this->metadataFrom($object);
        $bookingId = isset($metadata['booking_id']) ? (int) $metadata['booking_id'] : null;
        $paymentIntentId = $this->value($object, 'payment_intent') ?? $this->value($object, 'id');

        app(FailedWebhookEventService::class)->record(
            $event,
            $bookingId,
            $paymentIntentId,
            $storedEvent->payload,
            $exception
        );

        app(PaymentWebhookMetrics::class)->failed([
            'event_id' => $storedEvent->event_id,
            'booking_id' => $bookingId,
            'payment_intent_id' => $paymentIntentId,
            'attempts' => $storedEvent->attempts,
        ]);
    }

    private function metadataFrom(mixed $object): array
    {
        return $this->toArraySafe($this->value($object, 'metadata'));
    }

    private function toArraySafe(mixed $value): array
    {
        $normalized = $this->normalizeValue($value);

        return is_array($normalized) ? $normalized : [];
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizeValue($item), $value);
        }

        if (is_object($value)) {
            if (method_exists($value, 'toArray')) {
                return $this->normalizeValue($value->toArray());
            }

            if ($value instanceof \JsonSerializable) {
                return $this->normalizeValue($value->jsonSerialize());
            }

            if ($value instanceof \Traversable) {
                return $this->normalizeValue(iterator_to_array($value));
            }

            return $this->normalizeValue(get_object_vars($value));
        }

        return $value;
    }

    private function value(mixed $source, string $key, mixed $default = null): mixed
    {
        if (is_array($source)) {
            return $source[$key] ?? $default;
        }

        if (is_object($source) && isset($source->{$key})) {
            return $source->{$key};
        }

        return $default;
    }

    private function ignoreReason(string $eventType, ?int $bookingId, ?string $metadataType): ?string
    {
        if (str_starts_with($eventType, 'charge.')) {
            return 'charge_event_not_business_source';
        }

        if ($eventType === 'payment_intent.created') {
            return 'payment_intent_created_not_mutating';
        }

        $supported = [
            'payment_intent.amount_capturable_updated',
            'payment_intent.canceled',
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
        ];

        if (!in_array($eventType, $supported, true)) {
            return 'unsupported_stripe_event';
        }

        if (!$bookingId) {
            return 'missing_booking_id';
        }

        if (!Booking::whereKey($bookingId)->exists()) {
            return 'booking_not_found';
        }

        if ($eventType === 'payment_intent.succeeded' && !in_array($metadataType, ['rental', 'security_deposit'], true)) {
            return 'non_business_payment_intent';
        }

        return null;
    }

    private function markIgnored(StripeWebhookEvent $storedEvent, PaymentWebhookMetrics $metrics, array $context): void
    {
        $storedEvent->forceFill([
            'status' => StripeWebhookEvent::STATUS_PROCESSED,
            'processed_at' => now(),
            'failed_at' => null,
            'error_message' => null,
            'updated_at' => now(),
        ])->save();

        $metrics->processed($context + ['decision' => 'ignored']);

        Log::warning('payment.webhook.final_decision', $context + [
            'decision' => 'ignored',
        ]);
    }
}

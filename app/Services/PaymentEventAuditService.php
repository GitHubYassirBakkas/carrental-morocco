<?php

namespace App\Services;

use App\Models\PaymentEventAudit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentEventAuditService
{
    public function beginProcessing(
        object $event,
        ?int $bookingId,
        ?string $paymentIntentId,
        array $payload
    ): ?PaymentEventAudit {
        $eventId = $event->id ?? null;
        $eventType = $event->type ?? 'unknown';

        if (!$eventId) {
            Log::warning('Stripe webhook audit skipped: missing event id.', [
                'event_type' => $eventType,
                'booking_id' => $bookingId,
                'payment_intent_id' => $paymentIntentId,
            ]);

            return null;
        }

        try {
            $audit = PaymentEventAudit::create([
                'booking_id' => $bookingId,
                'event_id' => $eventId,
                'event_type' => $eventType,
                'payment_intent_id' => $paymentIntentId,
                'payload' => $payload,
            ]);

            Log::info('Stripe webhook audit record created.', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'booking_id' => $bookingId,
                'payment_intent_id' => $paymentIntentId,
            ]);

            return $audit;
        } catch (QueryException $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                $existingAudit = PaymentEventAudit::where('event_id', $eventId)->first();

                if ($existingAudit && $existingAudit->outcome === 'failed') {
                    Log::info('Stripe webhook audit event retry resumed after previous failure.', [
                        'event_id' => $eventId,
                        'event_type' => $eventType,
                        'booking_id' => $bookingId,
                        'payment_intent_id' => $paymentIntentId,
                    ]);

                    return $existingAudit;
                }

                Log::info('Stripe webhook ignored as duplicate audit event.', [
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                    'booking_id' => $bookingId,
                    'payment_intent_id' => $paymentIntentId,
                ]);

                return null;
            }

            throw $e;
        }
    }

    public function markProcessed(PaymentEventAudit $audit, string $outcome = 'processed'): void
    {
        $audit->forceFill([
            'processed_at' => now(),
            'outcome' => $outcome,
            'error_message' => null,
        ])->save();
    }

    public function markFailed(PaymentEventAudit $audit, Throwable|string $error): void
    {
        $message = $error instanceof Throwable ? $error->getMessage() : $error;

        $audit->forceFill([
            'processed_at' => now(),
            'outcome' => 'failed',
            'error_message' => substr($message, 0, 65000),
        ])->save();
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = (string) ($e->errorInfo[1] ?? '');
        $message = $e->getMessage();

        return $sqlState === '23000'
            || $driverCode === '1062'
            || str_contains($message, 'UNIQUE constraint failed')
            || str_contains($message, 'Duplicate entry');
    }
}

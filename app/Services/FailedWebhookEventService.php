<?php

namespace App\Services;

use App\Models\FailedWebhookEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

class FailedWebhookEventService
{
    public function record(
        ?object $event,
        ?int $bookingId,
        ?string $paymentIntentId,
        array $payload,
        Throwable $error
    ): void {
        try {
            FailedWebhookEvent::create([
                'event_id' => $event->id ?? null,
                'event_type' => $event->type ?? null,
                'booking_id' => $bookingId,
                'payment_intent_id' => $paymentIntentId,
                'payload' => $payload,
                'error_message' => substr($error->getMessage(), 0, 65000),
                'stack_trace' => substr($error->getTraceAsString(), 0, 65000),
                'failed_at' => now(),
            ]);
        } catch (Throwable $deadLetterError) {
            Log::error('Stripe webhook dead-letter persistence failed.', [
                'event_id' => $event->id ?? null,
                'booking_id' => $bookingId,
                'payment_intent_id' => $paymentIntentId,
                'message' => $deadLetterError->getMessage(),
            ]);
        }
    }
}

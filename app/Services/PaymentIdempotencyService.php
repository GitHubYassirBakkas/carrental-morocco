<?php

namespace App\Services;

use App\Models\PaymentIdempotencyKey;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class PaymentIdempotencyService
{
    public const RESULT_STARTED = 'started';

    public const RESULT_COMPLETED = PaymentIdempotencyKey::STATUS_COMPLETED;

    public const RESULT_RETRY_STARTED = 'retry_started';

    public const RESULT_PROCESSING_TIMEOUT = 'processing_timeout';

    public function begin(
        string $idempotencyKey,
        ?string $eventId,
        ?string $paymentIntentId,
        int $waitMilliseconds = 3000
    ): array {
        $deadline = microtime(true) + ($waitMilliseconds / 1000);

        do {
            try {
                $record = PaymentIdempotencyKey::create([
                    'idempotency_key' => $idempotencyKey,
                    'event_id' => $eventId,
                    'payment_intent_id' => $paymentIntentId,
                    'status' => PaymentIdempotencyKey::STATUS_PROCESSING,
                ]);

                return ['status' => self::RESULT_STARTED, 'record' => $record];
            } catch (QueryException $e) {
                if (! $this->isUniqueConstraintViolation($e)) {
                    throw $e;
                }
            }

            $record = PaymentIdempotencyKey::where('idempotency_key', $idempotencyKey)->first();

            if (! $record) {
                usleep(50_000);

                continue;
            }

            if ($record->status === PaymentIdempotencyKey::STATUS_COMPLETED) {
                return ['status' => self::RESULT_COMPLETED, 'record' => $record];
            }

            if ($record->status === PaymentIdempotencyKey::STATUS_FAILED) {
                $updated = PaymentIdempotencyKey::whereKey($record->id)
                    ->where('status', PaymentIdempotencyKey::STATUS_FAILED)
                    ->update([
                        'status' => PaymentIdempotencyKey::STATUS_PROCESSING,
                        'event_id' => $eventId,
                        'payment_intent_id' => $paymentIntentId,
                        'updated_at' => now(),
                    ]);

                if ($updated === 1) {
                    $record = PaymentIdempotencyKey::findOrFail($record->id);

                    return ['status' => self::RESULT_RETRY_STARTED, 'record' => $record];
                }
            }

            usleep(50_000);
        } while (microtime(true) < $deadline);

        Log::warning('Stripe webhook idempotency key still processing after timeout.', [
            'idempotency_key' => $idempotencyKey,
            'event_id' => $eventId,
            'payment_intent_id' => $paymentIntentId,
        ]);

        return ['status' => self::RESULT_PROCESSING_TIMEOUT, 'record' => $record ?? null];
    }

    public function markCompleted(PaymentIdempotencyKey $record): void
    {
        $record->forceFill([
            'status' => PaymentIdempotencyKey::STATUS_COMPLETED,
            'updated_at' => now(),
        ])->save();
    }

    public function markFailed(PaymentIdempotencyKey $record): void
    {
        $record->forceFill([
            'status' => PaymentIdempotencyKey::STATUS_FAILED,
            'updated_at' => now(),
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

<?php

namespace App\Services;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentWebhookLockService
{
    private string $lastStatus = 'not_attempted';

    public function __construct(private readonly DistributedLockManager $distributedLocks) {}

    public function lastStatus(): string
    {
        return $this->lastStatus;
    }

    public function acquire(int $bookingId, int $waitMilliseconds = 5000, int $ttlSeconds = 30, ?string $ownerToken = null): ?string
    {
        $deadline = microtime(true) + ($waitMilliseconds / 1000);
        $ownerToken ??= (string) Str::uuid();

        do {
            if ($this->attemptAcquire($bookingId, $ownerToken, $ttlSeconds)) {
                Log::info('Stripe webhook booking lock acquired.', [
                    'booking_id' => $bookingId,
                ]);

                return $ownerToken;
            }

            usleep(50_000);
        } while (microtime(true) < $deadline);

        Log::warning('Stripe webhook booking lock not acquired before timeout.', [
            'booking_id' => $bookingId,
        ]);

        return null;
    }

    public function withBookingLock(int $bookingId, Closure $callback, int $waitMilliseconds = 5000, int $ttlSeconds = 30): mixed
    {
        $this->lastStatus = 'not_attempted';
        $this->clearExpiredDatabaseLock($bookingId);

        $distributedResult = $this->withDistributedBookingLock($bookingId, $callback, $waitMilliseconds, $ttlSeconds);

        if ($distributedResult['status'] === 'handled') {
            return $distributedResult['result'];
        }

        if ($distributedResult['status'] === 'timeout') {
            $this->lastStatus = 'cache_timeout';

            return null;
        }

        $ownerToken = $this->acquire($bookingId, $waitMilliseconds, $ttlSeconds);

        if (! $ownerToken) {
            $this->lastStatus = 'database_timeout';

            return null;
        }

        $this->lastStatus = 'database_acquired';

        try {
            return DB::transaction(function () use ($bookingId, $callback, $ownerToken, $ttlSeconds) {
                $lock = DB::table('payment_webhook_locks')
                    ->where('booking_id', $bookingId)
                    ->lockForUpdate()
                    ->first();

                if (! $lock || $lock->owner_token !== $ownerToken) {
                    throw new RuntimeException('Webhook booking lock ownership was lost.');
                }

                if ($lock->expires_at && Carbon::parse($lock->expires_at)->lte(now())) {
                    throw new RuntimeException('Webhook booking lock expired before processing.');
                }

                DB::table('payment_webhook_locks')
                    ->where('booking_id', $bookingId)
                    ->where('owner_token', $ownerToken)
                    ->update([
                        'locked_at' => now(),
                        'expires_at' => now()->addSeconds($ttlSeconds),
                    ]);

                return $callback();
            }, 3);
        } finally {
            $this->release($bookingId, $ownerToken);
        }
    }

    public function release(int $bookingId, ?string $ownerToken = null): void
    {
        $query = DB::table('payment_webhook_locks')->where('booking_id', $bookingId);

        if ($ownerToken) {
            $query->where('owner_token', $ownerToken);
        }

        $query->delete();

        Log::info('Stripe webhook booking lock released.', [
            'booking_id' => $bookingId,
        ]);
    }

    private function attemptAcquire(int $bookingId, string $ownerToken, int $ttlSeconds): bool
    {
        return DB::transaction(function () use ($bookingId, $ownerToken, $ttlSeconds) {
            try {
                DB::table('payment_webhook_locks')->insert([
                    'booking_id' => $bookingId,
                    'locked_at' => now(),
                    'expires_at' => now()->addSeconds($ttlSeconds),
                    'owner_token' => $ownerToken,
                ]);

                return true;
            } catch (QueryException $e) {
                if (! $this->isUniqueConstraintViolation($e)) {
                    throw $e;
                }
            }

            $lock = DB::table('payment_webhook_locks')
                ->where('booking_id', $bookingId)
                ->lockForUpdate()
                ->first();

            if (! $lock) {
                return false;
            }

            if ($lock->expires_at && Carbon::parse($lock->expires_at)->gt(now())) {
                return false;
            }

            DB::table('payment_webhook_locks')
                ->where('booking_id', $bookingId)
                ->update([
                    'locked_at' => now(),
                    'expires_at' => now()->addSeconds($ttlSeconds),
                    'owner_token' => $ownerToken,
                ]);

            return true;
        }, 3);
    }

    private function withDistributedBookingLock(int $bookingId, Closure $callback, int $waitMilliseconds, int $ttlSeconds): array
    {
        $result = $this->distributedLocks->withLock($this->cacheLockName($bookingId), function () use ($bookingId, $callback) {
            Log::info('Stripe webhook distributed booking lock acquired.', [
                'booking_id' => $bookingId,
                'lock_status' => 'distributed_acquired',
            ]);

            return $callback();
        }, $waitMilliseconds, $ttlSeconds);

        $this->lastStatus = $this->distributedLocks->lastStatus();

        return $result === null
            ? ['status' => 'timeout', 'result' => null]
            : ['status' => 'handled', 'result' => $result];
    }

    private function cacheLockName(int $bookingId): string
    {
        return 'stripe-webhook:booking:'.$bookingId;
    }

    private function clearExpiredDatabaseLock(int $bookingId): void
    {
        DB::table('payment_webhook_locks')
            ->where('booking_id', $bookingId)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();
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

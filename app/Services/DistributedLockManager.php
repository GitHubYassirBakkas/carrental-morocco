<?php

namespace App\Services;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class DistributedLockManager
{
    private string $lastStatus = 'not_attempted';

    public function lastStatus(): string
    {
        return $this->lastStatus;
    }

    public function withLock(string $name, Closure $callback, int $waitMilliseconds = 5000, int $ttlSeconds = 30): mixed
    {
        $this->lastStatus = 'not_attempted';

        try {
            $store = $this->preferredCacheStore();
            $lock = $store->lock($name, $ttlSeconds);

            return $lock->block(max(1, (int) ceil($waitMilliseconds / 1000)), function () use ($callback) {
                $this->lastStatus = 'redis_or_cache_acquired';

                return $callback();
            });
        } catch (LockTimeoutException $e) {
            $this->lastStatus = 'distributed_timeout';

            return null;
        } catch (Throwable $e) {
            Log::warning('Distributed cache lock unavailable; falling back to database lock.', [
                'lock_name' => $name,
                'message' => $e->getMessage(),
            ]);

            return $this->withDatabaseLock($name, $callback, $waitMilliseconds, $ttlSeconds);
        }
    }

    private function preferredCacheStore(): mixed
    {
        if (config('cache.stores.redis')) {
            return Cache::store(config('cache.default') === 'redis' ? null : config('cache.default'));
        }

        return Cache::store();
    }

    private function withDatabaseLock(string $name, Closure $callback, int $waitMilliseconds, int $ttlSeconds): mixed
    {
        $bookingId = (int) abs(crc32($name));
        $ownerToken = (string) Str::uuid();
        $deadline = microtime(true) + ($waitMilliseconds / 1000);

        do {
            $acquired = DB::transaction(function () use ($bookingId, $ownerToken, $ttlSeconds) {
                $lock = DB::table('payment_webhook_locks')->where('booking_id', $bookingId)->lockForUpdate()->first();

                if ($lock && $lock->expires_at && now()->lt($lock->expires_at)) {
                    return false;
                }

                DB::table('payment_webhook_locks')->updateOrInsert(
                    ['booking_id' => $bookingId],
                    [
                        'locked_at' => now(),
                        'expires_at' => now()->addSeconds($ttlSeconds),
                        'owner_token' => $ownerToken,
                    ]
                );

                return true;
            }, 3);

            if ($acquired) {
                $this->lastStatus = 'database_acquired';

                try {
                    return $callback();
                } finally {
                    DB::table('payment_webhook_locks')
                        ->where('booking_id', $bookingId)
                        ->where('owner_token', $ownerToken)
                        ->delete();
                }
            }

            usleep(50_000);
        } while (microtime(true) < $deadline);

        $this->lastStatus = 'database_timeout';

        return null;
    }
}

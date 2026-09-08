<?php

namespace App\Services;

use App\Models\GlobalIdempotencyRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GlobalIdempotencyService
{
    public function begin(string $scope, string $key, array $context = [], int $ttlSeconds = 86400): array
    {
        $ownerToken = (string) Str::uuid();
        $cacheKey = $this->cacheKey($scope, $key);
        $cacheAcquired = Cache::add($cacheKey, $ownerToken, $ttlSeconds);

        try {
            $record = GlobalIdempotencyRecord::create([
                'scope' => $scope,
                'idempotency_key' => $key,
                'status' => GlobalIdempotencyRecord::STATUS_PROCESSING,
                'owner_token' => $ownerToken,
                'expires_at' => now()->addSeconds($ttlSeconds),
                'context' => $context,
            ]);

            return ['status' => 'started', 'record' => $record, 'owner_token' => $ownerToken, 'cache_acquired' => $cacheAcquired];
        } catch (QueryException $e) {
            $record = GlobalIdempotencyRecord::where('scope', $scope)->where('idempotency_key', $key)->first();

            if (! $record) {
                throw $e;
            }

            if ($record->status === GlobalIdempotencyRecord::STATUS_COMPLETED) {
                return ['status' => 'completed', 'record' => $record, 'owner_token' => null, 'cache_acquired' => false];
            }

            if ($record->expires_at && $record->expires_at->lt(now())) {
                $updated = GlobalIdempotencyRecord::whereKey($record->id)
                    ->where('status', '!=', GlobalIdempotencyRecord::STATUS_COMPLETED)
                    ->update([
                        'status' => GlobalIdempotencyRecord::STATUS_PROCESSING,
                        'owner_token' => $ownerToken,
                        'expires_at' => now()->addSeconds($ttlSeconds),
                        'context' => $context,
                        'updated_at' => now(),
                    ]);

                if ($updated === 1) {
                    return ['status' => 'recovered_expired', 'record' => $record->fresh(), 'owner_token' => $ownerToken, 'cache_acquired' => $cacheAcquired];
                }
            }

            return ['status' => 'processing', 'record' => $record, 'owner_token' => null, 'cache_acquired' => false];
        }
    }

    public function complete(GlobalIdempotencyRecord $record): void
    {
        $record->forceFill([
            'status' => GlobalIdempotencyRecord::STATUS_COMPLETED,
            'owner_token' => null,
            'updated_at' => now(),
        ])->save();
    }

    public function fail(GlobalIdempotencyRecord $record): void
    {
        $record->forceFill([
            'status' => GlobalIdempotencyRecord::STATUS_FAILED,
            'owner_token' => null,
            'updated_at' => now(),
        ])->save();
    }

    private function cacheKey(string $scope, string $key): string
    {
        return 'global-idempotency:'.$scope.':'.$key;
    }
}

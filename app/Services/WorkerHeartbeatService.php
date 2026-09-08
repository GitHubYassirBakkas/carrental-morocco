<?php

namespace App\Services;

use App\Models\EventStreamCursor;
use App\Models\WorkerHeartbeat;

class WorkerHeartbeatService
{
    public function beat(string $workerName, ?string $queue = null, ?string $job = null, ?int $eventId = null, array $context = []): void
    {
        WorkerHeartbeat::updateOrCreate(
            ['worker_name' => $workerName],
            [
                'queue' => $queue,
                'current_job' => $job,
                'current_event_id' => $eventId,
                'heartbeat_at' => now(),
                'context' => $context,
            ]
        );
    }

    public function release(string $workerName, array $context = []): void
    {
        WorkerHeartbeat::updateOrCreate(
            ['worker_name' => $workerName],
            [
                'queue' => $context['queue'] ?? null,
                'current_job' => null,
                'current_event_id' => null,
                'heartbeat_at' => now(),
                'context' => $context + [
                    'released_at' => now()->toISOString(),
                ],
            ]
        );
    }

    public function advanceCursor(string $workerName, int $eventStreamId): void
    {
        EventStreamCursor::updateOrCreate(
            ['worker_name' => $workerName],
            [
                'last_event_stream_id' => $eventStreamId,
                'last_seen_at' => now(),
            ]
        );
    }

    public function staleWorkers(int $olderThanSeconds = 300)
    {
        return WorkerHeartbeat::where('heartbeat_at', '<', now()->subSeconds($olderThanSeconds))->get();
    }
}

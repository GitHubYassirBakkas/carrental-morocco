<?php

namespace App\Jobs;

use App\Models\StripeWebhookEvent;
use App\Services\WorkerHeartbeatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecoverStuckWebhookJobs implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $staleAfterSeconds = 600)
    {
        $this->onConnection(config('queue.webhook_connection', config('queue.default')));
        $this->onQueue(config('queue.webhook_queue', 'stripe-webhooks'));
    }

    public function handle(WorkerHeartbeatService $heartbeats): void
    {
        $staleCutoff = now()->subSeconds($this->staleAfterSeconds);

        $events = StripeWebhookEvent::where('status', StripeWebhookEvent::STATUS_PROCESSING)
            ->where('updated_at', '<', $staleCutoff)
            ->get();

        foreach ($events as $event) {
            if ($event->attempts >= 5) {
                $event->forceFill([
                    'status' => StripeWebhookEvent::STATUS_FAILED,
                    'failed_at' => now(),
                    'error_message' => 'Poison webhook event: max attempts exceeded during stuck-job recovery.',
                    'updated_at' => now(),
                ])->save();

                continue;
            }

            $event->forceFill([
                'status' => StripeWebhookEvent::STATUS_PENDING,
                'error_message' => 'Recovered from stale processing state for retry.',
                'updated_at' => now(),
            ])->save();

            ProcessStripeWebhookEventJob::dispatch($event->id);
        }

        foreach ($heartbeats->staleWorkers($this->staleAfterSeconds) as $worker) {
            Log::warning('worker.heartbeat.stale', [
                'worker_name' => $worker->worker_name,
                'queue' => $worker->queue,
                'current_job' => $worker->current_job,
                'heartbeat_at' => $worker->heartbeat_at?->toISOString(),
            ]);

            $worker->forceFill([
                'current_job' => null,
                'current_event_id' => null,
                'heartbeat_at' => now(),
                'context' => array_merge($worker->context ?? [], [
                    'stale_acknowledged_at' => now()->toISOString(),
                    'previous_job' => $worker->current_job,
                ]),
            ])->save();
        }
    }
}

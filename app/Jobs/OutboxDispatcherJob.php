<?php

namespace App\Jobs;

use App\Domain\Events\EventBus;
use App\Models\EventStream;
use App\Models\OutboxEvent;
use App\Services\FailedWebhookEventService;
use App\Services\WorkerHeartbeatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class OutboxDispatcherJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $timeout = 60;

    public function __construct(public readonly ?int $outboxEventId = null)
    {
        $this->onConnection(config('queue.webhook_connection', config('queue.default')));
        $this->onQueue(config('queue.webhook_queue', 'stripe-webhooks'));
    }

    public function backoff(): array
    {
        return [5, 30, 120, 300, 600];
    }

    public function handle(
        EventBus $eventBus,
        ?WorkerHeartbeatService $heartbeats = null,
        ?FailedWebhookEventService $failedWebhookEvents = null
    ): void {
        $workerName = gethostname().':outbox:'.getmypid();
        $heartbeats ??= app(WorkerHeartbeatService::class);
        $failedWebhookEvents ??= app(FailedWebhookEventService::class);

        $heartbeats->beat($workerName, config('queue.webhook_queue', 'stripe-webhooks'), static::class);

        $events = $this->outboxEventId
            ? OutboxEvent::whereKey($this->outboxEventId)->where('dispatched', false)->get()
            : OutboxEvent::where('dispatched', false)->orderBy('id')->limit(50)->get();

        foreach ($events as $event) {
            DB::transaction(function () use ($event, $eventBus, $heartbeats, $failedWebhookEvents) {
                $locked = OutboxEvent::whereKey($event->id)->lockForUpdate()->first();

                if (! $locked || $locked->dispatched) {
                    return;
                }

                if ($locked->attempts >= $this->tries) {
                    $locked->forceFill([
                        'error_message' => 'Poison outbox message: max dispatch attempts exceeded.',
                    ])->save();

                    $failedWebhookEvents->record(
                        null,
                        isset($locked->payload['booking_id']) ? (int) $locked->payload['booking_id'] : null,
                        $locked->payload['payment_intent_id'] ?? null,
                        $locked->payload ?? [],
                        new \RuntimeException('Poison outbox message: max dispatch attempts exceeded.')
                    );

                    return;
                }

                $locked->forceFill([
                    'attempts' => $locked->attempts + 1,
                    'error_message' => null,
                ])->save();

                try {
                    $eventBus->dispatchOutbox($locked);

                    $locked->forceFill([
                        'dispatched' => true,
                        'dispatched_at' => now(),
                        'error_message' => null,
                    ])->save();

                    Log::info('outbox.event.dispatched', [
                        'outbox_event_id' => $locked->id,
                        'event_type' => $locked->event_type,
                        'trace_id' => $locked->trace_id,
                        'source_event_id' => $locked->source_event_id,
                    ]);

                    $heartbeats->advanceCursor(
                        gethostname().':outbox:'.getmypid(),
                        (int) EventStream::max('id')
                    );
                } catch (Throwable $e) {
                    $locked->forceFill([
                        'error_message' => substr($e->getMessage(), 0, 65000),
                    ])->save();

                    Log::warning('outbox.event.dispatch_failed', [
                        'outbox_event_id' => $locked->id,
                        'event_type' => $locked->event_type,
                        'trace_id' => $locked->trace_id,
                        'source_event_id' => $locked->source_event_id,
                        'message' => $e->getMessage(),
                    ]);

                    throw $e;
                }
            }, 3);
        }
    }
}

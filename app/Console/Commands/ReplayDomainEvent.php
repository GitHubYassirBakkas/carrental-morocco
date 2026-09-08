<?php

namespace App\Console\Commands;

use App\Domain\Events\EventBus;
use App\Models\EventReplayLog;
use App\Models\OutboxEvent;
use Illuminate\Console\Command;
use Throwable;

class ReplayDomainEvent extends Command
{
    protected $signature = 'events:replay {id : Outbox event id to replay}';

    protected $description = 'Replay a stored outbox domain event through the internal event bus.';

    public function handle(EventBus $eventBus): int
    {
        $outboxEvent = OutboxEvent::findOrFail((int) $this->argument('id'));
        $log = EventReplayLog::create([
            'event_source' => 'outbox_events',
            'event_id' => $outboxEvent->id,
            'trace_id' => $outboxEvent->trace_id,
            'status' => 'started',
        ]);

        try {
            $eventBus->dispatchOutbox($outboxEvent);

            $log->forceFill([
                'status' => 'completed',
                'error_message' => null,
            ])->save();

            $this->info('Event replay completed.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $log->forceFill([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 65000),
            ])->save();

            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}

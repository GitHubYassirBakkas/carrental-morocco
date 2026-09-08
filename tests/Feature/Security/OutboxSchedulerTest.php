<?php

use App\Domain\Events\EventBus;
use App\Jobs\OutboxDispatcherJob;
use App\Jobs\RecoverStuckWebhookJobs;
use App\Models\OutboxEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

test('authoritative outbox dispatcher job exists and is queue backed', function () {
    expect(class_exists(OutboxDispatcherJob::class))->toBeTrue()
        ->and(is_subclass_of(OutboxDispatcherJob::class, ShouldQueue::class))->toBeTrue();
});

test('scheduler registers outbox dispatcher every minute and preserves existing tasks', function () {
    $exitCode = Artisan::call('schedule:list');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain(OutboxDispatcherJob::class)
        ->and($output)->toContain('bookings:cancel-overdue')
        ->and($output)->toContain(RecoverStuckWebhookJobs::class);

    expect((bool) preg_match('/\*\s+\*\s+\*\s+\*\s+\*\s+App\\\\Jobs\\\\OutboxDispatcherJob/', $output))->toBeTrue();
});

test('outbox scheduler registration uses overlap protection without one server locking', function () {
    $source = file_get_contents(base_path('bootstrap/app.php'));

    $outboxSchedulePattern = '->job\(new OutboxDispatcherJob(?:\(\))?\)->everyMinute\(\)->withoutOverlapping\(5\)';

    expect((bool) preg_match('/'.$outboxSchedulePattern.'/', $source))->toBeTrue()
        ->and((bool) preg_match('/'.$outboxSchedulePattern.'->onOneServer\(\)/', $source))->toBeFalse();
});

test('inspecting the scheduler does not dispatch duplicate outbox work', function () {
    Bus::fake([OutboxDispatcherJob::class]);

    Artisan::call('schedule:list');

    Bus::assertNotDispatched(OutboxDispatcherJob::class);
});

test('outbox dispatcher retry idempotency still skips already dispatched rows', function () {
    $outboxEvent = OutboxEvent::create([
        'event_type' => 'payment.succeeded',
        'trace_id' => (string) Str::uuid(),
        'source_event_id' => 'evt_outbox_scheduler_already_dispatched',
        'payload' => [
            'event_id' => 'evt_outbox_scheduler_already_dispatched',
            'payment_intent_id' => 'pi_outbox_scheduler_already_dispatched',
        ],
        'dispatched' => true,
    ]);

    $eventBus = Mockery::mock(EventBus::class);
    $eventBus->shouldNotReceive('dispatchOutbox');

    app(OutboxDispatcherJob::class, ['outboxEventId' => $outboxEvent->id])->handle($eventBus);

    expect($outboxEvent->refresh()->attempts)->toBe(0)
        ->and($outboxEvent->dispatched)->toBeTrue();
});

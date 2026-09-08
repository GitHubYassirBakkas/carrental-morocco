<?php

use App\Domain\Booking\BookingStateMachine;
use App\Domain\Events\EventBus;
use App\Domain\Events\PaymentFailedEvent;
use App\Jobs\OutboxDispatcherJob;
use App\Jobs\ProcessStripeWebhookEventJob;
use App\Jobs\RecoverStuckWebhookJobs;
use App\Models\Booking;
use App\Models\BookingSaga;
use App\Models\BookingStateTransition;
use App\Models\Car;
use App\Models\EventReplayLog;
use App\Models\EventStream;
use App\Models\EventTimeline;
use App\Models\FailedWebhookEvent;
use App\Models\GlobalIdempotencyRecord;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\OutboxEvent;
use App\Models\Payment;
use App\Models\PaymentEventAudit;
use App\Models\PaymentIdempotencyKey;
use App\Models\StripeWebhookEvent;
use App\Models\User;
use App\Services\GlobalIdempotencyService;
use App\Services\PaymentWebhookLockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

test('rental payment intent succeeded pays invoice confirms booking and is idempotent', function () {
    [$booking, $invoice] = auditBookingWithInvoice();

    $payload = auditPaymentIntentPayload('payment_intent.succeeded', 'pi_audit_rental', $booking, [
        'status' => 'succeeded',
        'amount_received' => 130000,
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'invoice_id' => (string) $invoice->id,
            'type' => 'rental',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();
    postStripeWebhook($this, $payload)->assertOk();

    expect($booking->refresh()->status)->toBe('confirmed')
        ->and($booking->advance_payment_status)->toBe('paid')
        ->and($invoice->refresh()->status)->toBe('paid')
        ->and(Payment::where('transaction_id', 'pi_audit_rental')->count())->toBe(1)
        ->and((float) Payment::where('transaction_id', 'pi_audit_rental')->first()->amount)->toBe(1300.0)
        ->and(PaymentEventAudit::where('event_id', $payload['id'])->first()->outcome)->toBe('processed');
});

test('duplicate webhook same event id is replay protected before mutation', function () {
    [$booking, $invoice] = auditBookingWithInvoice();

    $payload = auditPaymentIntentPayload('payment_intent.succeeded', 'pi_duplicate_same_event', $booking, [
        'status' => 'succeeded',
        'amount_received' => 130000,
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'invoice_id' => (string) $invoice->id,
            'type' => 'rental',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();
    postStripeWebhook($this, $payload)->assertOk();

    expect(PaymentEventAudit::where('event_id', $payload['id'])->count())->toBe(1)
        ->and(Payment::where('transaction_id', 'pi_duplicate_same_event')->count())->toBe(1);
});

test('successful webhook records a completed global idempotency key', function () {
    [$booking, $invoice] = auditBookingWithInvoice();

    $payload = auditPaymentIntentPayload('payment_intent.succeeded', 'pi_global_idempotency', $booking, [
        'id' => 'evt_global_idempotency',
        'status' => 'succeeded',
        'amount_received' => 130000,
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'invoice_id' => (string) $invoice->id,
            'type' => 'rental',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();
    postStripeWebhook($this, $payload)->assertOk();

    $key = PaymentIdempotencyKey::where('idempotency_key', 'evt_global_idempotency')->first();

    expect($key)->not->toBeNull()
        ->and($key->status)->toBe(PaymentIdempotencyKey::STATUS_COMPLETED)
        ->and(PaymentIdempotencyKey::where('idempotency_key', 'evt_global_idempotency')->count())->toBe(1)
        ->and(Payment::where('transaction_id', 'pi_global_idempotency')->count())->toBe(1);
});

test('webhook controller only stores and queues event when queue is faked', function () {
    Queue::fake();
    [$booking, $invoice] = auditBookingWithInvoice();

    $payload = auditPaymentIntentPayload('payment_intent.succeeded', 'pi_queued_ingestion_only', $booking, [
        'id' => 'evt_queued_ingestion_only',
        'status' => 'succeeded',
        'amount_received' => 130000,
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'invoice_id' => (string) $invoice->id,
            'type' => 'rental',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();

    Queue::assertPushedOn(config('queue.webhook_queue', 'stripe-webhooks'), ProcessStripeWebhookEventJob::class);

    expect(StripeWebhookEvent::where('event_id', 'evt_queued_ingestion_only')->first()->status)->toBe(StripeWebhookEvent::STATUS_PENDING)
        ->and($booking->refresh()->status)->toBe('pending')
        ->and($invoice->refresh()->status)->toBe('pending')
        ->and(Payment::where('transaction_id', 'pi_queued_ingestion_only')->exists())->toBeFalse();
});

test('queued webhook job processes stored event and writes outbox event', function () {
    [$booking, $invoice] = auditBookingWithInvoice();

    $payload = auditPaymentIntentPayload('payment_intent.succeeded', 'pi_job_outbox', $booking, [
        'id' => 'evt_job_outbox',
        'status' => 'succeeded',
        'amount_received' => 130000,
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'invoice_id' => (string) $invoice->id,
            'type' => 'rental',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();

    $storedEvent = StripeWebhookEvent::where('event_id', 'evt_job_outbox')->first();

    expect($storedEvent->status)->toBe(StripeWebhookEvent::STATUS_PROCESSED)
        ->and($storedEvent->attempts)->toBe(1)
        ->and($booking->refresh()->status)->toBe('confirmed')
        ->and(Payment::where('transaction_id', 'pi_job_outbox')->count())->toBe(1)
        ->and(OutboxEvent::where('event_type', 'rental_payment_synchronized')->exists())->toBeTrue();
});

test('booking state machine rejects invalid transitions and records audit trail', function () {
    [$booking] = auditBookingWithInvoice();

    expect(fn () => app(BookingStateMachine::class)->transition($booking, 'active', [
        'source' => 'test',
        'source_event_id' => 'evt_invalid_transition',
        'trace_id' => (string) Str::uuid(),
    ]))->toThrow(RuntimeException::class);

    $transition = BookingStateTransition::where('source_event_id', 'evt_invalid_transition')->first();

    expect($booking->refresh()->status)->toBe('pending')
        ->and($transition)->not->toBeNull()
        ->and($transition->accepted)->toBeFalse()
        ->and($transition->from_status)->toBe('pending')
        ->and($transition->to_status)->toBe('active');
});

test('outbox dispatcher sends payment event through saga and timeline', function () {
    [$booking, $invoice] = auditBookingWithInvoice();
    $traceId = (string) Str::uuid();

    $outboxEvent = OutboxEvent::create([
        'event_type' => 'payment.succeeded',
        'trace_id' => $traceId,
        'source_event_id' => 'evt_saga_payment_succeeded',
        'payload' => [
            'event_id' => 'evt_saga_payment_succeeded',
            'trace_id' => $traceId,
            'booking_id' => $booking->id,
            'invoice_id' => $invoice->id,
            'payment_intent_id' => 'pi_saga_payment',
        ],
    ]);

    app(OutboxDispatcherJob::class, ['outboxEventId' => $outboxEvent->id])->handle(app(EventBus::class));

    $saga = BookingSaga::where('booking_id', $booking->id)->where('trace_id', $traceId)->first();

    expect($outboxEvent->refresh()->dispatched)->toBeTrue()
        ->and($booking->refresh()->status)->toBe('confirmed')
        ->and($saga)->not->toBeNull()
        ->and($saga->status)->toBe('completed')
        ->and(EventTimeline::where('trace_id', $traceId)->where('event_name', 'PaymentSucceededEvent')->exists())->toBeTrue()
        ->and(EventTimeline::where('trace_id', $traceId)->where('event_name', 'BookingConfirmedEvent')->exists())->toBeTrue()
        ->and(EventTimeline::where('trace_id', $traceId)->where('event_name', 'InvoiceCreatedEvent')->exists())->toBeTrue()
        ->and(BookingStateTransition::where('booking_id', $booking->id)->where('accepted', true)->count())->toBe(1);
});

test('event replay command re-runs saga from outbox checkpoint', function () {
    [$booking, $invoice] = auditBookingWithInvoice();
    $traceId = (string) Str::uuid();

    $outboxEvent = OutboxEvent::create([
        'event_type' => 'payment.succeeded',
        'trace_id' => $traceId,
        'source_event_id' => 'evt_replay_saga',
        'payload' => [
            'event_id' => 'evt_replay_saga',
            'trace_id' => $traceId,
            'booking_id' => $booking->id,
            'invoice_id' => $invoice->id,
            'payment_intent_id' => 'pi_replay_saga',
        ],
    ]);

    $this->artisan('events:replay', ['id' => $outboxEvent->id])->assertExitCode(0);
    $this->artisan('events:replay', ['id' => $outboxEvent->id])->assertExitCode(0);

    expect(EventReplayLog::where('event_id', $outboxEvent->id)->where('status', 'completed')->count())->toBe(2)
        ->and($booking->refresh()->status)->toBe('confirmed')
        ->and(BookingSaga::where('booking_id', $booking->id)->where('trace_id', $traceId)->first()->status)->toBe('completed')
        ->and(BookingStateTransition::where('booking_id', $booking->id)->where('accepted', true)->count())->toBe(1);
});

test('distributed event stream processes same outbox event exactly once across workers', function () {
    [$booking, $invoice] = auditBookingWithInvoice();
    $traceId = (string) Str::uuid();

    $outboxEvent = OutboxEvent::create([
        'event_type' => 'payment.succeeded',
        'trace_id' => $traceId,
        'source_event_id' => 'evt_multi_worker_same_event',
        'payload' => [
            'event_id' => 'evt_multi_worker_same_event',
            'trace_id' => $traceId,
            'correlation_id' => $traceId,
            'booking_id' => $booking->id,
            'invoice_id' => $invoice->id,
            'payment_intent_id' => 'pi_multi_worker_same_event',
        ],
    ]);

    app(EventBus::class)->dispatchOutbox($outboxEvent);
    app(EventBus::class)->dispatchOutbox($outboxEvent);

    expect(EventStream::where('source_event_id', 'evt_multi_worker_same_event')->where('event_name', 'PaymentSucceededEvent')->count())->toBe(1)
        ->and(EventStream::where('booking_id', $booking->id)->orderBy('version')->pluck('version')->all())->toBe([1, 2, 3])
        ->and(BookingStateTransition::where('booking_id', $booking->id)->where('accepted', true)->count())->toBe(1)
        ->and($booking->refresh()->status)->toBe('confirmed');
});

test('global idempotency recovers expired processing records across workers', function () {
    $service = app(GlobalIdempotencyService::class);

    $first = $service->begin('test_scope', 'same-global-key', ['worker' => 'one'], 1);

    $first['record']->forceFill([
        'expires_at' => now()->subSecond(),
    ])->save();

    $second = $service->begin('test_scope', 'same-global-key', ['worker' => 'two'], 60);

    expect($first['status'])->toBe('started')
        ->and($second['status'])->toBe('recovered_expired')
        ->and(GlobalIdempotencyRecord::where('scope', 'test_scope')->where('idempotency_key', 'same-global-key')->count())->toBe(1);
});

test('payment failure saga compensation cancels confirmed booking logically', function () {
    [$booking] = auditBookingWithInvoice([
        'status' => 'confirmed',
        'security_deposit_status' => 'held',
    ]);
    $traceId = (string) Str::uuid();

    app(EventBus::class)->dispatch(new PaymentFailedEvent([
        'event_id' => 'evt_compensate_payment_failed',
        'trace_id' => $traceId,
        'correlation_id' => $traceId,
        'booking_id' => $booking->id,
        'payment_intent_id' => 'pi_compensate_payment_failed',
    ], $traceId, 'evt_compensate_payment_failed'));

    $saga = BookingSaga::where('booking_id', $booking->id)->where('trace_id', $traceId)->first();

    expect($booking->refresh()->status)->toBe('cancelled')
        ->and($saga->status)->toBe('failed')
        ->and($saga->compensation_status)->toBe('completed')
        ->and($saga->compensation_payload['actions'])->toContain('booking_confirmation_rolled_back_to_cancelled')
        ->and($saga->compensation_payload['actions'])->toContain('refund_payment_intent_requested_logically')
        ->and($saga->compensation_payload['actions'])->toContain('release_deposit_requested_logically');
});

test('stuck webhook recovery requeues retryable processing events and tracks worker cursor', function () {
    Queue::fake();

    $event = StripeWebhookEvent::create([
        'event_id' => 'evt_stuck_recovery',
        'type' => 'payment_intent.succeeded',
        'payload' => [
            'id' => 'evt_stuck_recovery',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_stuck_recovery', 'metadata' => ['type' => 'rental']]],
        ],
        'status' => StripeWebhookEvent::STATUS_PROCESSING,
        'attempts' => 1,
    ]);
    $event->forceFill(['updated_at' => now()->subMinutes(30)])->save();

    app(RecoverStuckWebhookJobs::class, ['staleAfterSeconds' => 60])->handle(app(\App\Services\WorkerHeartbeatService::class));

    expect($event->refresh()->status)->toBe(StripeWebhookEvent::STATUS_PENDING);
});

test('stripe event without matching booking is ignored and does not fail job', function () {
    $event = StripeWebhookEvent::create([
        'event_id' => 'evt_missing_booking_ignored',
        'type' => 'payment_intent.succeeded',
        'payload' => [
            'id' => 'evt_missing_booking_ignored',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_missing_booking_ignored',
                    'status' => 'succeeded',
                    'metadata' => [
                        'booking_id' => '999999',
                        'type' => 'rental',
                    ],
                ],
            ],
        ],
        'status' => StripeWebhookEvent::STATUS_PENDING,
    ]);

    app(ProcessStripeWebhookEventJob::class, ['stripeWebhookEventId' => $event->id])
        ->handle(app(\App\Domain\Payment\PaymentProcessor::class), app(\App\Services\PaymentEventAuditService::class), app(\App\Services\PaymentWebhookMetrics::class));

    expect($event->refresh()->status)->toBe(StripeWebhookEvent::STATUS_PROCESSED)
        ->and($event->error_message)->toBeNull()
        ->and($event->attempts)->toBe(1)
        ->and(DB::table('failed_jobs')->count())->toBe(0)
        ->and(FailedWebhookEvent::count())->toBe(0);
});

test('unsupported stripe event is ignored and does not enter fail loop', function () {
    $event = StripeWebhookEvent::create([
        'event_id' => 'evt_charge_updated_ignored',
        'type' => 'charge.updated',
        'payload' => [
            'id' => 'evt_charge_updated_ignored',
            'type' => 'charge.updated',
            'data' => [
                'object' => [
                    'id' => 'ch_ignored',
                    'payment_intent' => 'pi_charge_updated_ignored',
                    'metadata' => [],
                ],
            ],
        ],
        'status' => StripeWebhookEvent::STATUS_PENDING,
    ]);

    app(ProcessStripeWebhookEventJob::class, ['stripeWebhookEventId' => $event->id])
        ->handle(app(\App\Domain\Payment\PaymentProcessor::class), app(\App\Services\PaymentEventAuditService::class), app(\App\Services\PaymentWebhookMetrics::class));

    expect($event->refresh()->status)->toBe(StripeWebhookEvent::STATUS_PROCESSED)
        ->and($event->attempts)->toBe(1)
        ->and($event->failed_at)->toBeNull()
        ->and(DB::table('failed_jobs')->count())->toBe(0)
        ->and(\App\Models\WorkerHeartbeat::where('worker_name', gethostname().':stripe-webhook:'.getmypid())->first()?->current_job)->toBeNull();
});

test('payment intent created test trigger is ignored safely', function () {
    $event = StripeWebhookEvent::create([
        'event_id' => 'evt_payment_intent_created_ignored',
        'type' => 'payment_intent.created',
        'payload' => [
            'id' => 'evt_payment_intent_created_ignored',
            'type' => 'payment_intent.created',
            'data' => [
                'object' => [
                    'id' => 'pi_created_ignored',
                    'status' => 'requires_payment_method',
                    'metadata' => ['type' => 'rental'],
                ],
            ],
        ],
        'status' => StripeWebhookEvent::STATUS_PENDING,
    ]);

    app(ProcessStripeWebhookEventJob::class, ['stripeWebhookEventId' => $event->id])
        ->handle(app(\App\Domain\Payment\PaymentProcessor::class), app(\App\Services\PaymentEventAuditService::class), app(\App\Services\PaymentWebhookMetrics::class));

    expect($event->refresh()->status)->toBe(StripeWebhookEvent::STATUS_PROCESSED)
        ->and(DB::table('failed_jobs')->count())->toBe(0);
});

test('stripe metadata array object and stripe-like objects are normalized safely', function () {
    $job = new ProcessStripeWebhookEventJob(1);
    $method = new ReflectionMethod($job, 'toArraySafe');
    $method->setAccessible(true);

    $stripeLike = new class
    {
        public function toArray(): array
        {
            return [
                'booking_id' => '77',
                'nested' => (object) ['source' => 'stripe_object'],
            ];
        }
    };

    expect($method->invoke($job, ['booking_id' => '42', 'type' => 'rental']))->toBe([
        'booking_id' => '42',
        'type' => 'rental',
    ])
        ->and($method->invoke($job, (object) ['booking_id' => '43', 'type' => 'security_deposit']))->toBe([
            'booking_id' => '43',
            'type' => 'security_deposit',
        ])
        ->and($method->invoke($job, $stripeLike))->toBe([
            'booking_id' => '77',
            'nested' => ['source' => 'stripe_object'],
        ]);
});

test('failed jobs table is compatible with queue failure commands', function () {
    expect(Schema::hasTable('failed_jobs'))->toBeTrue()
        ->and(Schema::hasColumn('failed_jobs', 'uuid'))->toBeTrue()
        ->and(Schema::hasColumn('failed_jobs', 'connection'))->toBeTrue()
        ->and(Schema::hasColumn('failed_jobs', 'queue'))->toBeTrue()
        ->and(Schema::hasColumn('failed_jobs', 'payload'))->toBeTrue()
        ->and(Schema::hasColumn('failed_jobs', 'exception'))->toBeTrue()
        ->and(Schema::hasColumn('failed_jobs', 'failed_at'))->toBeTrue()
        ->and(Schema::hasColumn('failed_jobs', 'created_at'))->toBeTrue();

    $this->artisan('queue:failed')->assertExitCode(0);
    $this->artisan('queue:failed:show')->assertExitCode(0);
});

test('duplicate webhook different event id same payment intent cannot duplicate payment mutation', function () {
    [$booking, $invoice] = auditBookingWithInvoice();

    $payload = auditPaymentIntentPayload('payment_intent.succeeded', 'pi_duplicate_intent', $booking, [
        'status' => 'succeeded',
        'amount_received' => 130000,
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'invoice_id' => (string) $invoice->id,
            'type' => 'rental',
        ],
    ]);

    postStripeWebhook($this, array_replace($payload, ['id' => 'evt_duplicate_intent_a']))->assertOk();
    postStripeWebhook($this, array_replace($payload, ['id' => 'evt_duplicate_intent_b']))->assertOk();

    expect(PaymentEventAudit::where('payment_intent_id', 'pi_duplicate_intent')->count())->toBe(2)
        ->and(Payment::where('transaction_id', 'pi_duplicate_intent')->count())->toBe(1);
});

test('rental webhook repairs partial sync when payment row exists but booking and invoice are stale', function () {
    [$booking, $invoice] = auditBookingWithInvoice();

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'amount' => 1300,
        'method' => 'card',
        'type' => 'payment',
        'status' => 'completed',
        'transaction_id' => 'pi_partial_sync',
        'paid_at' => now(),
    ]);

    expect($booking->status)->toBe('pending')
        ->and($invoice->status)->toBe('pending');

    $payload = auditPaymentIntentPayload('payment_intent.succeeded', 'pi_partial_sync', $booking, [
        'status' => 'succeeded',
        'amount_received' => 130000,
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'invoice_id' => (string) $invoice->id,
            'type' => 'rental',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();

    expect($booking->refresh()->status)->toBe('confirmed')
        ->and($booking->advance_payment_status)->toBe('paid')
        ->and($invoice->refresh()->status)->toBe('paid')
        ->and(Payment::where('transaction_id', 'pi_partial_sync')->count())->toBe(1);
});

test('rental charge webhook is ignored and does not create duplicate payment state', function () {
    [$booking, $invoice] = auditBookingWithInvoice();

    $payload = [
        'id' => 'evt_charge_ignored',
        'type' => 'charge.succeeded',
        'data' => [
            'object' => [
                'id' => 'ch_audit_rental',
                'object' => 'charge',
                'payment_intent' => 'pi_charge_only',
                'status' => 'succeeded',
                'amount' => 130000,
                'metadata' => [
                    'booking_id' => (string) $booking->id,
                    'invoice_id' => (string) $invoice->id,
                    'type' => 'rental',
                ],
            ],
        ],
    ];

    postStripeWebhook($this, $payload)->assertOk();

    expect($booking->refresh()->status)->toBe('pending')
        ->and($invoice->refresh()->status)->toBe('pending')
        ->and(Payment::where('transaction_id', 'pi_charge_only')->count())->toBe(0);
});

test('security deposit authorization is idempotent and never changes booking or invoice payment state', function () {
    [$booking, $invoice] = auditBookingWithInvoice();

    $payload = auditPaymentIntentPayload('payment_intent.amount_capturable_updated', 'pi_audit_deposit', $booking, [
        'status' => 'requires_capture',
        'amount' => 500000,
        'amount_capturable' => 500000,
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'type' => 'security_deposit',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();
    postStripeWebhook($this, $payload)->assertOk();

    expect($booking->refresh()->security_deposit_status)->toBe('held')
        ->and($booking->security_deposit_intent_id)->toBe('pi_audit_deposit')
        ->and((float) $booking->security_deposit_amount)->toBe(5000.0)
        ->and((float) $booking->security_deposit_capturable_amount)->toBe(5000.0)
        ->and($booking->status)->toBe('pending')
        ->and($invoice->refresh()->status)->toBe('pending')
        ->and(Payment::count())->toBe(0);
});

test('security deposit payment failed returns to pending without changing booking status', function () {
    [$booking, $invoice] = auditBookingWithInvoice([
        'status' => 'confirmed',
        'security_deposit_status' => 'held',
        'security_deposit_intent_id' => 'pi_deposit_failed',
        'security_deposit_capturable_amount' => 3000,
    ]);

    $payload = auditPaymentIntentPayload('payment_intent.payment_failed', 'pi_deposit_failed', $booking, [
        'status' => 'requires_payment_method',
        'amount' => 300000,
        'amount_capturable' => 0,
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'type' => 'security_deposit',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();
    postStripeWebhook($this, $payload)->assertOk();

    expect($booking->refresh()->security_deposit_status)->toBe('pending')
        ->and((float) $booking->security_deposit_capturable_amount)->toBe(0.0)
        ->and($booking->status)->toBe('confirmed')
        ->and($invoice->refresh()->status)->toBe('pending');
});

test('security deposit captured event is idempotent and does not mark rental invoice paid', function () {
    [$booking, $invoice] = auditBookingWithInvoice([
        'status' => 'confirmed',
        'security_deposit_status' => 'held',
        'security_deposit_intent_id' => 'pi_deposit_capture',
        'security_deposit_capturable_amount' => 5000,
    ]);

    $payload = auditPaymentIntentPayload('payment_intent.succeeded', 'pi_deposit_capture', $booking, [
        'status' => 'succeeded',
        'amount' => 500000,
        'amount_received' => 500000,
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'type' => 'security_deposit',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();
    postStripeWebhook($this, $payload)->assertOk();

    expect($booking->refresh()->security_deposit_status)->toBe('captured')
        ->and((float) $booking->security_deposit_charged_amount)->toBe(5000.0)
        ->and((float) $booking->security_deposit_capturable_amount)->toBe(0.0)
        ->and($booking->status)->toBe('confirmed')
        ->and($invoice->refresh()->status)->toBe('pending')
        ->and(Payment::where('transaction_id', 'pi_deposit_capture')->count())->toBe(0);
});

test('security deposit captured event can finalize when held webhook arrives late or is missing', function () {
    [$booking, $invoice] = auditBookingWithInvoice([
        'status' => 'confirmed',
        'security_deposit_status' => 'pending',
        'security_deposit_intent_id' => 'pi_deposit_capture_without_hold',
        'security_deposit_capturable_amount' => 5000,
    ]);

    $payload = auditPaymentIntentPayload('payment_intent.succeeded', 'pi_deposit_capture_without_hold', $booking, [
        'status' => 'succeeded',
        'amount' => 500000,
        'amount_received' => 500000,
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'type' => 'security_deposit',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();

    expect($booking->refresh()->security_deposit_status)->toBe('captured')
        ->and((float) $booking->security_deposit_charged_amount)->toBe(5000.0)
        ->and((float) $booking->security_deposit_capturable_amount)->toBe(0.0)
        ->and($invoice->refresh()->status)->toBe('pending')
        ->and(Payment::where('transaction_id', 'pi_deposit_capture_without_hold')->count())->toBe(0);
});

test('security deposit success with capturable amount is treated as held not disabled pending', function () {
    [$booking, $invoice] = auditBookingWithInvoice([
        'status' => 'confirmed',
        'security_deposit_status' => 'pending',
        'security_deposit_intent_id' => 'pi_deposit_success_requires_capture',
    ]);

    $payload = auditPaymentIntentPayload('payment_intent.succeeded', 'pi_deposit_success_requires_capture', $booking, [
        'status' => 'succeeded',
        'amount' => 500000,
        'amount_received' => 0,
        'amount_capturable' => 500000,
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'type' => 'security_deposit',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();

    expect($booking->refresh()->security_deposit_status)->toBe('held')
        ->and((float) $booking->security_deposit_capturable_amount)->toBe(5000.0)
        ->and((float) $booking->security_deposit_charged_amount)->toBe(0.0)
        ->and($invoice->refresh()->status)->toBe('pending')
        ->and(Payment::where('transaction_id', 'pi_deposit_success_requires_capture')->exists())->toBeFalse();
});

test('security deposit canceled releases hold idempotently without booking status regression', function () {
    [$booking, $invoice] = auditBookingWithInvoice([
        'status' => 'confirmed',
        'security_deposit_status' => 'held',
        'security_deposit_intent_id' => 'pi_deposit_cancel',
        'security_deposit_capturable_amount' => 5000,
    ]);

    $payload = auditPaymentIntentPayload('payment_intent.canceled', 'pi_deposit_cancel', $booking, [
        'status' => 'canceled',
        'amount' => 500000,
        'amount_capturable' => 0,
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'type' => 'security_deposit',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();
    $releasedAt = $booking->refresh()->security_deposit_released_at?->toISOString();
    postStripeWebhook($this, $payload)->assertOk();

    expect($booking->refresh()->security_deposit_status)->toBe('refunded')
        ->and($booking->security_deposit_released_at?->toISOString())->toBe($releasedAt)
        ->and($booking->status)->toBe('confirmed')
        ->and($invoice->refresh()->status)->toBe('pending')
        ->and(Payment::count())->toBe(0);
});

test('security deposit webhooks are ignored for completed bookings', function () {
    [$booking, $invoice] = auditBookingWithInvoice([
        'status' => 'completed',
        'security_deposit_status' => 'held',
        'security_deposit_intent_id' => 'pi_completed_deposit',
        'security_deposit_capturable_amount' => 5000,
    ]);

    $payload = auditPaymentIntentPayload('payment_intent.succeeded', 'pi_completed_deposit', $booking, [
        'status' => 'succeeded',
        'amount_received' => 500000,
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'type' => 'security_deposit',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();

    expect($booking->refresh()->security_deposit_status)->toBe('held')
        ->and((float) $booking->security_deposit_charged_amount)->toBe(0.0)
        ->and($booking->status)->toBe('completed')
        ->and($invoice->refresh()->status)->toBe('pending')
        ->and(Payment::count())->toBe(0)
        ->and(PaymentEventAudit::where('event_id', $payload['id'])->first()->outcome)->toBe('ignored');
});

test('captured security deposit is not regressed by a later canceled event', function () {
    [$booking] = auditBookingWithInvoice([
        'status' => 'confirmed',
        'security_deposit_status' => 'captured',
        'security_deposit_intent_id' => 'pi_captured_then_cancel',
        'security_deposit_charged_amount' => 1000,
    ]);

    $payload = auditPaymentIntentPayload('payment_intent.canceled', 'pi_captured_then_cancel', $booking, [
        'id' => 'evt_captured_then_cancel_rejected',
        'status' => 'canceled',
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'type' => 'security_deposit',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();

    expect($booking->refresh()->security_deposit_status)->toBe('captured')
        ->and((float) $booking->security_deposit_charged_amount)->toBe(1000.0)
        ->and($booking->security_deposit_released_at)->toBeNull()
        ->and(PaymentEventAudit::where('event_id', 'evt_captured_then_cancel_rejected')->first()->outcome)->toBe('ignored');
});

test('webhook replay after a successful audit exits without another mutation', function () {
    [$booking, $invoice] = auditBookingWithInvoice();

    $payload = auditPaymentIntentPayload('payment_intent.succeeded', 'pi_replay_after_success', $booking, [
        'id' => 'evt_replay_after_success',
        'status' => 'succeeded',
        'amount_received' => 130000,
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'invoice_id' => (string) $invoice->id,
            'type' => 'rental',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();
    Payment::where('transaction_id', 'pi_replay_after_success')->first()->update(['notes' => 'first mutation']);
    postStripeWebhook($this, $payload)->assertOk();

    expect(Payment::where('transaction_id', 'pi_replay_after_success')->count())->toBe(1)
        ->and(Payment::where('transaction_id', 'pi_replay_after_success')->first()->notes)->toBe('first mutation')
        ->and(PaymentEventAudit::where('event_id', 'evt_replay_after_success')->count())->toBe(1);
});

test('payment webhook lock recovers a stale lock and releases after success', function () {
    [$booking] = auditBookingWithInvoice();

    DB::table('payment_webhook_locks')->insert([
        'booking_id' => $booking->id,
        'locked_at' => now()->subMinutes(2),
        'expires_at' => now()->subMinute(),
        'owner_token' => 'stale-owner',
    ]);

    $lockService = app(PaymentWebhookLockService::class);
    $result = $lockService->withBookingLock($booking->id, fn () => 'locked');

    expect($result)->toBe('locked')
        ->and(in_array($lockService->lastStatus(), ['cache_acquired', 'database_acquired', 'redis_or_cache_acquired'], true))->toBeTrue()
        ->and(DB::table('payment_webhook_locks')->where('booking_id', $booking->id)->exists())->toBeFalse();
});

test('payment webhook lock releases after exception', function () {
    [$booking] = auditBookingWithInvoice();

    try {
        app(PaymentWebhookLockService::class)->withBookingLock($booking->id, function () {
            throw new RuntimeException('forced lock failure');
        });
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('forced lock failure');
    }

    expect(DB::table('payment_webhook_locks')->where('booking_id', $booking->id)->exists())->toBeFalse();
});

test('terminal security deposit replay is persisted as ignored audit', function () {
    [$booking] = auditBookingWithInvoice([
        'status' => 'confirmed',
        'security_deposit_status' => 'refunded',
        'security_deposit_intent_id' => 'pi_released_then_capture',
    ]);

    $payload = auditPaymentIntentPayload('payment_intent.succeeded', 'pi_released_then_capture', $booking, [
        'id' => 'evt_released_then_capture_rejected',
        'status' => 'succeeded',
        'amount_received' => 500000,
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'type' => 'security_deposit',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();

    $audit = PaymentEventAudit::where('event_id', 'evt_released_then_capture_rejected')->first();

    expect($booking->refresh()->security_deposit_status)->toBe('refunded')
        ->and($audit->outcome)->toBe('ignored')
        ->and($audit->error_message)->toBeNull()
        ->and(FailedWebhookEvent::where('event_id', 'evt_released_then_capture_rejected')->exists())->toBeFalse();
});

test('payment webhook migrations expose mysql safe lock and audit columns', function () {
    expect(Schema::hasColumn('payment_webhook_locks', 'expires_at'))->toBeTrue()
        ->and(Schema::hasColumn('payment_webhook_locks', 'owner_token'))->toBeTrue()
        ->and(Schema::hasColumn('payment_event_audits', 'processed_at'))->toBeTrue()
        ->and(Schema::hasColumn('payment_event_audits', 'outcome'))->toBeTrue()
        ->and(Schema::hasColumn('payment_event_audits', 'error_message'))->toBeTrue()
        ->and(Schema::hasTable('payment_idempotency_keys'))->toBeTrue()
        ->and(Schema::hasTable('failed_webhook_events'))->toBeTrue()
        ->and(Schema::hasTable('stripe_webhook_events'))->toBeTrue()
        ->and(Schema::hasTable('outbox_events'))->toBeTrue()
        ->and(Schema::hasTable('booking_sagas'))->toBeTrue()
        ->and(Schema::hasTable('booking_state_transitions'))->toBeTrue()
        ->and(Schema::hasTable('event_timelines'))->toBeTrue()
        ->and(Schema::hasTable('event_replay_log'))->toBeTrue()
        ->and(Schema::hasTable('event_stream'))->toBeTrue()
        ->and(Schema::hasTable('event_stream_cursors'))->toBeTrue()
        ->and(Schema::hasTable('worker_heartbeats'))->toBeTrue()
        ->and(Schema::hasTable('global_idempotency_records'))->toBeTrue();

    $migration = file_get_contents(database_path('migrations/2026_05_16_020100_create_payment_webhook_locks_table.php'));

    expect($migration)->toContain("timestamp('expires_at')->nullable()")
        ->and($migration)->toContain("index('expires_at')");
});

test('two webhook workers for one booking converge through the booking lock', function () {
    [$booking, $invoice] = auditBookingWithInvoice();

    foreach (['evt_worker_one', 'evt_worker_two'] as $eventId) {
        $payload = auditPaymentIntentPayload('payment_intent.succeeded', 'pi_two_workers', $booking, [
            'id' => $eventId,
            'status' => 'succeeded',
            'amount_received' => 130000,
            'metadata' => [
                'booking_id' => (string) $booking->id,
                'invoice_id' => (string) $invoice->id,
                'type' => 'rental',
            ],
        ]);

        postStripeWebhook($this, $payload)->assertOk();
    }

    expect($booking->refresh()->status)->toBe('confirmed')
        ->and(Payment::where('transaction_id', 'pi_two_workers')->count())->toBe(1)
        ->and(DB::table('payment_webhook_locks')->where('booking_id', $booking->id)->exists())->toBeFalse();
});

test('admin action and webhook race cannot regress captured terminal state', function () {
    [$booking] = auditBookingWithInvoice([
        'status' => 'confirmed',
        'security_deposit_status' => 'captured',
        'security_deposit_intent_id' => 'pi_admin_webhook_race',
        'security_deposit_charged_amount' => 750,
    ]);

    $payload = auditPaymentIntentPayload('payment_intent.canceled', 'pi_admin_webhook_race', $booking, [
        'id' => 'evt_admin_webhook_race_release',
        'status' => 'canceled',
        'metadata' => [
            'booking_id' => (string) $booking->id,
            'type' => 'security_deposit',
        ],
    ]);

    postStripeWebhook($this, $payload)->assertOk();

    expect($booking->refresh()->security_deposit_status)->toBe('captured')
        ->and((float) $booking->security_deposit_charged_amount)->toBe(750.0)
        ->and(PaymentEventAudit::where('event_id', 'evt_admin_webhook_race_release')->first()->outcome)->toBe('ignored');
});

function auditBookingWithInvoice(array $bookingOverrides = [], array $invoiceOverrides = []): array
{
    $user = User::factory()->create();

    $location = Location::create([
        'name' => 'Audit Casablanca',
        'address' => '1 Audit Street',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000000',
        'email' => fake()->unique()->safeEmail(),
        'opening_time' => '08:00',
        'closing_time' => '20:00',
        'is_active' => true,
    ]);

    $car = Car::create([
        'brand' => 'Toyota',
        'model' => 'Corolla',
        'year' => 2024,
        'type' => 'Sedan',
        'transmission' => 'Automatic',
        'fuel_type' => 'Petrol',
        'seats' => 5,
        'doors' => 4,
        'luggage' => 2,
        'price_per_day' => 650,
        'image' => 'cars/audit.jpg',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => 5000,
    ]);

    $booking = Booking::create(array_merge([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'rental_price_per_day' => 650,
        'insurance_fixed_price' => 0,
        'total_amount' => 1300,
        'status' => 'pending',
        'advance_payment_amount' => 1300,
        'advance_payment_status' => 'pending',
        'security_deposit_amount' => 5000,
        'security_deposit_capturable_amount' => 0,
        'security_deposit_charged_amount' => 0,
        'security_deposit_status' => 'pending',
    ], $bookingOverrides));

    $invoice = Invoice::create(array_merge([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => 1300,
        'tax_amount' => 0,
        'total_amount' => 1300,
        'status' => 'pending',
    ], $invoiceOverrides));

    return [$booking, $invoice, $user, $car, $location];
}

function auditPaymentIntentPayload(string $eventType, string $intentId, Booking $booking, array $overrides = []): array
{
    $eventId = $overrides['id'] ?? 'evt_'.str_replace(['.', '_'], '-', $eventType).'_'.$intentId;
    unset($overrides['id']);

    return [
        'id' => $eventId,
        'type' => $eventType,
        'data' => [
            'object' => array_merge([
                'id' => $intentId,
                'object' => 'payment_intent',
                'status' => 'requires_payment_method',
                'amount' => 130000,
                'amount_received' => 0,
                'amount_capturable' => 0,
                'metadata' => [
                    'booking_id' => (string) $booking->id,
                    'type' => 'rental',
                ],
            ], $overrides),
        ],
    ];
}

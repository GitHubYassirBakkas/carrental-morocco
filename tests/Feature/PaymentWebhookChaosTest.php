<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payment;
use App\Models\User;

test('rental succeeds after charge succeeded and ignored intermediate events', function () {
    [$booking, $invoice] = chaosBookingWithInvoice();

    $events = [
        chaosChargePayload('charge.succeeded', 'ch_before_pi', 'pi_chaos_rental', $booking, $invoice),
        chaosPaymentIntentPayload('payment_intent.created', 'pi_chaos_rental', $booking, [
            'created' => now()->subMinutes(2)->timestamp,
            'status' => 'requires_payment_method',
            'metadata' => chaosRentalMetadata($booking, $invoice),
        ]),
        chaosPaymentIntentPayload('payment_intent.processing', 'pi_chaos_rental', $booking, [
            'created' => now()->subMinute()->timestamp,
            'status' => 'processing',
            'metadata' => chaosRentalMetadata($booking, $invoice),
        ]),
        chaosPaymentIntentPayload('payment_intent.succeeded', 'pi_chaos_rental', $booking, [
            'created' => now()->timestamp,
            'status' => 'succeeded',
            'amount_received' => 130000,
            'metadata' => chaosRentalMetadata($booking, $invoice),
        ]),
    ];

    foreach ($events as $payload) {
        postStripeWebhook($this, $payload)->assertOk();
    }

    expect($booking->refresh()->status)->toBe('confirmed')
        ->and($booking->advance_payment_status)->toBe('paid')
        ->and($invoice->refresh()->status)->toBe('paid')
        ->and(Payment::where('transaction_id', 'pi_chaos_rental')->count())->toBe(1);
});

test('rental duplicate retries with different event ids and timestamps are idempotent', function () {
    [$booking, $invoice] = chaosBookingWithInvoice();

    for ($i = 0; $i < 5; $i++) {
        $payload = chaosPaymentIntentPayload('payment_intent.succeeded', 'pi_retry_rental', $booking, [
            'id' => 'evt_retry_rental_' . $i,
            'created' => now()->addSeconds($i)->timestamp,
            'status' => 'succeeded',
            'amount_received' => 130000,
            'metadata' => chaosRentalMetadata($booking, $invoice),
        ]);

        postStripeWebhook($this, $payload)->assertOk();
    }

    expect($booking->refresh()->status)->toBe('confirmed')
        ->and($invoice->refresh()->status)->toBe('paid')
        ->and(Payment::where('transaction_id', 'pi_retry_rental')->count())->toBe(1);
});

test('rental retry repairs partial invoice update where booking was not synced', function () {
    [$booking, $invoice] = chaosBookingWithInvoice([], ['status' => 'paid']);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'amount' => 1300,
        'method' => 'card',
        'type' => 'payment',
        'status' => 'completed',
        'transaction_id' => 'pi_partial_retry',
        'paid_at' => now(),
    ]);

    $payload = chaosPaymentIntentPayload('payment_intent.succeeded', 'pi_partial_retry', $booking, [
        'status' => 'succeeded',
        'amount_received' => 130000,
        'metadata' => chaosRentalMetadata($booking, $invoice),
    ]);

    for ($i = 0; $i < 3; $i++) {
        postStripeWebhook($this, array_replace($payload, ['id' => 'evt_partial_retry_' . $i]))->assertOk();
    }

    expect($booking->refresh()->status)->toBe('confirmed')
        ->and($booking->advance_payment_status)->toBe('paid')
        ->and($invoice->refresh()->status)->toBe('paid')
        ->and(Payment::where('transaction_id', 'pi_partial_retry')->count())->toBe(1);
});

test('deposit release is not regressed by stale authorization retries', function () {
    [$booking] = chaosBookingWithInvoice([
        'status' => 'confirmed',
        'security_deposit_status' => 'held',
        'security_deposit_intent_id' => 'pi_deposit_out_of_order',
        'security_deposit_capturable_amount' => 5000,
    ]);

    $release = chaosDepositPayload('payment_intent.canceled', 'pi_deposit_out_of_order', $booking, [
        'id' => 'evt_deposit_release_newer',
        'created' => now()->timestamp,
        'status' => 'canceled',
        'amount_capturable' => 0,
    ]);

    $staleHold = chaosDepositPayload('payment_intent.amount_capturable_updated', 'pi_deposit_out_of_order', $booking, [
        'id' => 'evt_deposit_hold_older_retry',
        'created' => now()->subMinutes(5)->timestamp,
        'status' => 'requires_capture',
        'amount' => 500000,
        'amount_capturable' => 500000,
    ]);

    postStripeWebhook($this, $release)->assertOk();
    $releasedAt = $booking->refresh()->security_deposit_released_at?->toISOString();
    postStripeWebhook($this, $staleHold)->assertOk();
    postStripeWebhook($this, $staleHold)->assertOk();

    expect($booking->refresh()->security_deposit_status)->toBe('refunded')
        ->and((float) $booking->security_deposit_capturable_amount)->toBe(0.0)
        ->and($booking->security_deposit_released_at?->toISOString())->toBe($releasedAt);
});

test('deposit capture is not regressed by stale hold failed or canceled retries', function () {
    [$booking] = chaosBookingWithInvoice([
        'status' => 'confirmed',
        'security_deposit_status' => 'held',
        'security_deposit_intent_id' => 'pi_deposit_terminal',
        'security_deposit_capturable_amount' => 5000,
    ]);

    $capture = chaosDepositPayload('payment_intent.succeeded', 'pi_deposit_terminal', $booking, [
        'id' => 'evt_deposit_capture_newer',
        'created' => now()->timestamp,
        'status' => 'succeeded',
        'amount' => 500000,
        'amount_received' => 500000,
    ]);

    $staleHold = chaosDepositPayload('payment_intent.amount_capturable_updated', 'pi_deposit_terminal', $booking, [
        'id' => 'evt_deposit_hold_stale',
        'created' => now()->subMinutes(4)->timestamp,
        'status' => 'requires_capture',
        'amount' => 500000,
        'amount_capturable' => 500000,
    ]);

    $staleFailed = chaosDepositPayload('payment_intent.payment_failed', 'pi_deposit_terminal', $booking, [
        'id' => 'evt_deposit_failed_stale',
        'created' => now()->subMinutes(3)->timestamp,
        'status' => 'requires_payment_method',
        'amount_capturable' => 0,
    ]);

    $staleCanceled = chaosDepositPayload('payment_intent.canceled', 'pi_deposit_terminal', $booking, [
        'id' => 'evt_deposit_cancel_stale',
        'created' => now()->subMinute()->timestamp,
        'status' => 'canceled',
        'amount_capturable' => 0,
    ]);

    postStripeWebhook($this, $capture)->assertOk();
    postStripeWebhook($this, $staleHold)->assertOk();
    postStripeWebhook($this, $staleFailed)->assertOk();
    postStripeWebhook($this, $staleCanceled)->assertOk();

    expect($booking->refresh()->security_deposit_status)->toBe('captured')
        ->and((float) $booking->security_deposit_charged_amount)->toBe(5000.0)
        ->and((float) $booking->security_deposit_capturable_amount)->toBe(0.0)
        ->and(Payment::where('transaction_id', 'pi_deposit_terminal')->count())->toBe(0);
});

test('interleaved rental and deposit retry storm converges to correct final state', function () {
    [$booking, $invoice] = chaosBookingWithInvoice();

    $events = [
        chaosChargePayload('charge.succeeded', 'ch_interleaved', 'pi_interleaved_rental', $booking, $invoice),
        chaosDepositPayload('payment_intent.amount_capturable_updated', 'pi_interleaved_deposit', $booking, [
            'status' => 'requires_capture',
            'amount' => 500000,
            'amount_capturable' => 500000,
        ]),
        chaosPaymentIntentPayload('payment_intent.succeeded', 'pi_interleaved_rental', $booking, [
            'status' => 'succeeded',
            'amount_received' => 130000,
            'metadata' => chaosRentalMetadata($booking, $invoice),
        ]),
        chaosPaymentIntentPayload('payment_intent.succeeded', 'pi_interleaved_rental', $booking, [
            'id' => 'evt_interleaved_rental_retry',
            'created' => now()->addSecond()->timestamp,
            'status' => 'succeeded',
            'amount_received' => 130000,
            'metadata' => chaosRentalMetadata($booking, $invoice),
        ]),
        chaosDepositPayload('payment_intent.amount_capturable_updated', 'pi_interleaved_deposit', $booking, [
            'id' => 'evt_interleaved_deposit_retry',
            'created' => now()->addSeconds(2)->timestamp,
            'status' => 'requires_capture',
            'amount' => 500000,
            'amount_capturable' => 500000,
        ]),
    ];

    foreach ($events as $payload) {
        postStripeWebhook($this, $payload)->assertOk();
    }

    expect($booking->refresh()->status)->toBe('confirmed')
        ->and($invoice->refresh()->status)->toBe('paid')
        ->and($booking->security_deposit_status)->toBe('held')
        ->and($booking->security_deposit_intent_id)->toBe('pi_interleaved_deposit')
        ->and(Payment::where('transaction_id', 'pi_interleaved_rental')->count())->toBe(1)
        ->and(Payment::where('transaction_id', 'pi_interleaved_deposit')->count())->toBe(0);
});

test('simulated concurrent webhook workers converge across event order permutations', function () {
    $orders = [
        ['charge', 'deposit_hold', 'rental_success', 'rental_retry', 'deposit_retry'],
        ['deposit_hold', 'charge', 'rental_retry', 'deposit_retry', 'rental_success'],
        ['rental_retry', 'deposit_retry', 'charge', 'deposit_hold', 'rental_success'],
    ];

    foreach ($orders as $index => $order) {
        [$booking, $invoice] = chaosBookingWithInvoice();

        $events = [
            'charge' => chaosChargePayload('charge.succeeded', 'ch_perm_' . $index, 'pi_perm_rental_' . $index, $booking, $invoice),
            'deposit_hold' => chaosDepositPayload('payment_intent.amount_capturable_updated', 'pi_perm_deposit_' . $index, $booking, [
                'status' => 'requires_capture',
                'amount' => 500000,
                'amount_capturable' => 500000,
            ]),
            'rental_success' => chaosPaymentIntentPayload('payment_intent.succeeded', 'pi_perm_rental_' . $index, $booking, [
                'status' => 'succeeded',
                'amount_received' => 130000,
                'metadata' => chaosRentalMetadata($booking, $invoice),
            ]),
            'rental_retry' => chaosPaymentIntentPayload('payment_intent.succeeded', 'pi_perm_rental_' . $index, $booking, [
                'id' => 'evt_perm_rental_retry_' . $index,
                'created' => now()->addSeconds($index + 1)->timestamp,
                'status' => 'succeeded',
                'amount_received' => 130000,
                'metadata' => chaosRentalMetadata($booking, $invoice),
            ]),
            'deposit_retry' => chaosDepositPayload('payment_intent.amount_capturable_updated', 'pi_perm_deposit_' . $index, $booking, [
                'id' => 'evt_perm_deposit_retry_' . $index,
                'created' => now()->addSeconds($index + 2)->timestamp,
                'status' => 'requires_capture',
                'amount' => 500000,
                'amount_capturable' => 500000,
            ]),
        ];

        foreach ($order as $eventName) {
            postStripeWebhook($this, $events[$eventName])->assertOk();
        }

        expect($booking->refresh()->status)->toBe('confirmed')
            ->and($booking->advance_payment_status)->toBe('paid')
            ->and($invoice->refresh()->status)->toBe('paid')
            ->and($booking->security_deposit_status)->toBe('held')
            ->and(Payment::where('transaction_id', 'pi_perm_rental_' . $index)->count())->toBe(1)
            ->and(Payment::where('transaction_id', 'pi_perm_deposit_' . $index)->count())->toBe(0);
    }
});

function chaosBookingWithInvoice(array $bookingOverrides = [], array $invoiceOverrides = []): array
{
    $user = User::factory()->create();

    $location = Location::create([
        'name' => 'Chaos Casablanca',
        'address' => '99 Chaos Street',
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
        'image' => 'cars/chaos.jpg',
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
        'subtotal' => 1300,
        'tax_amount' => 0,
        'total_amount' => 1300,
        'status' => 'pending',
    ], $invoiceOverrides));

    return [$booking, $invoice, $user, $car, $location];
}

function chaosPaymentIntentPayload(string $eventType, string $intentId, Booking $booking, array $overrides = []): array
{
    $created = $overrides['created'] ?? now()->timestamp;
    $eventId = $overrides['id'] ?? 'evt_' . str_replace('.', '_', $eventType) . '_' . $intentId . '_' . $created;
    unset($overrides['created'], $overrides['id']);

    return [
        'id' => $eventId,
        'type' => $eventType,
        'created' => $created,
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

function chaosDepositPayload(string $eventType, string $intentId, Booking $booking, array $overrides = []): array
{
    $metadata = [
        'booking_id' => (string) $booking->id,
        'type' => 'security_deposit',
    ];

    return chaosPaymentIntentPayload($eventType, $intentId, $booking, array_merge([
        'metadata' => $metadata,
        'amount' => 500000,
    ], $overrides));
}

function chaosChargePayload(string $eventType, string $chargeId, string $intentId, Booking $booking, Invoice $invoice): array
{
    return [
        'id' => 'evt_' . str_replace('.', '_', $eventType) . '_' . $chargeId,
        'type' => $eventType,
        'created' => now()->timestamp,
        'data' => [
            'object' => [
                'id' => $chargeId,
                'object' => 'charge',
                'payment_intent' => $intentId,
                'status' => 'succeeded',
                'amount' => 130000,
                'metadata' => chaosRentalMetadata($booking, $invoice),
            ],
        ],
    ];
}

function chaosRentalMetadata(Booking $booking, Invoice $invoice): array
{
    return [
        'booking_id' => (string) $booking->id,
        'invoice_id' => (string) $invoice->id,
        'type' => 'rental',
    ];
}

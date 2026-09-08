<?php

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\StripePaymentIntentGateway;

function medium2User(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'is_banned' => false,
    ], $attributes));
}

function medium2Booking(?User $user = null, array $attributes = []): Booking
{
    $user ??= medium2User();

    return Booking::factory()->create(array_merge([
        'user_id' => $user->id,
        'status' => Booking::STATUS_PENDING,
        'total_amount' => 1300,
        'advance_payment_amount' => 1300,
        'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PENDING,
        'security_deposit_amount' => 0,
        'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_PENDING,
        'rental_payment_intent_id' => 'pi_medium2_rental',
        'security_deposit_intent_id' => null,
    ], $attributes));
}

function medium2Invoice(Booking $booking, float $total = 1300): Invoice
{
    return Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => $total,
        'tax_amount' => 0,
        'total_amount' => $total,
        'status' => Invoice::STATUS_PENDING,
    ]);
}

function medium2StripeIntent(array $attributes = []): object
{
    return (object) array_merge([
        'id' => 'pi_medium2_rental',
        'status' => 'succeeded',
        'currency' => 'mad',
        'amount' => 130000,
        'amount_received' => 130000,
        'metadata' => (object) [
            'booking_id' => '1',
            'type' => 'rental',
        ],
    ], $attributes);
}

function medium2BindStripeGateway(array $responses): void
{
    $stripe = \Mockery::mock(StripePaymentIntentGateway::class);

    foreach ($responses as $id => $response) {
        $stripe->shouldReceive('retrieve')
            ->once()
            ->with($id)
            ->andReturn($response);
    }

    app()->instance(StripePaymentIntentGateway::class, $stripe);
}

function medium2BindStripeGatewayNever(): void
{
    $stripe = \Mockery::mock(StripePaymentIntentGateway::class);
    $stripe->shouldReceive('retrieve')->never();
    app()->instance(StripePaymentIntentGateway::class, $stripe);
}

function medium2PostCard(mixed $test, Booking $booking, array $overrides = []): Illuminate\Testing\TestResponse
{
    return $test->actingAs($booking->user)
        ->from(route('payments.show', $booking))
        ->post(route('payments.store', $booking), array_merge([
            'payment_method' => 'card',
            'rental_payment_intent' => $booking->rental_payment_intent_id,
        ], $overrides));
}

function medium2AssertNoFinancialMutation(Booking $booking, Invoice $invoice): void
{
    expect(Payment::where('transaction_id', $booking->rental_payment_intent_id)->exists())->toBeFalse()
        ->and((float) $invoice->fresh()->paid_amount)->toBe(0.0)
        ->and($invoice->fresh()->status)->toBe(Invoice::STATUS_PENDING)
        ->and($booking->fresh()->status)->toBe(Booking::STATUS_PENDING)
        ->and($booking->fresh()->advance_payment_status)->toBe(Booking::ADVANCE_PAYMENT_STATUS_PENDING);
}

test('valid rental PaymentIntent passes controller verification without finalizing booking state', function () {
    $booking = medium2Booking();
    $invoice = medium2Invoice($booking);
    medium2BindStripeGateway([
        'pi_medium2_rental' => medium2StripeIntent([
            'metadata' => (object) [
                'booking_id' => (string) $booking->id,
                'type' => 'rental',
            ],
        ]),
    ]);

    medium2PostCard($this, $booking)
        ->assertRedirect(route('bookings.success', $booking))
        ->assertSessionHas('success', 'Payment submitted. We are confirming it with Stripe.');

    expect(Payment::count())->toBe(0)
        ->and((float) $invoice->fresh()->paid_amount)->toBe(0.0)
        ->and($booking->fresh()->status)->toBe(Booking::STATUS_PENDING);
});

test('wrong rental PaymentIntent ID is rejected before Stripe retrieval', function () {
    $booking = medium2Booking();
    $invoice = medium2Invoice($booking);
    medium2BindStripeGatewayNever();

    medium2PostCard($this, $booking, ['rental_payment_intent' => 'pi_attacker_supplied'])
        ->assertRedirect(route('payments.show', $booking))
        ->assertSessionHasErrors('payment');

    medium2AssertNoFinancialMutation($booking, $invoice);
});

test('deposit PaymentIntent used in rental flow is rejected', function () {
    $booking = medium2Booking(attributes: [
        'rental_payment_intent_id' => 'pi_medium2_rental',
        'security_deposit_intent_id' => 'pi_medium2_deposit',
    ]);
    $invoice = medium2Invoice($booking);
    medium2BindStripeGatewayNever();

    medium2PostCard($this, $booking, ['rental_payment_intent' => 'pi_medium2_deposit'])
        ->assertRedirect(route('payments.show', $booking))
        ->assertSessionHasErrors('payment');

    medium2AssertNoFinancialMutation($booking, $invoice);
});

test('rental PaymentIntent used in deposit flow is rejected', function () {
    $booking = medium2Booking(attributes: [
        'security_deposit_amount' => 3000,
        'security_deposit_intent_id' => 'pi_medium2_deposit',
    ]);
    $invoice = medium2Invoice($booking);
    medium2BindStripeGateway([
        'pi_medium2_rental' => medium2StripeIntent([
            'metadata' => (object) [
                'booking_id' => (string) $booking->id,
                'type' => 'rental',
            ],
        ]),
    ]);

    medium2PostCard($this, $booking, ['security_deposit_intent' => 'pi_medium2_rental'])
        ->assertRedirect(route('payments.show', $booking))
        ->assertSessionHasErrors('payment');

    medium2AssertNoFinancialMutation($booking, $invoice);
    expect($booking->fresh()->security_deposit_status)->toBe(Booking::SECURITY_DEPOSIT_STATUS_PENDING);
});

test('missing stored rental PaymentIntent ID fails safely', function () {
    $booking = medium2Booking(attributes: ['rental_payment_intent_id' => null]);
    $invoice = medium2Invoice($booking);
    medium2BindStripeGatewayNever();

    medium2PostCard($this, $booking, ['rental_payment_intent' => 'pi_medium2_rental'])
        ->assertRedirect(route('payments.show', $booking))
        ->assertSessionHasErrors('payment');

    medium2AssertNoFinancialMutation($booking, $invoice);
});

test('amount mismatch is rejected', function () {
    $booking = medium2Booking();
    $invoice = medium2Invoice($booking);
    medium2BindStripeGateway([
        'pi_medium2_rental' => medium2StripeIntent([
            'amount' => 129999,
            'amount_received' => 129999,
            'metadata' => (object) [
                'booking_id' => (string) $booking->id,
                'type' => 'rental',
            ],
        ]),
    ]);

    medium2PostCard($this, $booking)
        ->assertRedirect(route('payments.show', $booking))
        ->assertSessionHasErrors('payment');

    medium2AssertNoFinancialMutation($booking, $invoice);
});

test('currency mismatch is rejected', function () {
    $booking = medium2Booking();
    $invoice = medium2Invoice($booking);
    medium2BindStripeGateway([
        'pi_medium2_rental' => medium2StripeIntent([
            'currency' => 'usd',
            'metadata' => (object) [
                'booking_id' => (string) $booking->id,
                'type' => 'rental',
            ],
        ]),
    ]);

    medium2PostCard($this, $booking)
        ->assertRedirect(route('payments.show', $booking))
        ->assertSessionHasErrors('payment');

    medium2AssertNoFinancialMutation($booking, $invoice);
});

test('missing booking metadata is rejected', function () {
    $booking = medium2Booking();
    $invoice = medium2Invoice($booking);
    medium2BindStripeGateway([
        'pi_medium2_rental' => medium2StripeIntent([
            'metadata' => (object) [
                'type' => 'rental',
            ],
        ]),
    ]);

    medium2PostCard($this, $booking)
        ->assertRedirect(route('payments.show', $booking))
        ->assertSessionHasErrors('payment');

    medium2AssertNoFinancialMutation($booking, $invoice);
});

test('wrong booking metadata is rejected', function () {
    $booking = medium2Booking();
    $invoice = medium2Invoice($booking);
    medium2BindStripeGateway([
        'pi_medium2_rental' => medium2StripeIntent([
            'metadata' => (object) [
                'booking_id' => (string) ($booking->id + 100),
                'type' => 'rental',
            ],
        ]),
    ]);

    medium2PostCard($this, $booking)
        ->assertRedirect(route('payments.show', $booking))
        ->assertSessionHasErrors('payment');

    medium2AssertNoFinancialMutation($booking, $invoice);
});

test('missing type metadata is rejected', function () {
    $booking = medium2Booking();
    $invoice = medium2Invoice($booking);
    medium2BindStripeGateway([
        'pi_medium2_rental' => medium2StripeIntent([
            'metadata' => (object) [
                'booking_id' => (string) $booking->id,
            ],
        ]),
    ]);

    medium2PostCard($this, $booking)
        ->assertRedirect(route('payments.show', $booking))
        ->assertSessionHasErrors('payment');

    medium2AssertNoFinancialMutation($booking, $invoice);
});

test('wrong type metadata is rejected', function () {
    $booking = medium2Booking();
    $invoice = medium2Invoice($booking);
    medium2BindStripeGateway([
        'pi_medium2_rental' => medium2StripeIntent([
            'metadata' => (object) [
                'booking_id' => (string) $booking->id,
                'type' => 'security_deposit',
            ],
        ]),
    ]);

    medium2PostCard($this, $booking)
        ->assertRedirect(route('payments.show', $booking))
        ->assertSessionHasErrors('payment');

    medium2AssertNoFinancialMutation($booking, $invoice);
});

test('unsuccessful rental status is rejected', function () {
    $booking = medium2Booking();
    $invoice = medium2Invoice($booking);
    medium2BindStripeGateway([
        'pi_medium2_rental' => medium2StripeIntent([
            'status' => 'processing',
            'metadata' => (object) [
                'booking_id' => (string) $booking->id,
                'type' => 'rental',
            ],
        ]),
    ]);

    medium2PostCard($this, $booking)
        ->assertRedirect(route('payments.show', $booking))
        ->assertSessionHasErrors('payment');

    medium2AssertNoFinancialMutation($booking, $invoice);
});

test('another user cannot verify a booking payment with a valid PaymentIntent', function () {
    $owner = medium2User();
    $attacker = medium2User();
    $booking = medium2Booking($owner);
    $invoice = medium2Invoice($booking);
    medium2BindStripeGatewayNever();

    $this->actingAs($attacker)
        ->post(route('payments.store', $booking), [
            'payment_method' => 'card',
            'rental_payment_intent' => 'pi_medium2_rental',
        ])
        ->assertForbidden();

    medium2AssertNoFinancialMutation($booking, $invoice);
});

test('valid rental plus deposit PaymentIntents pass and only synchronize held deposit state', function () {
    $booking = medium2Booking(attributes: [
        'security_deposit_amount' => 3000,
        'security_deposit_intent_id' => 'pi_medium2_deposit',
    ]);
    $invoice = medium2Invoice($booking);
    medium2BindStripeGateway([
        'pi_medium2_rental' => medium2StripeIntent([
            'metadata' => (object) [
                'booking_id' => (string) $booking->id,
                'type' => 'rental',
            ],
        ]),
        'pi_medium2_deposit' => medium2StripeIntent([
            'id' => 'pi_medium2_deposit',
            'status' => 'requires_capture',
            'currency' => 'mad',
            'amount' => 300000,
            'amount_received' => 0,
            'amount_capturable' => 300000,
            'metadata' => (object) [
                'booking_id' => (string) $booking->id,
                'type' => 'security_deposit',
            ],
        ]),
    ]);

    medium2PostCard($this, $booking, ['security_deposit_intent' => 'pi_medium2_deposit'])
        ->assertRedirect(route('bookings.success', $booking));

    expect(Payment::count())->toBe(0)
        ->and((float) $invoice->fresh()->paid_amount)->toBe(0.0)
        ->and($booking->fresh()->status)->toBe(Booking::STATUS_PENDING)
        ->and($booking->fresh()->security_deposit_status)->toBe(Booking::SECURITY_DEPOSIT_STATUS_HELD)
        ->and((float) $booking->fresh()->security_deposit_capturable_amount)->toBe(3000.0);
});

test('webhook duplicate synchronization remains idempotent after hardened controller verification', function () {
    $booking = medium2Booking();
    $invoice = medium2Invoice($booking);
    medium2BindStripeGateway([
        'pi_medium2_rental' => medium2StripeIntent([
            'metadata' => (object) [
                'booking_id' => (string) $booking->id,
                'type' => 'rental',
            ],
        ]),
    ]);

    medium2PostCard($this, $booking)->assertRedirect(route('bookings.success', $booking));

    $payload = [
        'id' => 'evt_medium2_controller_then_webhook',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_medium2_rental',
                'object' => 'payment_intent',
                'status' => 'succeeded',
                'currency' => 'mad',
                'amount' => 130000,
                'amount_received' => 130000,
                'metadata' => [
                    'booking_id' => (string) $booking->id,
                    'invoice_id' => (string) $invoice->id,
                    'type' => 'rental',
                ],
            ],
        ],
    ];

    postStripeWebhook($this, $payload)->assertOk();
    postStripeWebhook($this, $payload)->assertOk();

    expect(Payment::where('transaction_id', 'pi_medium2_rental')->count())->toBe(1)
        ->and($booking->refresh()->status)->toBe(Booking::STATUS_CONFIRMED)
        ->and($invoice->refresh()->status)->toBe(Invoice::STATUS_PAID);
});

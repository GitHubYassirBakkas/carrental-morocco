<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payment;
use App\Models\User;
use App\Services\StripePaymentIntentGateway;
use Illuminate\Support\Facades\Mail;

test('fully paid booking payment page does not create a rental payment intent', function () {
    $user = User::factory()->create();

    $location = Location::create([
        'name' => 'Casablanca Downtown',
        'address' => '1 Test Street',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000000',
        'email' => 'casa@example.com',
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
        'price_per_day' => 700,
        'image' => 'cars/test.jpg',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => 0,
    ]);

    $booking = Booking::create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'rental_price_per_day' => 700,
        'insurance_fixed_price' => 0,
        'total_amount' => 1400,
        'status' => 'confirmed',
        'advance_payment_amount' => 1400,
        'advance_payment_status' => 'paid',
        'advance_payment_paid_at' => now(),
        'security_deposit_amount' => 0,
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => 1400,
        'tax_amount' => 0,
        'total_amount' => 1400,
        'status' => 'paid',
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 1400,
        'method' => 'card',
        'type' => 'payment',
        'status' => 'completed',
        'paid_at' => now(),
    ]);

    $this
        ->actingAs($user)
        ->get(route('payments.show', $booking))
        ->assertOk()
        ->assertSee('Rental payment already completed');

    expect($booking->refresh()->rental_payment_intent_id)->toBeNull();
});

test('card payment controller validates stripe intents without finalizing booking state', function () {
    Mail::fake();

    $user = User::factory()->create();

    $location = Location::create([
        'name' => 'Casablanca Downtown',
        'address' => '1 Test Street',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000000',
        'email' => 'casa@example.com',
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
        'image' => 'cars/test.jpg',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => 3000,
    ]);

    $booking = Booking::create([
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
        'security_deposit_amount' => 3000,
        'security_deposit_status' => 'pending',
        'rental_payment_intent_id' => 'pi_rental_test',
        'security_deposit_intent_id' => 'pi_security_test',
    ]);

    Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => 1300,
        'tax_amount' => 0,
        'total_amount' => 1300,
        'status' => 'pending',
    ]);

    $stripe = Mockery::mock(StripePaymentIntentGateway::class);
    $stripe->shouldReceive('retrieve')
        ->once()
        ->with('pi_rental_test')
        ->andReturn((object) [
            'id' => 'pi_rental_test',
            'status' => 'succeeded',
            'currency' => 'mad',
            'amount_received' => 130000,
            'amount' => 130000,
            'metadata' => (object) [
                'booking_id' => (string) $booking->id,
                'type' => 'rental',
            ],
        ]);
    $stripe->shouldReceive('retrieve')
        ->once()
        ->with('pi_security_test')
        ->andReturn((object) [
            'id' => 'pi_security_test',
            'status' => 'requires_capture',
            'currency' => 'mad',
            'amount' => 300000,
            'amount_capturable' => 300000,
            'metadata' => (object) [
                'booking_id' => (string) $booking->id,
                'type' => 'security_deposit',
            ],
        ]);
    $this->app->instance(StripePaymentIntentGateway::class, $stripe);

    $this
        ->withSession(['_token' => 'test-token'])
        ->actingAs($user)
        ->post(route('payments.store', $booking), [
            '_token' => 'test-token',
            'payment_method' => 'card',
            'rental_payment_intent' => 'pi_rental_test',
            'security_deposit_intent' => 'pi_security_test',
        ])
        ->assertRedirect(route('bookings.success', $booking));

    $booking->refresh();

    expect($booking->security_deposit_status)->toBe('held')
        ->and($booking->security_deposit_intent_id)->toBe('pi_security_test')
        ->and((float) $booking->security_deposit_capturable_amount)->toBe(3000.0)
        ->and($booking->status)->toBe('pending')
        ->and($booking->advance_payment_status)->toBe('pending')
        ->and(Payment::where('transaction_id', 'pi_rental_test')->exists())->toBeFalse();

    Mail::assertNothingSent();

    $payload = [
        'id' => 'evt_store_then_webhook',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_rental_test',
                'object' => 'payment_intent',
                'status' => 'succeeded',
                'amount_received' => 130000,
                'metadata' => [
                    'booking_id' => (string) $booking->id,
                    'type' => 'rental',
                ],
            ],
        ],
    ];

    postStripeWebhook($this, $payload)->assertOk();

    expect($booking->refresh()->status)->toBe('confirmed')
        ->and($booking->advance_payment_status)->toBe('paid')
        ->and($booking->invoice->refresh()->status)->toBe('paid')
        ->and(Payment::where('transaction_id', 'pi_rental_test')->count())->toBe(1);
});

test('rental webhook still confirms booking when payment was already recorded', function () {
    $user = User::factory()->create();

    $location = Location::create([
        'name' => 'Casablanca Downtown',
        'address' => '1 Test Street',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000000',
        'email' => 'casa@example.com',
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
        'image' => 'cars/test.jpg',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => 3000,
    ]);

    $booking = Booking::create([
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
        'security_deposit_amount' => 3000,
        'security_deposit_status' => 'pending',
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'subtotal' => 1300,
        'tax_amount' => 0,
        'total_amount' => 1300,
        'status' => 'paid',
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 1300,
        'method' => 'card',
        'type' => 'payment',
        'status' => 'completed',
        'transaction_id' => 'pi_rental_duplicate',
        'paid_at' => now(),
    ]);

    $payload = [
        'id' => 'evt_test',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_rental_duplicate',
                'object' => 'payment_intent',
                'status' => 'succeeded',
                'metadata' => [
                    'booking_id' => (string) $booking->id,
                    'type' => 'rental',
                ],
            ],
        ],
    ];

    postStripeWebhook($this, $payload)->assertOk();

    $booking->refresh();

    expect($booking->status)->toBe('confirmed')
        ->and($booking->advance_payment_status)->toBe('paid');
});

test('rental webhook creates final payment state once across duplicate events', function () {
    $user = User::factory()->create();

    $location = Location::create([
        'name' => 'Casablanca Downtown',
        'address' => '1 Test Street',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000000',
        'email' => 'casa@example.com',
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
        'image' => 'cars/test.jpg',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => 3000,
    ]);

    $booking = Booking::create([
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
        'security_deposit_amount' => 3000,
        'security_deposit_status' => 'pending',
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => 1300,
        'tax_amount' => 0,
        'total_amount' => 1300,
        'status' => 'pending',
    ]);

    $payload = [
        'id' => 'evt_test',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_rental_once',
                'object' => 'payment_intent',
                'status' => 'succeeded',
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

    expect(Payment::where('transaction_id', 'pi_rental_once')->count())->toBe(1)
        ->and($booking->refresh()->status)->toBe('confirmed')
        ->and($booking->advance_payment_status)->toBe('paid')
        ->and($invoice->refresh()->status)->toBe('paid');
});

test('security deposit webhook holds deposit without confirming booking or paying invoice', function () {
    $user = User::factory()->create();

    $location = Location::create([
        'name' => 'Casablanca Downtown',
        'address' => '1 Test Street',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000000',
        'email' => 'casa@example.com',
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
        'image' => 'cars/test.jpg',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => 3000,
    ]);

    $booking = Booking::create([
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
        'security_deposit_amount' => 3000,
        'security_deposit_status' => 'pending',
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => 1300,
        'tax_amount' => 0,
        'total_amount' => 1300,
        'status' => 'pending',
    ]);

    $payload = [
        'id' => 'evt_deposit',
        'type' => 'payment_intent.amount_capturable_updated',
        'data' => [
            'object' => [
                'id' => 'pi_security_hold',
                'object' => 'payment_intent',
                'status' => 'requires_capture',
                'amount' => 300000,
                'metadata' => [
                    'booking_id' => (string) $booking->id,
                    'type' => 'security_deposit',
                ],
            ],
        ],
    ];

    postStripeWebhook($this, $payload)->assertOk();

    $booking->refresh();

    expect($booking->security_deposit_status)->toBe('held')
        ->and($booking->security_deposit_intent_id)->toBe('pi_security_hold')
        ->and((float) $booking->security_deposit_amount)->toBe(3000.0)
        ->and($booking->status)->toBe('pending')
        ->and($booking->advance_payment_status)->toBe('pending')
        ->and($invoice->refresh()->status)->toBe('pending')
        ->and(Payment::count())->toBe(0);
});

test('security deposit capture webhook marks deposit captured once without invoice changes', function () {
    [$booking, $invoice] = makeSecurityDepositWebhookBooking('held');

    $payload = [
        'id' => 'evt_deposit_capture',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_security_capture',
                'object' => 'payment_intent',
                'status' => 'succeeded',
                'amount' => 300000,
                'amount_received' => 300000,
                'metadata' => [
                    'booking_id' => (string) $booking->id,
                    'type' => 'security_deposit',
                ],
            ],
        ],
    ];

    postStripeWebhook($this, $payload)->assertOk();
    postStripeWebhook($this, $payload)->assertOk();

    $booking->refresh();

    expect($booking->security_deposit_status)->toBe('captured')
        ->and((float) $booking->security_deposit_charged_amount)->toBe(3000.0)
        ->and((float) $booking->security_deposit_capturable_amount)->toBe(0.0)
        ->and(Payment::where('transaction_id', 'pi_security_capture')->count())->toBe(0)
        ->and($invoice->refresh()->status)->toBe('pending');
});

test('security deposit canceled webhook releases held deposit without invoice changes', function () {
    [$booking, $invoice] = makeSecurityDepositWebhookBooking('held');

    $payload = [
        'id' => 'evt_deposit_cancel',
        'type' => 'payment_intent.canceled',
        'data' => [
            'object' => [
                'id' => 'pi_security_capture',
                'object' => 'payment_intent',
                'status' => 'canceled',
                'amount' => 300000,
                'amount_capturable' => 0,
                'metadata' => [
                    'booking_id' => (string) $booking->id,
                    'type' => 'security_deposit',
                ],
            ],
        ],
    ];

    postStripeWebhook($this, $payload)->assertOk();
    postStripeWebhook($this, $payload)->assertOk();

    $booking->refresh();

    expect($booking->security_deposit_status)->toBe('refunded')
        ->and((float) $booking->security_deposit_capturable_amount)->toBe(0.0)
        ->and($booking->security_deposit_released_at)->not->toBeNull()
        ->and(Payment::count())->toBe(0)
        ->and($invoice->refresh()->status)->toBe('pending');
});

test('admin booking page repairs pending capturable deposit and shows actions', function () {
    [$booking] = makeSecurityDepositWebhookBooking('pending');
    $booking->update([
        'security_deposit_intent_id' => 'pi_admin_actionable_hold',
        'security_deposit_capturable_amount' => 3000,
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
        'email_verified_at' => now(),
    ]);

    $this
        ->actingAs($admin)
        ->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee('Release Hold')
        ->assertSee('Capture Deposit')
        ->assertDontSee('no actions available');

    expect($booking->refresh()->security_deposit_status)->toBe('held')
        ->and($booking->isSecurityDepositActionable())->toBeTrue();
});

test('admin booking page normalizes legacy released state to refunded', function () {
    [$booking] = makeSecurityDepositWebhookBooking('released');
    $booking->update([
        'security_deposit_capturable_amount' => 0,
    ]);

    $admin = User::factory()->create([
        'role' => 'admin',
        'email_verified_at' => now(),
    ]);

    $this
        ->actingAs($admin)
        ->get(route('admin.bookings.show', $booking))
        ->assertOk()
        ->assertSee('Refunded')
        ->assertDontSee('id="release-security-deposit-button"', false)
        ->assertDontSee('Capture Deposit');

    expect($booking->refresh()->security_deposit_status)->toBe('refunded')
        ->and((float) $booking->security_deposit_capturable_amount)->toBe(0.0)
        ->and($booking->isSecurityDepositActionable())->toBeFalse();
});

function makeSecurityDepositWebhookBooking(string $depositStatus): array
{
    $user = User::factory()->create();

    $location = Location::create([
        'name' => 'Casablanca Downtown',
        'address' => '1 Test Street',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000000',
        'email' => 'casa@example.com',
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
        'image' => 'cars/test.jpg',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => 3000,
    ]);

    $booking = Booking::create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'rental_price_per_day' => 650,
        'insurance_fixed_price' => 0,
        'total_amount' => 1300,
        'status' => 'confirmed',
        'advance_payment_amount' => 1300,
        'advance_payment_status' => 'paid',
        'security_deposit_amount' => 3000,
        'security_deposit_capturable_amount' => 3000,
        'security_deposit_intent_id' => 'pi_security_capture',
        'security_deposit_status' => $depositStatus,
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => 1300,
        'tax_amount' => 0,
        'total_amount' => 1300,
        'status' => 'pending',
    ]);

    return [$booking, $invoice];
}

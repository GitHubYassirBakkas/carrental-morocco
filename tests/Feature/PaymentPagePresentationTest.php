<?php

use App\Http\Controllers\PaymentController;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\User;
use App\Services\StripePaymentIntentGateway;
use Illuminate\Support\Facades\Route;

test('payment page renders polished card and pickup hierarchy without implying deposit is charged', function () {
    [$user, $booking] = paymentPagePresentationBooking();
    paymentPagePresentationMockStripeCreates(2);

    $response = $this
        ->withSession(['locale' => 'en'])
        ->actingAs($user)
        ->get(route('payments.show', $booking));

    $response
        ->assertOk()
        ->assertSee('Credit/Debit Card')
        ->assertSee('Pay securely by card')
        ->assertSee('Pay at Pickup')
        ->assertSee('Pay when you collect the vehicle')
        ->assertSee('Visa')
        ->assertSee('Mastercard')
        ->assertSee('Rental Total')
        ->assertSee('1,100 MAD')
        ->assertSee('Refundable Security Deposit')
        ->assertSee('5,000 MAD refundable authorization hold')
        ->assertSee('Authorization hold')
        ->assertSee('not included in rental total')
        ->assertSee('Amount charged today')
        ->assertSee('Pay 1,100 MAD')
        ->assertSee('A separate 5,000 MAD refundable hold will be authorized on your card.')
        ->assertSee('Secure payment powered by Stripe')
        ->assertSee('Card details are handled securely by Stripe.')
        ->assertSee('grid grid-cols-1 md:grid-cols-2', false)
        ->assertSee('min-w-0', false)
        ->assertSee('break-words', false)
        ->assertSee('id="payment-form"', false)
        ->assertSee('id="payCard"', false)
        ->assertSee('id="payCash"', false)
        ->assertSee('id="cardLabel"', false)
        ->assertSee('id="cashLabel"', false)
        ->assertSee('id="cardForm"', false)
        ->assertSee('id="card-element"', false)
        ->assertSee('id="card-errors"', false)
        ->assertSee('id="submit-button"', false)
        ->assertSee('id="btnText"', false)
        ->assertSee('cardElement.mount(\'#card-element\')', false)
        ->assertSee("form.action = '".route('payments.store', $booking)."'", false)
        ->assertDontSee('Cash on Delivery')
        ->assertDontSee('256-bit SSL encryption')
        ->assertDontSee('upload.wikimedia.org', false)
        ->assertDontSee('Visa.svg', false)
        ->assertDontSee('Mastercard-logo.svg', false)
        ->assertDontSee('Pay Now 1,100 MAD +')
        ->assertDontSee('+ Security Deposit 5,000 MAD')
        ->assertDontSee('6,100 MAD');
});

test('payment page pickup wording and deposit explanation render in supported locales', function (string $locale, array $expected) {
    [$user, $booking] = paymentPagePresentationBooking();
    paymentPagePresentationMockStripeCreates(2);

    $response = $this
        ->withSession(['locale' => $locale])
        ->actingAs($user)
        ->get(route('payments.show', $booking));

    $response
        ->assertOk()
        ->assertSee($expected['pickup'])
        ->assertSee($expected['subtitle'])
        ->assertSee($expected['deposit'])
        ->assertSee($expected['stripe'])
        ->assertDontSee($expected['old_ssl']);
})->with([
    'en' => ['en', [
        'pickup' => 'Pay at Pickup',
        'subtitle' => 'Pay when you collect the vehicle',
        'deposit' => 'A temporary authorization is placed on your card.',
        'stripe' => 'Secure payment powered by Stripe',
        'old_ssl' => '256-bit SSL encryption',
    ]],
    'fr' => ['fr', [
        'pickup' => 'Paiement à la prise en charge',
        'subtitle' => 'Payez lors de la prise en charge du véhicule',
        'deposit' => 'Une autorisation temporaire est placée sur votre carte.',
        'stripe' => 'Paiement sécurisé par Stripe',
        'old_ssl' => 'Chiffrement SSL 256 bits',
    ]],
    'ar' => ['ar', [
        'pickup' => 'الدفع عند استلام السيارة',
        'subtitle' => 'ادفع عند استلام السيارة',
        'deposit' => 'يتم وضع حجز مؤقت على البطاقة.',
        'stripe' => 'دفع آمن عبر Stripe',
        'old_ssl' => 'تشفير SSL 256 بت',
    ]],
]);

test('customer payment route remains bound to the existing controller action', function () {
    $route = Route::getRoutes()->getByName('payments.store');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('bookings/{booking}/payment')
        ->and($route->methods())->toContain('POST')
        ->and($route->getActionName())->toBe(PaymentController::class.'@store');
});

test('payment page rental amount uses invoice balance that already includes tax', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create([
        'location_id' => $location->id,
        'security_deposit_amount' => 3000,
    ]);
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'rental_price_per_day' => 500,
        'total_amount' => 960,
        'discount_amount' => 200,
        'security_deposit_amount' => 3000,
        'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_PENDING,
    ]);
    Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 1000,
        'discount_amount' => 200,
        'tax_amount' => 160,
        'total_amount' => 960,
        'status' => Invoice::STATUS_PENDING,
    ]);

    $payloads = [];
    $stripe = Mockery::mock(StripePaymentIntentGateway::class);
    $stripe->shouldReceive('create')
        ->twice()
        ->andReturnUsing(function (array $payload) use (&$payloads) {
            $payloads[] = $payload;
            $type = $payload['metadata']['type'] ?? 'rental';

            return (object) [
                'id' => $type === 'security_deposit' ? 'pi_tax_deposit' : 'pi_tax_rental',
                'client_secret' => $type === 'security_deposit' ? 'pi_tax_deposit_secret' : 'pi_tax_rental_secret',
                'status' => 'requires_payment_method',
            ];
        });
    app()->instance(StripePaymentIntentGateway::class, $stripe);

    $this->actingAs($user)
        ->get(route('payments.show', $booking))
        ->assertOk();

    $rentalPayload = collect($payloads)->first(fn (array $payload): bool => ($payload['metadata']['type'] ?? null) === 'rental');
    $depositPayload = collect($payloads)->first(fn (array $payload): bool => ($payload['metadata']['type'] ?? null) === 'security_deposit');

    expect($rentalPayload['amount'])->toBe(96000)
        ->and($depositPayload['amount'])->toBe(300000);
});

function paymentPagePresentationBooking(): array
{
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create([
        'location_id' => $location->id,
        'security_deposit_amount' => 5000,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'start_date' => now()->addDays(2),
        'end_date' => now()->addDays(4),
        'rental_price_per_day' => 550,
        'total_amount' => 1100,
        'advance_payment_amount' => 1100,
        'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PENDING,
        'security_deposit_amount' => 5000,
        'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_PENDING,
        'rental_payment_intent_id' => null,
        'security_deposit_intent_id' => null,
    ]);

    Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 1100,
        'tax_amount' => 0,
        'total_amount' => 1100,
        'status' => Invoice::STATUS_PENDING,
    ]);

    return [$user, $booking];
}

function paymentPagePresentationMockStripeCreates(int $times): void
{
    $stripe = Mockery::mock(StripePaymentIntentGateway::class);
    $stripe->shouldReceive('create')
        ->times($times)
        ->andReturnUsing(function (array $payload) {
            $type = $payload['metadata']['type'] ?? 'rental';

            return (object) [
                'id' => $type === 'security_deposit' ? 'pi_presentation_deposit' : 'pi_presentation_rental',
                'client_secret' => $type === 'security_deposit' ? 'pi_presentation_deposit_secret' : 'pi_presentation_rental_secret',
                'status' => 'requires_payment_method',
            ];
        });

    app()->instance(StripePaymentIntentGateway::class, $stripe);
}

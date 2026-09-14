<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payment;
use App\Models\PaymentEventAudit;
use App\Models\Setting;
use App\Models\User;

test('stripe webhook fails without signature when verification is configured', function () {
    $originalEnvironment = app()->environment();
    config(['services.stripe.webhook_secret' => 'whsec_test']);
    app()->detectEnvironment(fn () => 'production');

    try {
        $this->postJson('/stripe/webhook', [
            'id' => 'evt_unsigned_rejected',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_unsigned_rejected',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'metadata' => ['type' => 'rental'],
                ],
            ],
        ])->assertStatus(400);
    } finally {
        app()->detectEnvironment(fn () => $originalEnvironment);
    }

    expect(PaymentEventAudit::where('event_id', 'evt_unsigned_rejected')->exists())->toBeFalse();
});

test('availability ignores terminal bookings and blocks active or reserved overlaps', function () {
    [$car, $location, $user] = hardeningCarFixture();
    $start = now()->addDay();
    $end = now()->addDays(3);

    foreach (['cancelled', 'completed'] as $status) {
        hardeningBooking($user, $car, $location, [
            'status' => $status,
            'start_date' => $start,
            'end_date' => $end,
        ]);

        expect($car->isAvailableForDates($start, $end))->toBeTrue();
        Booking::query()->delete();
    }

    foreach (['pending', 'confirmed', 'active'] as $status) {
        hardeningBooking($user, $car, $location, [
            'status' => $status,
            'start_date' => $start,
            'end_date' => $end,
        ]);

        expect($car->isAvailableForDates($start->copy()->addHour(), $end->copy()->subHour()))->toBeFalse();
        Booking::query()->delete();
    }
});

test('booking store refuses an overlapping reserved booking', function () {
    [$car, $location, $user] = hardeningCarFixture();
    $profile = CustomerProfile::create([
        'user_id' => $user->id,
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'driving_license_number' => 'HARDENING-DL',
        'driving_license_front_path' => "private/customer-documents/{$user->id}/driving-license/front/front.jpg",
        'driving_license_back_path' => "private/customer-documents/{$user->id}/driving-license/back/back.jpg",
        'identity_front_path' => "private/customer-documents/{$user->id}/identity/front/front.jpg",
        'identity_back_path' => "private/customer-documents/{$user->id}/identity/back/back.jpg",
    ]);
    $profile->forceFill([
        'driver_verification_status' => CustomerProfile::STATUS_VERIFIED,
        'driver_verified_at' => now(),
        'driver_verified_by' => User::factory()->create(['role' => 'admin'])->id,
    ])->save();

    $start = now()->addDay();
    $end = now()->addDays(3);

    hardeningBooking($user, $car, $location, [
        'status' => 'pending',
        'start_date' => $start,
        'end_date' => $end,
    ]);

    $this->actingAs($user)
        ->withSession([
            'booking_preview' => [
                'car_id' => $car->id,
                'pickup_location_id' => $location->id,
                'dropoff_location_id' => $location->id,
                'pickup_date' => $start->toDateString(),
                'return_date' => $end->toDateString(),
                'pickup_time' => '10:00',
                'return_time' => '10:00',
                'start_date' => $start->toDateTimeString(),
                'end_date' => $end->toDateTimeString(),
                'days' => 2,
                'car_price' => 500,
                'insurance_id' => null,
                'insurance_price' => 0,
                'rental_amount' => 1000,
                'insurance_amount' => 0,
                'extras_amount' => 0,
                'dropoff_fee' => 0,
                'grand_total' => 1000,
            ],
        ])
        ->post(route('bookings.store'))
        ->assertSessionHasErrors('dates');

    expect(Booking::count())->toBe(1)
        ->and(Invoice::count())->toBe(0);
});

test('scheduler registers and overdue cancellation command cancels pending bookings', function () {
    [$car, $location, $user] = hardeningCarFixture();

    $booking = hardeningBooking($user, $car, $location, [
        'status' => 'pending',
        'advance_payment_status' => 'pending',
        'advance_payment_due_at' => now()->subHour(),
    ]);
    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => 1000,
        'tax_amount' => 0,
        'total_amount' => 1000,
        'status' => Invoice::STATUS_PENDING,
    ]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'amount' => 1000,
        'method' => 'cash',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_PENDING,
        'transaction_id' => null,
        'paid_at' => null,
    ]);

    $exitCode = \Illuminate\Support\Facades\Artisan::call('schedule:list');

    expect($exitCode)->toBe(0)
        ->and(\Illuminate\Support\Facades\Artisan::output())->toContain('bookings:cancel-overdue');

    $this->artisan('bookings:cancel-overdue')->assertExitCode(0);

    expect($booking->refresh()->status)->toBe('cancelled');
});

test('setting set preserves existing type unless explicitly changed', function () {
    Setting::create([
        'key' => 'hardening_number',
        'value' => '24',
        'type' => 'number',
        'group' => 'general',
    ]);

    Setting::set('hardening_number', '48');

    $setting = Setting::where('key', 'hardening_number')->first();

    expect($setting->getRawOriginal('type'))->toBe('number')
        ->and($setting->value)->toBe(48);
});

function hardeningCarFixture(): array
{
    $user = User::factory()->create();

    $location = Location::create([
        'name' => 'Hardening Casablanca',
        'address' => '1 Hardening Street',
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
        'price_per_day' => 500,
        'image' => 'cars/hardening.jpg',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => 1000,
    ]);

    return [$car, $location, $user];
}

function hardeningBooking(User $user, Car $car, Location $location, array $overrides = []): Booking
{
    return Booking::create(array_merge([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'rental_price_per_day' => 500,
        'insurance_fixed_price' => 0,
        'total_amount' => 1000,
        'status' => 'pending',
        'advance_payment_amount' => 1000,
        'advance_payment_status' => 'pending',
        'advance_payment_due_at' => now()->addDay(),
        'security_deposit_amount' => 1000,
        'security_deposit_status' => 'pending',
    ], $overrides));
}

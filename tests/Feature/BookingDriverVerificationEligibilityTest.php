<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\CustomerProfile;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;

function bookingEligibilityLocation(): Location
{
    return Location::create([
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
}

function bookingEligibilityCar(Location $location): Car
{
    return Car::create([
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
}

function bookingEligibilityPreviewPayload(Location $location): array
{
    return [
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'pickup_date' => now()->addDays(2)->toDateString(),
        'pickup_time' => '10:00',
        'return_date' => now()->addDays(4)->toDateString(),
        'return_time' => '10:00',
    ];
}

function bookingEligibilityPreviewSession(Car $car, Location $location): array
{
    $start = now()->addDays(2)->setTime(10, 0);
    $end = now()->addDays(4)->setTime(10, 0);

    return [
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
        'car_price' => 700,
        'insurance_id' => null,
        'insurance_price' => 0,
        'car_total' => 1400,
        'insurance_total' => 0,
        'dropoff_fee' => 0,
        'rental_amount' => 1400,
        'insurance_amount' => 0,
        'protection_plan_amount' => 0,
        'extras_amount' => 0,
        'pricing_breakdown' => [
            'rental_amount' => 1400,
            'insurance_amount' => 0,
            'protection_plan_amount' => 0,
            'extras_amount' => 0,
            'subtotal_amount' => 1400,
            'discount_amount' => 0,
            'total_amount' => 1400,
        ],
        'grand_total' => 1400,
    ];
}

function bookingEligibilityProfile(User $user, string $status, array $overrides = []): CustomerProfile
{
    $profile = CustomerProfile::create(array_merge([
        'user_id' => $user->id,
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'driving_license_number' => 'BOOKING-DL',
        'driving_license_front_path' => "private/customer-documents/{$user->id}/driving-license/front/front.jpg",
        'driving_license_back_path' => "private/customer-documents/{$user->id}/driving-license/back/back.jpg",
        'identity_front_path' => "private/customer-documents/{$user->id}/identity/front/front.jpg",
        'identity_back_path' => "private/customer-documents/{$user->id}/identity/back/back.jpg",
    ], $overrides));

    $profile->forceFill([
        'driver_verification_status' => $status,
        'driver_verification_submitted_at' => $status !== CustomerProfile::STATUS_INCOMPLETE ? now()->subHour() : null,
        'driver_verified_at' => $status === CustomerProfile::STATUS_VERIFIED ? now()->subMinute() : null,
        'driver_verified_by' => $status === CustomerProfile::STATUS_VERIFIED ? User::factory()->create(['role' => 'admin'])->id : null,
        'driver_verification_rejection_reason' => $overrides['driver_verification_rejection_reason'] ?? null,
    ])->save();

    return $profile->fresh();
}

function bookingEligibilityFixture(): array
{
    $location = bookingEligibilityLocation();
    $car = bookingEligibilityCar($location);

    return [$car, $location];
}

test('guest can still browse car details', function () {
    [$car] = bookingEligibilityFixture();

    $this->get(route('cars.details', $car))->assertOk();
});

test('no profile cannot enter new booking pipeline', function (string $method, string $routeName) {
    [$car, $location] = bookingEligibilityFixture();
    $user = User::factory()->create();

    $request = $this->actingAs($user)->withSession([
        'booking_preview' => bookingEligibilityPreviewSession($car, $location),
        'unrelated_session_value' => 'keep-me',
    ]);

    $response = match ($routeName) {
        'bookings.preview' => $request->post(route($routeName, $car), bookingEligibilityPreviewPayload($location)),
        default => $request->{$method}(route($routeName)),
    };

    $response->assertRedirect(route('booking.driver-verification-required'))
        ->assertSessionMissing('booking_preview')
        ->assertSessionHas('unrelated_session_value', 'keep-me');

    expect(Booking::count())->toBe(0);
})->with([
    ['post', 'bookings.preview'],
    ['get', 'bookings.preview.show'],
    ['post', 'bookings.store'],
]);

test('incomplete pending and rejected profiles cannot enter new booking pipeline', function (string $status, string $method, string $routeName) {
    [$car, $location] = bookingEligibilityFixture();
    $user = User::factory()->create();
    $overrides = $status === CustomerProfile::STATUS_INCOMPLETE
        ? ['identity_back_path' => null]
        : [];

    bookingEligibilityProfile($user, $status, $overrides);

    $request = $this->actingAs($user)->withSession([
        'booking_preview' => bookingEligibilityPreviewSession($car, $location),
        'unrelated_session_value' => 'keep-me',
    ]);

    $response = match ($routeName) {
        'bookings.preview' => $request->post(route($routeName, $car), bookingEligibilityPreviewPayload($location)),
        default => $request->{$method}(route($routeName)),
    };

    $response->assertRedirect(route('booking.driver-verification-required'))
        ->assertSessionMissing('booking_preview')
        ->assertSessionHas('unrelated_session_value', 'keep-me');

    expect(Booking::count())->toBe(0);
})->with([
    CustomerProfile::STATUS_INCOMPLETE,
    CustomerProfile::STATUS_PENDING,
    CustomerProfile::STATUS_REJECTED,
])->with([
    ['post', 'bookings.preview'],
    ['get', 'bookings.preview.show'],
    ['post', 'bookings.store'],
]);

test('rejected booking block page shows escaped rejection reason', function () {
    $user = User::factory()->create();
    bookingEligibilityProfile($user, CustomerProfile::STATUS_REJECTED, [
        'driver_verification_rejection_reason' => '<script>alert("x")</script>',
    ]);

    $this->actingAs($user)
        ->get(route('booking.driver-verification-required'))
        ->assertOk()
        ->assertSeeText('Verification needs attention')
        ->assertSeeText('alert("x")')
        ->assertDontSee('<script>alert("x")</script>', false);
});

test('verified customer can use normal booking preview flow', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-18 09:00:00'));

    [$car, $location] = bookingEligibilityFixture();
    $user = User::factory()->create();
    bookingEligibilityProfile($user, CustomerProfile::STATUS_VERIFIED);

    $this->actingAs($user)
        ->post(route('bookings.preview', $car), [
            'pickup_location_id' => $location->id,
            'dropoff_location_id' => $location->id,
            'pickup_date' => '2026-08-20',
            'pickup_time' => '10:00',
            'return_date' => '2026-08-22',
            'return_time' => '10:00',
        ])
        ->assertRedirect(route('bookings.preview.show'))
        ->assertSessionHas('booking_preview.pickup_date', '2026-08-20');

    $this->actingAs($user)
        ->get(route('bookings.preview.show'))
        ->assertOk()
        ->assertSeeText('Toyota');

    Carbon::setTestNow();
});

test('verified customer can create a new booking from preview session', function () {
    [$car, $location] = bookingEligibilityFixture();
    $user = User::factory()->create();
    bookingEligibilityProfile($user, CustomerProfile::STATUS_VERIFIED);

    $response = $this->actingAs($user)
        ->withSession([
            'booking_preview' => bookingEligibilityPreviewSession($car, $location),
        ])
        ->post(route('bookings.store'));

    $booking = Booking::firstOrFail();

    $response->assertRedirect(route('payments.show', $booking));

    expect($booking->user_id)->toBe($user->id)
        ->and($booking->status)->toBe(Booking::STATUS_PENDING);
});

test('payment routes are not newly blocked for an existing unverified booking', function () {
    [$car, $location] = bookingEligibilityFixture();
    $user = User::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'status' => Booking::STATUS_PENDING,
        'total_amount' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('payments.show', $booking))
        ->assertOk();
});

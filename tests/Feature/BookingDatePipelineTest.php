<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\CustomerProfile;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;

function bookingDatePipelineLocation(): Location
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

function bookingDatePipelineCar(Location $location): Car
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

function bookingDatePipelineVerifiedDriver(User $user): CustomerProfile
{
    $profile = CustomerProfile::create([
        'user_id' => $user->id,
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'driving_license_number' => 'DATE-PIPELINE-DL',
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

    return $profile;
}

test('car details date formatter keeps local calendar dates and avoids UTC ISO conversion', function () {
    $source = file_get_contents(resource_path('views/cars/details.blade.php'));

    expect($source)->toContain("fmt(d) { return flatpickr.formatDate(d, 'Y-m-d'); }")
        ->and($source)->toContain('localDate(value)')
        ->and($source)->not->toContain("toISOString().split('T')[0]")
        ->and($source)->not->toContain('new Date(this.returnDate)')
        ->and($source)->not->toContain('new Date(this.pickupDate)');
});

test('booking preview stores selected 20 Aug to 22 Aug dates without shifting', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-18 09:00:00'));

    $user = User::factory()->create();
    $location = bookingDatePipelineLocation();
    $car = bookingDatePipelineCar($location);
    bookingDatePipelineVerifiedDriver($user);

    $response = $this->actingAs($user)->post(route('bookings.preview', $car), [
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'pickup_date' => '2026-08-20',
        'pickup_time' => '10:00',
        'return_date' => '2026-08-22',
        'return_time' => '10:00',
    ]);

    $response->assertRedirect(route('bookings.preview.show'));
    $response->assertSessionHas('booking_preview.pickup_date', '2026-08-20');
    $response->assertSessionHas('booking_preview.return_date', '2026-08-22');
    $response->assertSessionHas('booking_preview.start_date', '2026-08-20 10:00:00');
    $response->assertSessionHas('booking_preview.end_date', '2026-08-22 10:00:00');
    $response->assertSessionHas('booking_preview.days', 2);

    Carbon::setTestNow();
});

test('booking preview allows pickup dates up to the configured max advance boundary', function (int $daysAhead) {
    Carbon::setTestNow(Carbon::parse('2026-01-15 15:30:00'));

    $user = User::factory()->create();
    $location = bookingDatePipelineLocation();
    $car = bookingDatePipelineCar($location);
    bookingDatePipelineVerifiedDriver($user);

    $pickup = now()->copy()->addDays($daysAhead);
    $return = $pickup->copy()->addDays(2);

    $response = $this->actingAs($user)->post(route('bookings.preview', $car), [
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'pickup_date' => $pickup->toDateString(),
        'pickup_time' => '10:00',
        'return_date' => $return->toDateString(),
        'return_time' => '10:00',
    ]);

    $response->assertRedirect(route('bookings.preview.show'));
    $response->assertSessionHasNoErrors();

    Carbon::setTestNow();
})->with([89, 90]);

test('booking preview rejects pickup dates beyond the configured max advance boundary', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-15 15:30:00'));

    $user = User::factory()->create();
    $location = bookingDatePipelineLocation();
    $car = bookingDatePipelineCar($location);
    bookingDatePipelineVerifiedDriver($user);

    $pickup = now()->copy()->addDays(91);
    $return = $pickup->copy()->addDays(2);

    expect($pickup->diffInDays(now()))->toBeLessThan(0);

    $response = $this->actingAs($user)->post(route('bookings.preview', $car), [
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'pickup_date' => $pickup->toDateString(),
        'pickup_time' => '10:00',
        'return_date' => $return->toDateString(),
        'return_time' => '10:00',
    ]);

    $response->assertSessionHasErrors('dates');
    $response->assertSessionMissing('booking_preview');

    Carbon::setTestNow();
});

test('booking preview preserves existing past date validation behavior', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-15 15:30:00'));

    $user = User::factory()->create();
    $location = bookingDatePipelineLocation();
    $car = bookingDatePipelineCar($location);
    bookingDatePipelineVerifiedDriver($user);

    $response = $this->actingAs($user)->post(route('bookings.preview', $car), [
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'pickup_date' => now()->copy()->subDay()->toDateString(),
        'pickup_time' => '10:00',
        'return_date' => now()->copy()->addDay()->toDateString(),
        'return_time' => '10:00',
    ]);

    $response->assertSessionHasErrors('pickup_date');

    Carbon::setTestNow();
});

test('booking store rejects stale session dates beyond the max advance boundary', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-15 15:30:00'));

    $user = User::factory()->create();
    $location = bookingDatePipelineLocation();
    $car = bookingDatePipelineCar($location);
    bookingDatePipelineVerifiedDriver($user);

    $pickup = now()->copy()->addDays(91)->setTime(10, 0);
    $return = $pickup->copy()->addDays(2);

    $this->actingAs($user)->withSession([
        'booking_preview' => [
            'car_id' => $car->id,
            'pickup_location_id' => $location->id,
            'dropoff_location_id' => $location->id,
            'pickup_date' => $pickup->toDateString(),
            'return_date' => $return->toDateString(),
            'pickup_time' => $pickup->format('H:i'),
            'return_time' => $return->format('H:i'),
            'start_date' => $pickup->toDateTimeString(),
            'end_date' => $return->toDateTimeString(),
            'days' => 2,
            'car_price' => $car->price_per_day,
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
        ],
    ]);

    $response = $this->post(route('bookings.store'));

    $response->assertRedirect(route('cars.details', $car));
    $response->assertSessionHasErrors('dates');

    expect(Booking::count())->toBe(0);

    Carbon::setTestNow();
});

<?php

use App\Models\Booking;
use App\Models\BookingInspection;
use App\Models\CustomerProfile;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Support\Facades\Storage;

function rentalStartDriverProfile(User $user, string $status): CustomerProfile
{
    $paths = [
        'driving_license_front_path' => "private/customer-documents/{$user->id}/driving-license/front/front.jpg",
        'driving_license_back_path' => "private/customer-documents/{$user->id}/driving-license/back/back.jpg",
        'identity_front_path' => "private/customer-documents/{$user->id}/identity/front/front.jpg",
        'identity_back_path' => "private/customer-documents/{$user->id}/identity/back/back.jpg",
    ];

    foreach ($paths as $path) {
        Storage::disk('local')->put($path, 'document');
    }

    $profile = CustomerProfile::create(array_merge([
        'user_id' => $user->id,
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'driving_license_number' => 'START-DL',
        'driving_license_country' => 'Morocco',
        'driving_license_issue_date' => now()->subYears(5)->toDateString(),
        'driving_license_expiry_date' => now()->addYears(5)->toDateString(),
    ], $paths));

    $profile->forceFill([
        'driver_verification_status' => $status,
        'driver_verification_submitted_at' => now()->subHour(),
        'driver_verified_at' => $status === CustomerProfile::STATUS_VERIFIED ? now()->subMinute() : null,
        'driver_verified_by' => $status === CustomerProfile::STATUS_VERIFIED ? User::factory()->create(['role' => 'admin'])->id : null,
    ])->save();

    return $profile->fresh();
}

function rentalStartBookingFor(User $user, bool $withCheckin = true): Booking
{
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'status' => Booking::STATUS_CONFIRMED,
    ]);

    if ($withCheckin) {
        BookingInspection::factory()->create([
            'booking_id' => $booking->id,
            'type' => 'checkin',
        ]);
    }

    return $booking->fresh();
}

test('rental cannot start with missing driver profile', function () {
    $user = User::factory()->create();
    $booking = rentalStartBookingFor($user);

    app(BookingService::class)->startRental($booking);
})->throws(DomainException::class, 'Driver verification must be completed before starting the rental.');

test('rental cannot start when driver profile is incomplete', function () {
    $user = User::factory()->create();
    CustomerProfile::create(['user_id' => $user->id]);
    $booking = rentalStartBookingFor($user);

    app(BookingService::class)->startRental($booking);
})->throws(DomainException::class, 'Driver verification must be completed before starting the rental.');

test('rental cannot start when driver verification is pending', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    rentalStartDriverProfile($user, CustomerProfile::STATUS_PENDING);
    $booking = rentalStartBookingFor($user);

    app(BookingService::class)->startRental($booking);
})->throws(DomainException::class, 'Driver verification must be completed before starting the rental.');

test('rental cannot start when driver verification is rejected', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    rentalStartDriverProfile($user, CustomerProfile::STATUS_REJECTED);
    $booking = rentalStartBookingFor($user);

    app(BookingService::class)->startRental($booking);
})->throws(DomainException::class, 'Driver verification must be completed before starting the rental.');

test('rental can start when driver profile is verified and check-in exists', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    rentalStartDriverProfile($user, CustomerProfile::STATUS_VERIFIED);
    $booking = rentalStartBookingFor($user);

    $started = app(BookingService::class)->startRental($booking);

    expect($started->refresh()->status)->toBe(Booking::STATUS_ACTIVE);
});

test('check-in inspection is still required for verified drivers', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    rentalStartDriverProfile($user, CustomerProfile::STATUS_VERIFIED);
    $booking = rentalStartBookingFor($user, withCheckin: false);

    app(BookingService::class)->startRental($booking);
})->throws(Exception::class, 'Check-in inspection required before starting rental');

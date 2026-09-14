<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Location;
use App\Models\User;

test('customer booking list separates active bookings from cancelled history', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $bookingAttributes = [
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'start_date' => now()->addDays(3),
        'end_date' => now()->addDays(5),
    ];

    $pending = Booking::factory()->create(array_merge($bookingAttributes, [
        'status' => Booking::STATUS_PENDING,
        'start_date' => now()->addDays(3),
        'end_date' => now()->addDays(5),
    ]));
    $confirmed = Booking::factory()->create(array_merge($bookingAttributes, [
        'status' => Booking::STATUS_CONFIRMED,
        'start_date' => now()->addDays(6),
        'end_date' => now()->addDays(8),
    ]));
    $active = Booking::factory()->create(array_merge($bookingAttributes, [
        'status' => Booking::STATUS_ACTIVE,
        'start_date' => now()->addDays(9),
        'end_date' => now()->addDays(11),
    ]));
    $cancelled = Booking::factory()->create(array_merge($bookingAttributes, [
        'status' => Booking::STATUS_CANCELLED,
        'start_date' => now()->addDays(12),
        'end_date' => now()->addDays(14),
    ]));
    $otherCancelled = Booking::factory()->create(array_merge($bookingAttributes, [
        'user_id' => $otherUser->id,
        'status' => Booking::STATUS_CANCELLED,
        'start_date' => now()->addDays(15),
        'end_date' => now()->addDays(17),
    ]));

    $response = $this->actingAs($user)
        ->get(route('my_booking.index'))
        ->assertOk()
        ->assertViewIs('my_booking.index');

    $upcomingIds = $response->viewData('upcoming')->pluck('id')->all();
    $pastIds = $response->viewData('past')->pluck('id')->all();

    expect($upcomingIds)
        ->toContain($pending->id)
        ->toContain($confirmed->id)
        ->toContain($active->id)
        ->not->toContain($cancelled->id)
        ->not->toContain($otherCancelled->id);

    expect($pastIds)
        ->toContain($cancelled->id)
        ->not->toContain($pending->id)
        ->not->toContain($confirmed->id)
        ->not->toContain($active->id)
        ->not->toContain($otherCancelled->id);
});

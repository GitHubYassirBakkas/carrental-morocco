<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Location;
use App\Models\Payment;
use App\Models\User;

function paymentFlowLocation(): Location
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

function paymentFlowBooking(): Booking
{
    $user = User::factory()->create();
    $location = paymentFlowLocation();
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
        'image' => 'cars/test.jpg',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => 0,
    ]);

    return Booking::create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'start_date' => now()->addDays(2),
        'end_date' => now()->addDays(4),
        'rental_price_per_day' => 500,
        'insurance_fixed_price' => 0,
        'total_amount' => 1000,
        'status' => Booking::STATUS_PENDING,
        'advance_payment_amount' => 300,
        'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PENDING,
        'security_deposit_amount' => 0,
    ]);
}

test('cash booking remains pending until admin records payment then becomes confirmed', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $booking = paymentFlowBooking();

    expect($booking->status)->toBe(Booking::STATUS_PENDING);

    $this->actingAs($admin)
        ->from(route('admin.bookings.index'))
        ->post(route('admin.payments.cash', $booking))
        ->assertRedirect(route('admin.bookings.index'));

    $booking->refresh();

    expect($booking->status)->toBe(Booking::STATUS_CONFIRMED)
        ->and($booking->advance_payment_status)->toBe(Booking::ADVANCE_PAYMENT_STATUS_PAID)
        ->and($booking->invoice->payments()->where('method', 'cash')->where('status', Payment::STATUS_COMPLETED)->exists())->toBeTrue();
});

<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;

function couponFinalLocation(): Location
{
    return Location::create([
        'name' => 'Casablanca Coupon Final',
        'address' => '1 Test Street',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000000',
        'email' => 'coupon-final@example.com',
        'opening_time' => '08:00',
        'closing_time' => '20:00',
        'is_active' => true,
    ]);
}

function couponFinalCar(?Location $location = null, array $overrides = []): Car
{
    $location ??= couponFinalLocation();

    return Car::create(array_merge([
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
        'security_deposit_amount' => 3000,
    ], $overrides));
}

function couponFinalVerifiedDriver(User $user): void
{
    $profile = CustomerProfile::create([
        'user_id' => $user->id,
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'driving_license_number' => 'FINAL-DL',
        'driving_license_front_path' => "private/customer-documents/{$user->id}/driving-license/front/front.jpg",
        'driving_license_back_path' => "private/customer-documents/{$user->id}/driving-license/back/back.jpg",
        'identity_front_path' => "private/customer-documents/{$user->id}/identity/front/front.jpg",
        'identity_back_path' => "private/customer-documents/{$user->id}/identity/back/back.jpg",
    ]);

    $profile->forceFill([
        'driver_verification_status' => CustomerProfile::STATUS_VERIFIED,
        'driver_verification_submitted_at' => now()->subDay(),
        'driver_verified_at' => now()->subHour(),
        'driver_verified_by' => User::factory()->create(['role' => 'admin'])->id,
    ])->save();
}

function couponFinalPreviewSession(Car $car, Location $location, int $startOffsetDays = 2): array
{
    $start = now()->addDays($startOffsetDays)->setTime(10, 0);
    $end = now()->addDays($startOffsetDays + 2)->setTime(10, 0);

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
        'car_price' => 500,
        'insurance_id' => null,
        'insurance_price' => 0,
        'car_total' => 1000,
        'insurance_total' => 0,
        'dropoff_fee' => 0,
        'rental_amount' => 1000,
        'insurance_amount' => 0,
        'protection_plan_amount' => 0,
        'extras_amount' => 0,
        'pricing_breakdown' => [
            'rental_amount' => 1000,
            'insurance_amount' => 0,
            'protection_plan_amount' => 0,
            'extras_amount' => 0,
            'subtotal_amount' => 1000,
            'discount_amount' => 0,
            'total_amount' => 1000,
        ],
        'grand_total' => 1000,
    ];
}

function couponFinalCoupon(array $overrides = []): Coupon
{
    return Coupon::create(array_merge([
        'code' => 'FINAL-'.strtoupper(str()->random(8)),
        'category' => 'seasonal',
        'discount_type' => 'percentage',
        'discount_value' => 50,
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addDays(10),
        'max_uses' => 1,
        'max_uses_per_user' => 1,
        'is_active' => true,
        'description' => 'Final validation coupon',
    ], $overrides));
}

function couponFinalAppliedSession(Car $car, Location $location, Coupon $coupon, int $startOffsetDays = 2): array
{
    return [
        'booking_preview' => couponFinalPreviewSession($car, $location, $startOffsetDays),
        'applied_coupon' => [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
        ],
        'coupon_discount' => 9999.0,
        'final_total' => 1.0,
        'unrelated_session_value' => 'keep-me',
    ];
}

function couponFinalAssertRejected($test, User $user, array $session, Coupon $coupon): void
{
    $test->actingAs($user)
        ->withSession($session)
        ->post(route('bookings.store'))
        ->assertRedirect(route('bookings.preview.show'))
        ->assertSessionHasErrors('coupon_code')
        ->assertSessionHas('booking_preview')
        ->assertSessionHas('unrelated_session_value', 'keep-me')
        ->assertSessionMissing('applied_coupon')
        ->assertSessionMissing('coupon_discount')
        ->assertSessionMissing('final_total');

    expect(Booking::count())->toBe(0)
        ->and(Invoice::count())->toBe(0)
        ->and(CouponUsage::count())->toBe(0)
        ->and($coupon->fresh()->used_count)->toBe((int) $coupon->used_count);
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-08-31 10:00:00'));
});

test('coupon valid at preview but expired before store does not create discounted booking', function () {
    $user = User::factory()->create();
    couponFinalVerifiedDriver($user);
    $location = couponFinalLocation();
    $car = couponFinalCar($location);
    $coupon = couponFinalCoupon();
    $session = couponFinalAppliedSession($car, $location, $coupon);

    $coupon->update(['valid_until' => now()->subDay()]);

    couponFinalAssertRejected($this, $user, $session, $coupon);
});

test('coupon valid at preview but deactivated before store does not create discounted booking', function () {
    $user = User::factory()->create();
    couponFinalVerifiedDriver($user);
    $location = couponFinalLocation();
    $car = couponFinalCar($location);
    $coupon = couponFinalCoupon();
    $session = couponFinalAppliedSession($car, $location, $coupon);

    $coupon->update(['is_active' => false]);

    couponFinalAssertRejected($this, $user, $session, $coupon);
});

test('coupon valid at preview but max uses reached before store does not create discounted booking', function () {
    $user = User::factory()->create();
    couponFinalVerifiedDriver($user);
    $location = couponFinalLocation();
    $car = couponFinalCar($location);
    $coupon = couponFinalCoupon();
    $session = couponFinalAppliedSession($car, $location, $coupon);

    $coupon->update(['used_count' => 1]);

    couponFinalAssertRejected($this, $user, $session, $coupon);
});

test('coupon valid at preview but reassigned to another user before store does not create discounted booking', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    couponFinalVerifiedDriver($user);
    $location = couponFinalLocation();
    $car = couponFinalCar($location);
    $coupon = couponFinalCoupon(['user_id' => $user->id]);
    $session = couponFinalAppliedSession($car, $location, $coupon);

    $coupon->update(['user_id' => $otherUser->id]);

    couponFinalAssertRejected($this, $user, $session, $coupon);
});

test('valid percentage coupon is recalculated at store and persisted atomically', function () {
    $user = User::factory()->create();
    couponFinalVerifiedDriver($user);
    $location = couponFinalLocation();
    $car = couponFinalCar($location);
    $coupon = couponFinalCoupon([
        'discount_type' => 'percentage',
        'discount_value' => 20,
    ]);

    $response = $this->actingAs($user)
        ->withSession(couponFinalAppliedSession($car, $location, $coupon))
        ->post(route('bookings.store'));

    $booking = Booking::firstOrFail();

    $response->assertRedirect(route('payments.show', $booking));

    expect($booking->total_amount)->toEqual('800.00')
        ->and($booking->discount_amount)->toEqual('200.00')
        ->and($booking->security_deposit_amount)->toEqual('3000.00')
        ->and($booking->invoice->subtotal)->toEqual('1000.00')
        ->and($booking->invoice->discount_amount)->toEqual('200.00')
        ->and($booking->invoice->total_amount)->toEqual('800.00')
        ->and(CouponUsage::where('coupon_id', $coupon->id)->where('booking_id', $booking->id)->count())->toBe(1)
        ->and($coupon->fresh()->used_count)->toBe(1);
});

test('valid fixed coupon is recalculated at store and persisted atomically', function () {
    $user = User::factory()->create();
    couponFinalVerifiedDriver($user);
    $location = couponFinalLocation();
    $car = couponFinalCar($location);
    $coupon = couponFinalCoupon([
        'discount_type' => 'fixed',
        'discount_value' => 150,
    ]);

    $response = $this->actingAs($user)
        ->withSession(couponFinalAppliedSession($car, $location, $coupon))
        ->post(route('bookings.store'));

    $booking = Booking::firstOrFail();

    $response->assertRedirect(route('payments.show', $booking));

    expect($booking->total_amount)->toEqual('850.00')
        ->and($booking->discount_amount)->toEqual('150.00')
        ->and($booking->invoice->subtotal)->toEqual('1000.00')
        ->and($booking->invoice->discount_amount)->toEqual('150.00')
        ->and($booking->invoice->total_amount)->toEqual('850.00')
        ->and(CouponUsage::where('coupon_id', $coupon->id)->where('booking_id', $booking->id)->count())->toBe(1)
        ->and($coupon->fresh()->used_count)->toBe(1);
});

test('coupon preview stores canonical tax breakdown after discount', function () {
    Setting::set('tax_percentage', 20, 'number');

    $user = User::factory()->create();
    couponFinalVerifiedDriver($user);
    $location = couponFinalLocation();
    $car = couponFinalCar($location);
    $coupon = couponFinalCoupon([
        'discount_type' => 'fixed',
        'discount_value' => 200,
    ]);

    $this->actingAs($user)
        ->withSession(['booking_preview' => couponFinalPreviewSession($car, $location)])
        ->post(route('coupons.apply'), ['coupon_code' => $coupon->code])
        ->assertRedirect()
        ->assertSessionHas('coupon_discount', 200.0)
        ->assertSessionHas('final_total', 960.0)
        ->assertSessionHas('final_pricing_breakdown.tax_amount', 160.0)
        ->assertSessionHas('final_pricing_breakdown.total_amount', 960.0);
});

test('coupon plus non zero tax discounts before tax when booking is stored', function () {
    Setting::set('tax_percentage', 20, 'number');

    $user = User::factory()->create();
    couponFinalVerifiedDriver($user);
    $location = couponFinalLocation();
    $car = couponFinalCar($location);
    $coupon = couponFinalCoupon([
        'discount_type' => 'fixed',
        'discount_value' => 200,
    ]);

    $response = $this->actingAs($user)
        ->withSession(couponFinalAppliedSession($car, $location, $coupon))
        ->post(route('bookings.store'));

    $booking = Booking::with('invoice')->firstOrFail();

    $response->assertRedirect(route('payments.show', $booking));

    expect((float) $booking->discount_amount)->toBe(200.0)
        ->and((float) $booking->total_amount)->toBe(960.0)
        ->and((float) $booking->invoice->subtotal)->toBe(1000.0)
        ->and((float) $booking->invoice->discount_amount)->toBe(200.0)
        ->and((float) $booking->invoice->tax_amount)->toBe(160.0)
        ->and((float) $booking->invoice->total_amount)->toBe(960.0);
});

test('loyalty coupon recipient can book but another user cannot persist stale discount', function () {
    $recipient = User::factory()->create();
    $otherUser = User::factory()->create();
    couponFinalVerifiedDriver($recipient);
    couponFinalVerifiedDriver($otherUser);
    $location = couponFinalLocation();
    $car = couponFinalCar($location);
    $coupon = couponFinalCoupon([
        'code' => 'LOYALTY-FINAL',
        'category' => 'loyalty',
        'user_id' => $recipient->id,
        'discount_type' => 'percentage',
        'discount_value' => 50,
        'valid_until' => now()->addDays(60),
    ]);

    $this->actingAs($otherUser)
        ->withSession(couponFinalAppliedSession($car, $location, $coupon))
        ->post(route('bookings.store'))
        ->assertRedirect(route('bookings.preview.show'))
        ->assertSessionHasErrors('coupon_code');

    expect(Booking::count())->toBe(0)
        ->and(Invoice::count())->toBe(0)
        ->and(CouponUsage::count())->toBe(0);

    $response = $this->actingAs($recipient)
        ->withSession(couponFinalAppliedSession($car, $location, $coupon))
        ->post(route('bookings.store'));

    $booking = Booking::firstOrFail();

    $response->assertRedirect(route('payments.show', $booking));

    expect($booking->total_amount)->toEqual('500.00')
        ->and($booking->discount_amount)->toEqual('500.00')
        ->and(CouponUsage::where('coupon_id', $coupon->id)->where('booking_id', $booking->id)->count())->toBe(1)
        ->and($coupon->fresh()->used_count)->toBe(1);
});

test('one use coupon cannot be consumed by a stale second booking attempt', function () {
    $user = User::factory()->create();
    couponFinalVerifiedDriver($user);
    $location = couponFinalLocation();
    $firstCar = couponFinalCar($location);
    $secondCar = couponFinalCar($location);
    $coupon = couponFinalCoupon(['max_uses' => 1]);

    $this->actingAs($user)
        ->withSession(couponFinalAppliedSession($firstCar, $location, $coupon, 2))
        ->post(route('bookings.store'))
        ->assertRedirect();

    $this->actingAs($user)
        ->withSession(couponFinalAppliedSession($secondCar, $location, $coupon, 5))
        ->post(route('bookings.store'))
        ->assertRedirect(route('bookings.preview.show'))
        ->assertSessionHasErrors('coupon_code');

    expect(Booking::count())->toBe(1)
        ->and(Invoice::count())->toBe(1)
        ->and(CouponUsage::where('coupon_id', $coupon->id)->count())->toBe(1)
        ->and($coupon->fresh()->used_count)->toBe(1);
});

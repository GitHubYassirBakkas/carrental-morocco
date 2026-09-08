<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Carbon;

function medium3Location(): Location
{
    return Location::create([
        'name' => 'Casablanca Coupon Types',
        'address' => '1 Test Street',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000000',
        'email' => 'coupon-types@example.com',
        'opening_time' => '08:00',
        'closing_time' => '20:00',
        'is_active' => true,
    ]);
}

function medium3Car(?Location $location = null, string $type = 'Economy'): Car
{
    $location ??= medium3Location();

    return Car::create([
        'brand' => 'Toyota',
        'model' => $type.' Test',
        'year' => 2024,
        'type' => $type,
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
}

function medium3Coupon(array $overrides = []): Coupon
{
    return Coupon::create(array_merge([
        'code' => 'TYPE-'.strtoupper(str()->random(8)),
        'category' => 'seasonal',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addDays(10),
        'max_uses' => null,
        'max_uses_per_user' => 1,
        'allowed_car_types' => null,
        'is_active' => true,
        'description' => 'Vehicle type validation coupon',
    ], $overrides));
}

function medium3PreviewSession(Car $car, Location $location, int $startOffsetDays = 2): array
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

function medium3AppliedSession(Car $car, Location $location, Coupon $coupon): array
{
    return [
        'booking_preview' => medium3PreviewSession($car, $location),
        'applied_coupon' => [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
        ],
        'coupon_discount' => 9999.0,
        'final_total' => 1.0,
    ];
}

function medium3VerifiedDriver(User $user): void
{
    $profile = CustomerProfile::create([
        'user_id' => $user->id,
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'driving_license_number' => 'TYPE-DL',
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

function medium3PostCoupon(mixed $test, User $user, Car $car, Location $location, Coupon $coupon, array $extraSession = []): Illuminate\Testing\TestResponse
{
    return $test->actingAs($user)
        ->withSession(array_merge(['booking_preview' => medium3PreviewSession($car, $location)], $extraSession))
        ->post(route('coupons.apply'), [
            'coupon_code' => $coupon->code,
            'car_type' => 'Economy',
        ]);
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00'));
});

test('unrestricted coupon works for Economy and Luxury car types', function () {
    $coupon = medium3Coupon(['allowed_car_types' => []]);

    expect($coupon->canBeUsed(User::factory()->create()->id, 1000, 'Economy')['valid'])->toBeTrue()
        ->and($coupon->canBeUsed(User::factory()->create()->id, 1000, 'Luxury')['valid'])->toBeTrue();
});

test('coupon restricted to Economy works for Economy and fails for Luxury', function () {
    $coupon = medium3Coupon(['allowed_car_types' => ['economy']]);

    expect($coupon->canBeUsed(User::factory()->create()->id, 1000, 'Economy')['valid'])->toBeTrue();

    $validation = $coupon->canBeUsed(User::factory()->create()->id, 1000, 'Luxury');

    expect($validation['valid'])->toBeFalse()
        ->and($validation['message'])->toBe('This coupon is not valid for the selected vehicle type.');
});

test('coupon restricted to multiple types works only for listed car types', function () {
    $coupon = medium3Coupon(['allowed_car_types' => ['economy', ' luxury ']]);

    expect($coupon->canBeUsed(User::factory()->create()->id, 1000, 'Economy')['valid'])->toBeTrue()
        ->and($coupon->canBeUsed(User::factory()->create()->id, 1000, 'Luxury')['valid'])->toBeTrue()
        ->and($coupon->canBeUsed(User::factory()->create()->id, 1000, 'Sedan')['valid'])->toBeFalse();
});

test('null and empty allowed car types mean unrestricted', function () {
    $nullCoupon = medium3Coupon(['allowed_car_types' => null]);
    $emptyCoupon = medium3Coupon(['allowed_car_types' => []]);

    expect($nullCoupon->canBeUsed(User::factory()->create()->id, 1000, 'Luxury')['valid'])->toBeTrue()
        ->and($emptyCoupon->canBeUsed(User::factory()->create()->id, 1000, 'Luxury')['valid'])->toBeTrue();
});

test('preview coupon apply endpoint rejects incompatible authoritative car type', function () {
    $user = User::factory()->create();
    $location = medium3Location();
    $luxury = medium3Car($location, 'Luxury');
    $coupon = medium3Coupon(['allowed_car_types' => ['economy']]);

    medium3PostCoupon($this, $user, $luxury, $location, $coupon)
        ->assertRedirect()
        ->assertSessionHasErrors('coupon_code')
        ->assertSessionMissing('applied_coupon')
        ->assertSessionMissing('coupon_discount');
});

test('preview coupon apply endpoint accepts compatible authoritative car type despite fake request car type', function () {
    $user = User::factory()->create();
    $location = medium3Location();
    $luxury = medium3Car($location, 'Luxury');
    $coupon = medium3Coupon(['allowed_car_types' => ['luxury']]);

    medium3PostCoupon($this, $user, $luxury, $location, $coupon)
        ->assertRedirect()
        ->assertSessionHas('applied_coupon.id', $coupon->id)
        ->assertSessionHas('coupon_discount', 200.0);
});

test('final booking store rejects incompatible restricted coupon from stale session', function () {
    $user = User::factory()->create();
    medium3VerifiedDriver($user);
    $location = medium3Location();
    $luxury = medium3Car($location, 'Luxury');
    $coupon = medium3Coupon(['allowed_car_types' => ['economy']]);

    $this->actingAs($user)
        ->withSession(medium3AppliedSession($luxury, $location, $coupon))
        ->post(route('bookings.store'))
        ->assertRedirect(route('bookings.preview.show'))
        ->assertSessionHasErrors('coupon_code')
        ->assertSessionMissing('applied_coupon')
        ->assertSessionMissing('coupon_discount')
        ->assertSessionMissing('final_total');

    expect(Booking::count())->toBe(0)
        ->and(Invoice::count())->toBe(0)
        ->and(CouponUsage::count())->toBe(0)
        ->and($coupon->fresh()->used_count)->toBe(0);
});

test('compatible final booking stores recalculated discount and coupon usage', function () {
    $user = User::factory()->create();
    medium3VerifiedDriver($user);
    $location = medium3Location();
    $economy = medium3Car($location, 'Economy');
    $coupon = medium3Coupon(['allowed_car_types' => ['economy']]);

    $response = $this->actingAs($user)
        ->withSession(medium3AppliedSession($economy, $location, $coupon))
        ->post(route('bookings.store'));

    $booking = Booking::firstOrFail();

    $response->assertRedirect(route('payments.show', $booking));

    expect($booking->total_amount)->toEqual('800.00')
        ->and($booking->discount_amount)->toEqual('200.00')
        ->and($booking->invoice->subtotal)->toEqual('1000.00')
        ->and($booking->invoice->discount_amount)->toEqual('200.00')
        ->and(CouponUsage::where('coupon_id', $coupon->id)->where('booking_id', $booking->id)->exists())->toBeTrue()
        ->and($coupon->fresh()->used_count)->toBe(1);
});

test('Coupon apply cannot bypass allowed car type restriction', function () {
    $user = User::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'total_amount' => 1000,
    ]);
    $coupon = medium3Coupon(['allowed_car_types' => ['economy']]);

    $result = $coupon->apply($user->id, $booking->id, 1000, 'Luxury');

    expect($result['valid'])->toBeFalse()
        ->and(CouponUsage::count())->toBe(0)
        ->and($coupon->fresh()->used_count)->toBe(0);
});

test('recipient only loyalty coupon remains usable on unrestricted car type', function () {
    $user = User::factory()->create();
    medium3VerifiedDriver($user);
    $location = medium3Location();
    $luxury = medium3Car($location, 'Luxury');
    $coupon = medium3Coupon([
        'code' => 'LOYALTY-TYPES',
        'category' => 'loyalty',
        'user_id' => $user->id,
        'discount_type' => 'percentage',
        'discount_value' => 50,
        'valid_until' => now()->addDays(60),
        'max_uses' => 1,
        'allowed_car_types' => null,
    ]);

    $response = $this->actingAs($user)
        ->withSession(medium3AppliedSession($luxury, $location, $coupon))
        ->post(route('bookings.store'));

    $booking = Booking::firstOrFail();

    $response->assertRedirect(route('payments.show', $booking));

    expect($booking->total_amount)->toEqual('500.00')
        ->and($booking->discount_amount)->toEqual('500.00')
        ->and(CouponUsage::where('coupon_id', $coupon->id)->where('booking_id', $booking->id)->exists())->toBeTrue()
        ->and($coupon->fresh()->used_count)->toBe(1);
});

test('existing expiry max use and per user restrictions still apply with car type context', function () {
    $user = User::factory()->create();
    $location = medium3Location();
    $economy = medium3Car($location, 'Economy');
    $expired = medium3Coupon([
        'code' => 'TYPE-EXPIRED',
        'allowed_car_types' => ['economy'],
        'valid_until' => now()->subDay(),
    ]);
    $used = medium3Coupon([
        'code' => 'TYPE-USED',
        'allowed_car_types' => ['economy'],
        'max_uses' => 1,
        'used_count' => 1,
    ]);
    $perUser = medium3Coupon([
        'code' => 'TYPE-PERUSER',
        'allowed_car_types' => ['economy'],
        'max_uses' => null,
        'max_uses_per_user' => 1,
    ]);
    $booking = Booking::factory()->create(['user_id' => $user->id]);
    CouponUsage::create([
        'coupon_id' => $perUser->id,
        'user_id' => $user->id,
        'booking_id' => $booking->id,
        'discount_amount' => 100,
        'original_amount' => 1000,
        'final_amount' => 900,
    ]);

    expect($expired->canBeUsed($user->id, 1000, $economy->type)['valid'])->toBeFalse()
        ->and($used->canBeUsed($user->id, 1000, $economy->type)['valid'])->toBeFalse()
        ->and($perUser->canBeUsed($user->id, 1000, $economy->type)['valid'])->toBeFalse();
});

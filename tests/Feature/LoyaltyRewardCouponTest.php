<?php

use App\Events\BookingCompleted;
use App\Listeners\SendCouponRewardNotification;
use App\Mail\CouponRewardMail;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\CustomerLoyaltyReward;
use App\Models\CustomerProfile;
use App\Models\Location;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

function loyaltyLocation(): Location
{
    return Location::create([
        'name' => 'Casablanca Loyalty',
        'address' => '1 Test Street',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000000',
        'email' => 'loyalty@example.com',
        'opening_time' => '08:00',
        'closing_time' => '20:00',
        'is_active' => true,
    ]);
}

function loyaltyCar(?Location $location = null, array $overrides = []): Car
{
    $location ??= loyaltyLocation();

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
        'security_deposit_amount' => 0,
    ], $overrides));
}

function loyaltyBookingFor(User $user, string $status = Booking::STATUS_COMPLETED, array $overrides = []): Booking
{
    $location = $overrides['location'] ?? loyaltyLocation();
    $car = $overrides['car'] ?? loyaltyCar($location);

    unset($overrides['location'], $overrides['car']);

    return Booking::create(array_merge([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'start_date' => now()->subDays(5),
        'end_date' => now()->subDays(3),
        'rental_price_per_day' => 500,
        'insurance_fixed_price' => 0,
        'total_amount' => 1000,
        'status' => $status,
        'advance_payment_amount' => 300,
        'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PAID,
        'security_deposit_amount' => $car->security_deposit_amount ?? 0,
    ], $overrides));
}

function loyaltyCompletedBookings(User $user, int $count): void
{
    $location = loyaltyLocation();
    $car = loyaltyCar($location);

    for ($i = 0; $i < $count; $i++) {
        loyaltyBookingFor($user, Booking::STATUS_COMPLETED, [
            'location' => $location,
            'car' => $car,
            'start_date' => now()->subDays(60 - $i),
            'end_date' => now()->subDays(59 - $i),
        ]);
    }
}

function loyaltyHandle(Booking $booking): void
{
    app(SendCouponRewardNotification::class)->handle(new BookingCompleted($booking));
}

function loyaltyListenerRecognizesUniqueViolation(QueryException $exception): bool
{
    $method = new ReflectionMethod(SendCouponRewardNotification::class, 'isUniqueConstraintViolation');
    $method->setAccessible(true);

    return $method->invoke(app(SendCouponRewardNotification::class), $exception);
}

function loyaltyQueryException(array $errorInfo, string $message = 'SQL error'): QueryException
{
    $previous = new PDOException($message);
    $previous->errorInfo = $errorInfo;

    return new QueryException('testing', 'insert into customer_loyalty_rewards', [], $previous);
}

function loyaltyVerifiedDriver(User $user): void
{
    $profile = CustomerProfile::create([
        'user_id' => $user->id,
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'driving_license_number' => 'LOYALTY-DL',
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

function loyaltyBookingPreviewSession(Car $car, Location $location): array
{
    return [
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'pickup_date' => now()->addDays(2)->toDateString(),
        'return_date' => now()->addDays(4)->toDateString(),
        'pickup_time' => '10:00',
        'return_time' => '10:00',
        'start_date' => now()->addDays(2)->setTime(10, 0)->toDateTimeString(),
        'end_date' => now()->addDays(4)->setTime(10, 0)->toDateTimeString(),
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

function loyaltyCouponFor(User $user, array $overrides = []): Coupon
{
    return Coupon::create(array_merge([
        'code' => 'LOYALTY-TEST-'.strtoupper(str()->random(6)),
        'user_id' => $user->id,
        'category' => 'loyalty',
        'discount_type' => 'percentage',
        'discount_value' => 50,
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addDays(60),
        'max_uses' => 1,
        'max_uses_per_user' => 1,
        'is_active' => true,
        'description' => 'Test loyalty reward',
    ], $overrides));
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-08-31 10:00:00'));
});

test('nine completed rentals do not award a loyalty reward', function () {
    Mail::fake();

    $user = User::factory()->create();
    loyaltyCompletedBookings($user, 8);
    $booking = loyaltyBookingFor($user);

    loyaltyHandle($booking);

    expect(CustomerLoyaltyReward::count())->toBe(0)
        ->and(Coupon::where('category', 'loyalty')->count())->toBe(0)
        ->and(Notification::where('type', 'loyalty_reward_unlocked')->count())->toBe(0);
    Mail::assertNothingSent();
});

test('tenth completed rental awards exactly one fifty percent coupon', function () {
    Mail::fake();

    $user = User::factory()->create();
    loyaltyCompletedBookings($user, 9);
    $booking = loyaltyBookingFor($user);

    loyaltyHandle($booking);

    $reward = CustomerLoyaltyReward::with('coupon')->first();

    expect(CustomerLoyaltyReward::count())->toBe(1)
        ->and(Coupon::where('category', 'loyalty')->count())->toBe(1)
        ->and($reward->user_id)->toBe($user->id)
        ->and($reward->milestone)->toBe(10)
        ->and($reward->qualifying_booking_id)->toBe($booking->id)
        ->and($reward->coupon->user_id)->toBe($user->id)
        ->and($reward->coupon->discount_type)->toBe('percentage')
        ->and((float) $reward->coupon->discount_value)->toBe(50.0)
        ->and($reward->coupon->valid_until->toDateString())->toBe(now()->addDays(60)->toDateString())
        ->and($reward->coupon->max_uses)->toBe(1)
        ->and($reward->coupon->max_uses_per_user)->toBe(1)
        ->and($reward->coupon->is_active)->toBeTrue()
        ->and($reward->coupon->code)->toStartWith('LOYALTY-10-')
        ->and($reward->coupon->min_booking_amount)->toBeNull()
        ->and($reward->coupon->min_bookings)->toBeNull()
        ->and($reward->coupon->min_total_spent)->toBeNull()
        ->and($reward->coupon->allowed_car_types)->toBeNull();

    expect(Notification::where('type', 'loyalty_reward_unlocked')->count())->toBe(1);
    Mail::assertSent(CouponRewardMail::class, 1);
});

test('eleventh through nineteenth completed rentals create no additional reward', function () {
    Mail::fake();

    $user = User::factory()->create();
    loyaltyCompletedBookings($user, 9);
    loyaltyHandle(loyaltyBookingFor($user));

    for ($i = 11; $i <= 19; $i++) {
        loyaltyHandle(loyaltyBookingFor($user));
    }

    expect(CustomerLoyaltyReward::count())->toBe(1)
        ->and(Coupon::where('category', 'loyalty')->count())->toBe(1)
        ->and(Notification::where('type', 'loyalty_reward_unlocked')->count())->toBe(1);
    Mail::assertSent(CouponRewardMail::class, 1);
});

test('twentieth and thirtieth completed rentals create recurring milestone rewards', function () {
    Mail::fake();

    $user = User::factory()->create();
    loyaltyCompletedBookings($user, 9);
    loyaltyHandle(loyaltyBookingFor($user));

    loyaltyCompletedBookings($user, 9);
    loyaltyHandle(loyaltyBookingFor($user));

    loyaltyCompletedBookings($user, 9);
    loyaltyHandle(loyaltyBookingFor($user));

    expect(CustomerLoyaltyReward::query()->orderBy('milestone')->pluck('milestone')->all())
        ->toBe([10, 20, 30])
        ->and(Coupon::where('category', 'loyalty')->count())->toBe(3)
        ->and(Notification::where('type', 'loyalty_reward_unlocked')->count())->toBe(3);

    Mail::assertSent(CouponRewardMail::class, 3);
});

test('only completed rentals count toward loyalty milestones', function (string $status) {
    Mail::fake();

    $user = User::factory()->create();
    loyaltyCompletedBookings($user, 9);
    $booking = loyaltyBookingFor($user, $status);

    loyaltyHandle($booking);

    expect(CustomerLoyaltyReward::count())->toBe(0)
        ->and(Coupon::where('category', 'loyalty')->count())->toBe(0);
    Mail::assertNothingSent();
})->with([
    Booking::STATUS_PENDING,
    Booking::STATUS_CONFIRMED,
    Booking::STATUS_ACTIVE,
    Booking::STATUS_CANCELLED,
]);

test('duplicate booking completed processing does not duplicate reward coupon notification or email', function () {
    Mail::fake();

    $user = User::factory()->create();
    loyaltyCompletedBookings($user, 9);
    $booking = loyaltyBookingFor($user);

    loyaltyHandle($booking);
    loyaltyHandle($booking);

    expect(CustomerLoyaltyReward::count())->toBe(1)
        ->and(Coupon::where('category', 'loyalty')->count())->toBe(1)
        ->and(Notification::where('type', 'loyalty_reward_unlocked')->count())->toBe(1);
    Mail::assertSent(CouponRewardMail::class, 1);
});

test('concurrent completions cannot skip the crossed loyalty milestone', function () {
    Mail::fake();

    $user = User::factory()->create();
    loyaltyCompletedBookings($user, 9);

    $tenthBooking = loyaltyBookingFor($user);
    $eleventhBooking = loyaltyBookingFor($user);

    loyaltyHandle($eleventhBooking);

    expect(CustomerLoyaltyReward::count())->toBe(0)
        ->and(Coupon::where('category', 'loyalty')->count())->toBe(0);

    loyaltyHandle($tenthBooking);

    $reward = CustomerLoyaltyReward::first();

    expect(CustomerLoyaltyReward::count())->toBe(1)
        ->and($reward->milestone)->toBe(10)
        ->and($reward->qualifying_booking_id)->toBe($tenthBooking->id)
        ->and(Coupon::where('category', 'loyalty')->count())->toBe(1)
        ->and(Notification::where('type', 'loyalty_reward_unlocked')->count())->toBe(1);

    Mail::assertSent(CouponRewardMail::class, 1);
});

test('future completions do not backfill old loyalty milestones', function () {
    Mail::fake();

    $user = User::factory()->create();
    loyaltyCompletedBookings($user, 25);

    for ($i = 26; $i <= 29; $i++) {
        loyaltyHandle(loyaltyBookingFor($user));
    }

    expect(CustomerLoyaltyReward::count())->toBe(0)
        ->and(Coupon::where('category', 'loyalty')->count())->toBe(0);

    $thirtiethBooking = loyaltyBookingFor($user);
    loyaltyHandle($thirtiethBooking);

    $reward = CustomerLoyaltyReward::first();

    expect(CustomerLoyaltyReward::count())->toBe(1)
        ->and($reward->milestone)->toBe(30)
        ->and($reward->qualifying_booking_id)->toBe($thirtiethBooking->id);

    Mail::assertSent(CouponRewardMail::class, 1);
});

test('loyalty listener only treats precise duplicate key errors as duplicate rewards', function () {
    expect(loyaltyListenerRecognizesUniqueViolation(loyaltyQueryException([
        '23000',
        1062,
        "Duplicate entry '1-10' for key 'customer_loyalty_rewards_user_id_milestone_unique'",
    ])))->toBeTrue();

    expect(loyaltyListenerRecognizesUniqueViolation(loyaltyQueryException([
        '23505',
        7,
        'duplicate key value violates unique constraint',
    ])))->toBeTrue();

    expect(loyaltyListenerRecognizesUniqueViolation(loyaltyQueryException([
        '23000',
        19,
        'UNIQUE constraint failed: customer_loyalty_rewards.user_id, customer_loyalty_rewards.milestone',
    ], 'UNIQUE constraint failed: customer_loyalty_rewards.user_id, customer_loyalty_rewards.milestone')))->toBeTrue();

    expect(loyaltyListenerRecognizesUniqueViolation(loyaltyQueryException([
        '23000',
        1452,
        'Cannot add or update a child row: a foreign key constraint fails',
    ])))->toBeFalse();
});

test('loyalty reward database uniqueness protects milestones and qualifying bookings', function () {
    $user = User::factory()->create();
    $booking = loyaltyBookingFor($user);
    $coupon = loyaltyCouponFor($user);

    CustomerLoyaltyReward::create([
        'user_id' => $user->id,
        'milestone' => 10,
        'coupon_id' => $coupon->id,
        'qualifying_booking_id' => $booking->id,
        'awarded_at' => now(),
    ]);

    $secondCoupon = loyaltyCouponFor($user);
    $secondBooking = loyaltyBookingFor($user);

    expect(fn () => CustomerLoyaltyReward::create([
        'user_id' => $user->id,
        'milestone' => 10,
        'coupon_id' => $secondCoupon->id,
        'qualifying_booking_id' => $secondBooking->id,
        'awarded_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => CustomerLoyaltyReward::create([
        'user_id' => User::factory()->create()->id,
        'milestone' => 10,
        'coupon_id' => $secondCoupon->id,
        'qualifying_booking_id' => $booking->id,
        'awarded_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('mail failure does not remove reward coupon or notification', function () {
    Mail::shouldReceive('to')
        ->once()
        ->andThrow(new RuntimeException('SMTP unavailable'));

    $user = User::factory()->create();
    loyaltyCompletedBookings($user, 9);
    $booking = loyaltyBookingFor($user);

    loyaltyHandle($booking);

    expect(CustomerLoyaltyReward::count())->toBe(1)
        ->and(Coupon::where('category', 'loyalty')->count())->toBe(1)
        ->and(Notification::where('type', 'loyalty_reward_unlocked')->count())->toBe(1);
});

test('banned users are not awarded new loyalty rewards', function () {
    Mail::fake();

    $user = User::factory()->create(['is_banned' => true]);
    loyaltyCompletedBookings($user, 9);
    $booking = loyaltyBookingFor($user);

    loyaltyHandle($booking);

    expect(CustomerLoyaltyReward::count())->toBe(0)
        ->and(Coupon::where('category', 'loyalty')->count())->toBe(0)
        ->and(Notification::where('type', 'loyalty_reward_unlocked')->count())->toBe(0);
    Mail::assertNothingSent();
});

test('recipient can apply loyalty coupon and another customer cannot use the same code', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $location = loyaltyLocation();
    $car = loyaltyCar($location);
    $coupon = loyaltyCouponFor($user);

    $this->actingAs($user)
        ->withSession(['booking_preview' => loyaltyBookingPreviewSession($car, $location)])
        ->post(route('coupons.apply'), ['coupon_code' => $coupon->code])
        ->assertRedirect()
        ->assertSessionHas('applied_coupon.id', $coupon->id)
        ->assertSessionHas('coupon_discount', 500.0);

    $this->actingAs($otherUser)
        ->withSession(['booking_preview' => loyaltyBookingPreviewSession($car, $location)])
        ->post(route('coupons.apply'), ['coupon_code' => $coupon->code])
        ->assertRedirect()
        ->assertSessionHasErrors('coupon_code');
});

test('loyalty coupon remains one use and expired coupons are rejected', function () {
    $user = User::factory()->create();
    $location = loyaltyLocation();
    $car = loyaltyCar($location);
    $coupon = loyaltyCouponFor($user);
    $booking = loyaltyBookingFor($user, Booking::STATUS_PENDING, ['location' => $location, 'car' => $car]);

    CouponUsage::create([
        'coupon_id' => $coupon->id,
        'user_id' => $user->id,
        'booking_id' => $booking->id,
        'discount_amount' => 500,
        'original_amount' => 1000,
        'final_amount' => 500,
    ]);

    $coupon->forceFill(['used_count' => 1])->save();

    $this->actingAs($user)
        ->withSession(['booking_preview' => loyaltyBookingPreviewSession($car, $location)])
        ->post(route('coupons.apply'), ['coupon_code' => $coupon->code])
        ->assertRedirect()
        ->assertSessionHasErrors('coupon_code');

    $expiredCoupon = loyaltyCouponFor($user, [
        'code' => 'LOYALTY-EXPIRED',
        'valid_from' => now()->subDays(10),
        'valid_until' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->withSession(['booking_preview' => loyaltyBookingPreviewSession($car, $location)])
        ->post(route('coupons.apply'), ['coupon_code' => $expiredCoupon->code])
        ->assertRedirect()
        ->assertSessionHasErrors('coupon_code');
});

test('booking creation consumes loyalty coupon and keeps security deposit unchanged', function () {
    $user = User::factory()->create();
    loyaltyVerifiedDriver($user);

    $location = loyaltyLocation();
    $car = loyaltyCar($location, ['security_deposit_amount' => 3000]);
    $coupon = loyaltyCouponFor($user);

    $this->actingAs($user)
        ->withSession([
            'booking_preview' => loyaltyBookingPreviewSession($car, $location),
            'applied_coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
            ],
            'coupon_discount' => 500.0,
            'final_total' => 500.0,
        ])
        ->post(route('bookings.store'))
        ->assertRedirect();

    $booking = Booking::latest('id')->first();

    expect($booking->total_amount)->toEqual('500.00')
        ->and($booking->discount_amount)->toEqual('500.00')
        ->and($booking->security_deposit_amount)->toEqual('3000.00')
        ->and(CouponUsage::where('coupon_id', $coupon->id)->where('booking_id', $booking->id)->exists())->toBeTrue();
});

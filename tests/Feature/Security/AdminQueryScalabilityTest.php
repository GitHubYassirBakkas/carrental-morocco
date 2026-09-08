<?php

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

test('admin dashboard uses scalar counts and bounded notification widgets', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Booking::factory()
        ->count(12)
        ->create([
            'status' => Booking::STATUS_PENDING,
            'created_at' => now()->subMinutes(10),
        ]);

    Booking::factory()
        ->count(4)
        ->create([
            'status' => Booking::STATUS_ACTIVE,
            'end_date' => today(),
        ]);

    Booking::factory()
        ->count(3)
        ->create([
            'status' => Booking::STATUS_CONFIRMED,
            'start_date' => today(),
        ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();

    expect($response->viewData('bookingsToConfirm'))->toBe(12)
        ->and($response->viewData('endingTodayBookings'))->toBe(4)
        ->and($response->viewData('startingTodayBookings'))->toBe(3)
        ->and($response->viewData('bookingsToConfirm'))->toBeInt()
        ->and($response->viewData('endingTodayBookings'))->toBeInt()
        ->and($response->viewData('startingTodayBookings'))->toBeInt()
        ->and($response->viewData('adminNotifications'))->toHaveCount(5);
});

test('admin coupon usage history is paginated and preserves query strings', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $coupon = Coupon::create([
        'code' => 'SCALE20',
        'category' => 'seasonal',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'max_uses_per_user' => 1,
        'used_count' => 25,
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addMonth(),
        'is_active' => true,
    ]);

    for ($i = 0; $i < 25; $i++) {
        $user = User::factory()->create();
        $booking = Booking::factory()->for($user)->create();

        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'discount_amount' => 100,
            'original_amount' => 1000,
            'final_amount' => 900,
            'created_at' => now()->subMinutes($i),
            'updated_at' => now()->subMinutes($i),
        ]);
    }

    $response = $this->actingAs($admin)
        ->get(route('admin.coupons.show', ['coupon' => $coupon, 'audit' => 'scale']))
        ->assertOk()
        ->assertSee('audit=scale', false);

    $usages = $response->viewData('usages');

    expect($usages)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($usages->count())->toBe(20)
        ->and($usages->total())->toBe(25)
        ->and($usages->getCollection()->first()->relationLoaded('user'))->toBeTrue()
        ->and($usages->getCollection()->first()->relationLoaded('booking'))->toBeTrue();

    $secondPage = $this->actingAs($admin)
        ->get(route('admin.coupons.show', ['coupon' => $coupon, 'audit' => 'scale', 'page' => 2]))
        ->assertOk();

    expect($secondPage->viewData('usages')->count())->toBe(5)
        ->and($secondPage->viewData('usages')->total())->toBe(25);
});

test('admin query scalability changes do not expose admin reads to non admins', function () {
    $user = User::factory()->create(['role' => 'user']);
    $coupon = Coupon::create([
        'code' => 'NOACCESS20',
        'category' => 'seasonal',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'max_uses_per_user' => 1,
        'used_count' => 0,
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addMonth(),
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.coupons.show', $coupon))
        ->assertForbidden();
});

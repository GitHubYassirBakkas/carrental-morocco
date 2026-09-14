<?php

use App\Mail\BookingPendingMail;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Setting;
use App\Models\User;
use App\Services\AdvancePaymentService;
use App\Services\RentalBusinessRules;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

function adminSettingsRule(string $key, mixed $value, string $type = 'number'): void
{
    Setting::set($key, $value, $type);
}

function adminSettingsLocation(array $attributes = []): Location
{
    return Location::factory()->create($attributes);
}

function adminSettingsCar(Location $location, array $attributes = []): Car
{
    return Car::factory()->create(array_merge([
        'location_id' => $location->id,
        'price_per_day' => 700,
        'minimum_age' => 21,
        'security_deposit_amount' => 0,
    ], $attributes));
}

function adminSettingsVerifiedDriver(User $user, int $age = 30): CustomerProfile
{
    $profile = CustomerProfile::create([
        'user_id' => $user->id,
        'date_of_birth' => now()->subYears($age)->toDateString(),
        'driving_license_number' => 'ADMIN-SETTINGS-DL-'.$user->id,
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

    return $profile->fresh();
}

function adminSettingsPreviewPayload(Location $location, Carbon $pickup, int $days = 3): array
{
    return [
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'pickup_date' => $pickup->toDateString(),
        'pickup_time' => '10:00',
        'return_date' => $pickup->copy()->addDays($days)->toDateString(),
        'return_time' => '10:00',
    ];
}

function adminSettingsPreviewSession(Car $car, Location $location, Carbon $pickup, int $days = 2): array
{
    $start = $pickup->copy()->setTime(10, 0);
    $end = $start->copy()->addDays($days);
    $rentalAmount = (float) $car->price_per_day * $days;

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
        'days' => $days,
        'car_price' => $car->price_per_day,
        'insurance_id' => null,
        'insurance_price' => 0,
        'car_total' => $rentalAmount,
        'insurance_total' => 0,
        'dropoff_fee' => 0,
        'rental_amount' => $rentalAmount,
        'insurance_amount' => 0,
        'protection_plan_amount' => 0,
        'extras_amount' => 0,
        'pricing_breakdown' => [
            'rental_amount' => $rentalAmount,
            'insurance_amount' => 0,
            'protection_plan_amount' => 0,
            'extras_amount' => 0,
            'subtotal_amount' => $rentalAmount,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => $rentalAmount,
        ],
        'grand_total' => $rentalAmount,
    ];
}

test('public car details reflects configured datepicker and effective minimum age rules', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-01 09:00:00'));

    adminSettingsRule('booking_min_days', 3);
    adminSettingsRule('booking_max_days', 5);
    adminSettingsRule('max_advance_booking_days', 12);
    adminSettingsRule('min_driver_age', 23);

    $location = adminSettingsLocation();
    $car = adminSettingsCar($location, ['minimum_age' => 25]);

    $this->get(route('cars.details', $car))
        ->assertOk()
        ->assertSee('minDays: 3', false)
        ->assertSee('maxDays: 5', false)
        ->assertSee('maxAdvanceDays: 12', false)
        ->assertSee("maxAdvancePickupDate: '2026-09-13'", false)
        ->assertSee('maxDate: "2026-09-18"', false)
        ->assertSeeText('25');

    Carbon::setTestNow();
});

test('booking preview enforces rental duration and max advance settings', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-01 09:00:00'));

    adminSettingsRule('booking_min_days', 3);
    adminSettingsRule('booking_max_days', 5);
    adminSettingsRule('max_advance_booking_days', 10);

    $user = User::factory()->create();
    $location = adminSettingsLocation();
    $car = adminSettingsCar($location);
    adminSettingsVerifiedDriver($user);

    $this->actingAs($user)
        ->post(route('bookings.preview', $car), adminSettingsPreviewPayload($location, now()->copy()->addDay(), 2))
        ->assertSessionHasErrors('dates');

    $this->actingAs($user)
        ->post(route('bookings.preview', $car), adminSettingsPreviewPayload($location, now()->copy()->addDay(), 6))
        ->assertSessionHasErrors('dates');

    $this->actingAs($user)
        ->post(route('bookings.preview', $car), adminSettingsPreviewPayload($location, now()->copy()->addDays(11), 3))
        ->assertSessionHasErrors('dates');

    $this->actingAs($user)
        ->post(route('bookings.preview', $car), adminSettingsPreviewPayload($location, now()->copy()->addDays(10), 3))
        ->assertRedirect(route('bookings.preview.show'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('booking_preview.days', 3);

    Carbon::setTestNow();
});

test('booking store waits for cash selection before storing configured advance deadline', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-01 09:00:00'));
    Mail::fake();

    adminSettingsRule('advance_payment_deadline_hours', 12);
    adminSettingsRule('tax_percentage', 20);

    $user = User::factory()->create();
    $location = adminSettingsLocation();
    $car = adminSettingsCar($location, ['price_per_day' => 700]);
    adminSettingsVerifiedDriver($user);

    $response = $this->actingAs($user)
        ->withSession([
            'booking_preview' => adminSettingsPreviewSession($car, $location, now()->copy()->addDays(2), 2),
        ])
        ->post(route('bookings.store'));

    $booking = Booking::with('invoice')->firstOrFail();

    $response->assertRedirect(route('payments.show', $booking));

    expect($booking->advance_payment_due_at)->toBeNull()
        ->and((float) $booking->total_amount)->toBe(1680.0)
        ->and((float) $booking->advance_payment_amount)->toBe(504.0)
        ->and($booking->invoice)->toBeInstanceOf(Invoice::class)
        ->and((float) $booking->invoice->subtotal)->toBe(1400.0)
        ->and((float) $booking->invoice->tax_amount)->toBe(280.0)
        ->and((float) $booking->invoice->total_amount)->toBe(1680.0);

    $this->actingAs($user)
        ->post(route('payments.store', $booking), ['payment_method' => 'cash'])
        ->assertRedirect(route('bookings.success', $booking));

    expect($booking->fresh()->advance_payment_due_at->toDateTimeString())->toBe('2026-09-01 21:00:00');

    Carbon::setTestNow();
});

test('non zero tax keeps preview booking and invoice totals aligned while security deposit stays separate', function () {
    Mail::fake();

    adminSettingsRule('tax_percentage', 20);

    $user = User::factory()->create();
    $location = adminSettingsLocation();
    $car = adminSettingsCar($location, [
        'price_per_day' => 500,
        'security_deposit_amount' => 3000,
    ]);
    adminSettingsVerifiedDriver($user);

    $this->actingAs($user)
        ->post(route('bookings.preview', $car), adminSettingsPreviewPayload($location, now()->copy()->addDays(2), 2))
        ->assertRedirect(route('bookings.preview.show'))
        ->assertSessionHas('booking_preview.pricing_breakdown.tax_amount', 200.0)
        ->assertSessionHas('booking_preview.grand_total', 1200.0);

    $previewTotal = session('booking_preview.grand_total');

    $this->actingAs($user)
        ->post(route('bookings.store'))
        ->assertRedirect();

    $booking = Booking::with('invoice')->firstOrFail();

    expect((float) $previewTotal)->toBe(1200.0)
        ->and((float) $booking->total_amount)->toBe((float) $previewTotal)
        ->and((float) $booking->invoice->total_amount)->toBe((float) $booking->total_amount)
        ->and((float) $booking->invoice->subtotal)->toBe(1000.0)
        ->and((float) $booking->invoice->tax_amount)->toBe(200.0)
        ->and((float) $booking->security_deposit_amount)->toBe(3000.0);
});

test('cash selection preserves an existing stored advance deadline', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-01 09:00:00'));
    Mail::fake();

    adminSettingsRule('advance_payment_deadline_hours', 48);

    $user = User::factory()->create();
    $storedDeadline = Carbon::parse('2026-09-01 21:00:00');
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'status' => Booking::STATUS_PENDING,
        'total_amount' => 1000,
        'advance_payment_due_at' => $storedDeadline,
        'security_deposit_amount' => 0,
        'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_PENDING,
    ]);

    $this->actingAs($user)
        ->post(route('payments.store', $booking), ['payment_method' => 'cash'])
        ->assertRedirect(route('bookings.success', $booking));

    expect($booking->fresh()->advance_payment_due_at->toDateTimeString())->toBe($storedDeadline->toDateTimeString());

    Carbon::setTestNow();
});

test('advance confirmation requirement uses stored booking amount after percentage setting changes', function () {
    adminSettingsRule('advance_payment_percentage', 80);

    $booking = Booking::factory()->create([
        'total_amount' => 1000,
        'advance_payment_amount' => 300,
    ]);

    expect(app(AdvancePaymentService::class)->calculateMinimumAdvancePayment($booking))->toBe(300.0);
});

test('effective minimum driver age uses the max of global and car specific settings', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-01 09:00:00'));

    adminSettingsRule('min_driver_age', 23);

    $user = User::factory()->create();
    $location = adminSettingsLocation();
    $car = adminSettingsCar($location, ['minimum_age' => 21]);
    adminSettingsVerifiedDriver($user, 22);

    expect(app(RentalBusinessRules::class)->effectiveMinimumDriverAge($car))->toBe(23);

    $this->actingAs($user)
        ->post(route('bookings.preview', $car), adminSettingsPreviewPayload($location, now()->copy()->addDays(2), 3))
        ->assertSessionHasErrors('driver_age');

    adminSettingsRule('min_driver_age', 21);

    $olderCar = adminSettingsCar($location, ['minimum_age' => 25]);

    expect(app(RentalBusinessRules::class)->effectiveMinimumDriverAge($olderCar))->toBe(25);

    $this->actingAs($user)
        ->post(route('bookings.preview', $olderCar), adminSettingsPreviewPayload($location, now()->copy()->addDays(2), 3))
        ->assertSessionHasErrors('driver_age');

    Carbon::setTestNow();
});

test('cash payment deadline copy reflects configured advance deadline', function () {
    Mail::fake();

    adminSettingsRule('advance_payment_deadline_hours', 12);

    $user = User::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'status' => Booking::STATUS_PENDING,
        'total_amount' => 1000,
        'security_deposit_amount' => 0,
        'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_PENDING,
    ]);

    $this->actingAs($user)
        ->post(route('payments.store', $booking), ['payment_method' => 'cash'])
        ->assertRedirect(route('bookings.success', $booking))
        ->assertSessionHas('success', 'Booking created! Please visit our agency within 12 hours.');

    Mail::assertSent(BookingPendingMail::class, 1);
});

test('general identity settings are used by public contact footer legal and booking email views', function () {
    adminSettingsRule('site_name', 'Atlas Fleet Morocco', 'text');
    adminSettingsRule('site_phone', '+212 6 12 34 56 78', 'text');
    adminSettingsRule('site_email', 'ops@example.test', 'text');
    adminSettingsRule('site_address', '12 Avenue Atlas, Meknes', 'text');
    adminSettingsRule('advance_payment_deadline_hours', 12);

    adminSettingsLocation();

    $this->get(route('contact'))
        ->assertOk()
        ->assertSeeText('Atlas Fleet Morocco')
        ->assertSeeText('+212 6 12 34 56 78')
        ->assertSeeText('ops@example.test')
        ->assertSeeText('12 Avenue Atlas, Meknes');

    $this->get(route('legal.notice'))
        ->assertOk()
        ->assertSeeText('Atlas Fleet Morocco')
        ->assertSeeText('12 Avenue Atlas, Meknes');

    $booking = Booking::factory()->create([
        'user_id' => User::factory()->create(['name' => 'Test Driver'])->id,
        'car_id' => adminSettingsCar(adminSettingsLocation())->id,
    ]);

    $email = (new BookingPendingMail($booking->load('user', 'car')))->render();

    expect($email)->toContain('Atlas Fleet Morocco')
        ->and($email)->toContain('ops@example.test')
        ->and($email)->toContain('+212 6 12 34 56 78')
        ->and($email)->toContain('12 Avenue Atlas, Meknes')
        ->and($email)->toContain('12 hours');
});

test('saving a setting invalidates individual shared and group settings cache entries', function () {
    Setting::updateOrCreate(['key' => 'site_name'], [
        'value' => 'Cached Agency',
        'type' => 'text',
        'group' => 'general',
        'label' => 'Site Name',
        'description' => null,
        'autoload' => true,
        'is_public' => true,
    ]);

    Cache::put('setting_site_name', 'Cached Agency');
    Cache::put('autoload_settings', ['site_name' => 'Cached Agency']);
    Cache::put('public_settings', ['site_name' => 'Cached Agency']);
    Cache::put('settings_group_general', ['site_name' => 'Cached Agency']);

    Setting::set('site_name', 'Fresh Agency', 'text');

    expect(Cache::has('setting_site_name'))->toBeFalse()
        ->and(Cache::has('autoload_settings'))->toBeFalse()
        ->and(Cache::has('public_settings'))->toBeFalse()
        ->and(Cache::has('settings_group_general'))->toBeFalse()
        ->and(Setting::get('site_name'))->toBe('Fresh Agency');
});

test('admin refund settings enforce pickup-based window order and hide retired toggles', function () {
    Setting::updateOrCreate(['key' => 'refund_cancellation_window_hours'], [
        'value' => 48,
        'type' => 'number',
        'group' => 'refund',
        'label' => 'Full Refund Before Pickup (Hours)',
        'description' => 'Scheduled pickup must be at least this many hours away for a full rental payment refund',
        'autoload' => true,
        'is_public' => false,
    ]);
    Setting::updateOrCreate(['key' => 'refund_partial_refund_cutoff_hours'], [
        'value' => 24,
        'type' => 'number',
        'group' => 'refund',
        'label' => 'Partial Refund Until Pickup (Hours)',
        'description' => 'Scheduled pickup must be at least this many hours away for the configured partial refund',
        'autoload' => true,
        'is_public' => false,
    ]);
    Setting::updateOrCreate(
        ['key' => 'refund_free_cancellation_enabled'],
        ['value' => true, 'type' => 'boolean', 'group' => 'refund', 'label' => 'Free Cancellation']
    );
    Setting::updateOrCreate(
        ['key' => 'refund_no_refund_enabled'],
        ['value' => true, 'type' => 'boolean', 'group' => 'refund', 'label' => 'No Refund']
    );

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('Full Refund Before Pickup (Hours)')
        ->assertSee('Partial Refund Until Pickup (Hours)')
        ->assertDontSee('name="refund_free_cancellation_enabled"', false)
        ->assertDontSee('name="refund_no_refund_enabled"', false);

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'refund_cancellation_window_hours' => 12,
            'refund_partial_refund_cutoff_hours' => 12,
            'refund_partial_percentage' => 50,
        ])
        ->assertSessionHasErrors('refund_cancellation_window_hours');
});

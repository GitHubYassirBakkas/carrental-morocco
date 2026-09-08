<?php

use App\Models\Booking;
use App\Models\BookingInspection;
use App\Models\BookingPhoto;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

function low3SecurityHeaderProbe(): string
{
    $uri = '/_low3-security-headers';

    if (! Route::has('low3.security.headers')) {
        Route::middleware('web')->get($uri, fn () => response('ok'))->name('low3.security.headers');
    }

    return $uri;
}

function low3User(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'is_banned' => false,
    ], $attributes));
}

function low3Location(): Location
{
    return Location::create([
        'name' => 'Low 3 Header Desk',
        'address' => '3 Header Street',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000003',
        'email' => 'low3@example.test',
        'opening_time' => '08:00',
        'closing_time' => '20:00',
        'is_active' => true,
    ]);
}

function low3Booking(?User $user = null): Booking
{
    $user ??= low3User();
    $location = low3Location();
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
        'image' => 'low3-car.jpg',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => 0,
    ]);

    $booking = Booking::create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'start_date' => now()->addDays(3),
        'end_date' => now()->addDays(5),
        'rental_price_per_day' => 500,
        'insurance_fixed_price' => 0,
        'total_amount' => 1000,
        'status' => Booking::STATUS_PENDING,
        'advance_payment_amount' => 300,
        'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PENDING,
        'advance_payment_due_at' => now()->addDay(),
        'security_deposit_amount' => 0,
        'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_PENDING,
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => 1000,
        'tax_amount' => 0,
        'total_amount' => 1000,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
        'due_date' => now()->addDay(),
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 1000,
        'method' => 'card',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'paid_at' => now(),
    ]);

    return $booking;
}

function low3SetEnvironment(string $environment): void
{
    app()->detectEnvironment(fn () => $environment);
}

test('baseline browser security headers are emitted', function () {
    config([
        'security.headers.csp.enabled' => false,
        'security.headers.hsts.enabled' => false,
    ]);

    $this->get(low3SecurityHeaderProbe())
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

test('content security policy is report only when configured', function () {
    config([
        'security.headers.csp.enabled' => true,
        'security.headers.csp.report_only' => true,
    ]);

    $response = $this->get(low3SecurityHeaderProbe())
        ->assertOk()
        ->assertHeaderMissing('Content-Security-Policy');

    $policy = $response->headers->get('Content-Security-Policy-Report-Only');

    expect($policy)->toContain("default-src 'self'")
        ->and($policy)->toContain("object-src 'none'")
        ->and($policy)->toContain("base-uri 'self'")
        ->and($policy)->toContain("frame-ancestors 'self'")
        ->and($policy)->toContain("form-action 'self'");
});

test('content security policy can be switched to enforcement mode', function () {
    config([
        'security.headers.csp.enabled' => true,
        'security.headers.csp.report_only' => false,
    ]);

    $response = $this->get(low3SecurityHeaderProbe())
        ->assertOk()
        ->assertHeaderMissing('Content-Security-Policy-Report-Only');

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("default-src 'self'");
});

test('content security policy includes current Stripe and CDN browser requirements', function () {
    config([
        'security.headers.csp.enabled' => true,
        'security.headers.csp.report_only' => true,
    ]);

    $policy = $this->get(low3SecurityHeaderProbe())
        ->assertOk()
        ->headers->get('Content-Security-Policy-Report-Only');

    expect($policy)->toContain('https://js.stripe.com')
        ->and($policy)->toContain('https://api.stripe.com')
        ->and($policy)->toContain('https://r.stripe.com')
        ->and($policy)->toContain('https://m.stripe.network')
        ->and($policy)->toContain('https://hooks.stripe.com')
        ->and($policy)->toContain('https://cdn.jsdelivr.net')
        ->and($policy)->toContain('https://cdnjs.cloudflare.com')
        ->and($policy)->toContain('https://fonts.googleapis.com')
        ->and($policy)->toContain('https://fonts.gstatic.com')
        ->and($policy)->toContain('https://fonts.bunny.net')
        ->and($policy)->toContain('https://upload.wikimedia.org')
        ->and($policy)->toContain('https://images.unsplash.com')
        ->and($policy)->not->toContain('*')
        ->and($policy)->not->toContain("'unsafe-eval'");
});

test('hsts is emitted only for production https when explicitly enabled', function () {
    low3SetEnvironment('production');

    config(['security.headers.hsts.enabled' => true]);

    $this->get('https://localhost'.low3SecurityHeaderProbe())
        ->assertOk()
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});

test('hsts is absent for local development and non secure production requests', function () {
    config(['security.headers.hsts.enabled' => true]);

    low3SetEnvironment('local');

    $this->get('https://localhost'.low3SecurityHeaderProbe())
        ->assertOk()
        ->assertHeaderMissing('Strict-Transport-Security');

    low3SetEnvironment('production');

    $this->get('http://localhost'.low3SecurityHeaderProbe())
        ->assertOk()
        ->assertHeaderMissing('Strict-Transport-Security');
});

test('hsts deliberately excludes preload and include subdomains', function () {
    low3SetEnvironment('production');

    config(['security.headers.hsts.enabled' => true]);

    $header = $this->get('https://localhost'.low3SecurityHeaderProbe())
        ->assertOk()
        ->headers->get('Strict-Transport-Security');

    expect($header)->toBe('max-age=31536000')
        ->and(strtolower($header))->not->toContain('preload')
        ->and(strtolower($header))->not->toContain('includesubdomains');
});

test('customer admin payment and private evidence routes remain reachable or protected', function () {
    config([
        'security.headers.csp.enabled' => true,
        'security.headers.csp.report_only' => true,
    ]);

    Storage::fake('local');

    $customer = low3User();
    $admin = low3User(['role' => 'admin']);
    $booking = low3Booking($customer);

    $this->get('/')
        ->assertOk()
        ->assertHeader('Content-Security-Policy-Report-Only');

    $this->actingAs($customer)
        ->get(route('payments.show', $booking))
        ->assertOk()
        ->assertHeader('Content-Security-Policy-Report-Only');

    $inspection = BookingInspection::create([
        'booking_id' => $booking->id,
        'type' => 'checkin',
        'mileage' => 100,
        'fuel_level' => 80,
        'has_damage' => false,
        'created_by' => $admin->id,
    ]);

    Storage::disk('local')->put('booking-evidence/inspections/low3.jpg', 'private image');

    $photo = BookingPhoto::create([
        'booking_inspection_id' => $inspection->id,
        'path' => 'booking-evidence/inspections/low3.jpg',
    ]);

    $this->app['auth']->guard()->logout();

    $this->get(route('admin.bookings.inspection.photos.show', $photo))
        ->assertRedirect(route('login'));

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertHeader('Content-Security-Policy-Report-Only');

    $this->actingAs($admin)
        ->get(route('admin.bookings.inspection.photos.show', $photo))
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

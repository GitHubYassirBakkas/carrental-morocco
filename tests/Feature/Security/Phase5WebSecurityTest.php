<?php

use App\Models\Booking;
use App\Models\BookingInspection;
use App\Models\BookingPhoto;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function phase57User(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'is_banned' => false,
    ], $attributes));
}

function phase57Location(): Location
{
    return Location::create([
        'name' => 'Phase 5.7 Security Desk',
        'address' => '57 Header Street',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000057',
        'email' => 'phase57@example.test',
        'opening_time' => '08:00',
        'closing_time' => '20:00',
        'is_active' => true,
    ]);
}

function phase57Car(?Location $location = null): Car
{
    $location ??= phase57Location();

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
        'price_per_day' => 500,
        'image' => 'phase57-car.jpg',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => 0,
    ]);
}

function phase57Booking(?User $user = null): Booking
{
    $user ??= phase57User();
    $location = phase57Location();
    $car = phase57Car($location);

    return Booking::create([
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
}

function phase57Invoice(Booking $booking): Invoice
{
    return Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => 1000,
        'tax_amount' => 0,
        'total_amount' => 1000,
        'status' => Invoice::STATUS_PENDING,
        'issued_at' => now(),
        'due_date' => now()->addDay(),
    ]);
}

function phase57MiddlewareFor(string $method, string $uri): array
{
    return Route::getRoutes()
        ->match(Request::create($uri, $method))
        ->gatherMiddleware();
}

test('safe security headers are present on web responses', function () {
    $this->get('/')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

test('failed login attempts are rate limited', function () {
    $user = phase57User([
        'email' => 'phase57-throttle@example.test',
    ]);

    $key = Str::transliterate(Str::lower($user->email).'|127.0.0.1');
    RateLimiter::clear($key);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'not-the-password',
        ])->assertSessionHasErrors('email');
    }

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'not-the-password',
    ])->assertSessionHasErrors('email');

    expect(RateLimiter::tooManyAttempts($key, 5))->toBeTrue();

    RateLimiter::clear($key);
});

test('abuse prone routes have focused throttling middleware', function () {
    expect(phase57MiddlewareFor('POST', '/register'))->toContain('throttle:phase5-register')
        ->and(phase57MiddlewareFor('POST', '/forgot-password'))->toContain('throttle:phase5-password-reset')
        ->and(phase57MiddlewareFor('POST', '/contact/send'))->toContain('throttle:phase5-contact')
        ->and(phase57MiddlewareFor('POST', '/booking/confirm'))->toContain('throttle:phase5-booking')
        ->and(phase57MiddlewareFor('POST', '/bookings/1/payment'))->toContain('throttle:phase5-payment')
        ->and(phase57MiddlewareFor('POST', '/contact'))->toContain('throttle:phase5-support')
        ->and(phase57MiddlewareFor('POST', '/account/support/1/reply'))->toContain('throttle:phase5-support')
        ->and(phase57MiddlewareFor('POST', '/admin/invoices/1/refund'))->toContain('throttle:phase5-admin-action')
        ->and(phase57MiddlewareFor('POST', '/admin/bookings/1/security-deposit/release'))->toContain('throttle:phase5-admin-action');
});

test('admin authorization boundary remains intact', function () {
    $admin = phase57User(['role' => 'admin']);
    $customer = phase57User();

    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));

    $this->actingAs($customer)
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('customer support messages are escaped when rendered', function () {
    $user = phase57User(['name' => '<script>alert("name")</script>']);

    $ticket = Ticket::create([
        'user_id' => $user->id,
        'ticket_number' => 'TKT-5701',
        'subject' => '<img src=x onerror=alert(1)>',
        'category' => 'other',
        'status' => 'open',
        'priority' => 'medium',
    ]);

    TicketMessage::create([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'message' => '<script>alert("ticket")</script>',
        'is_admin' => false,
    ]);

    $this->actingAs($user)
        ->get(route('tickets.show', $ticket))
        ->assertOk()
        ->assertSee('<script>alert("ticket")</script>')
        ->assertSee('<img src=x onerror=alert(1)>')
        ->assertDontSee('<script>alert("ticket")</script>', false)
        ->assertDontSee('<img src=x onerror=alert(1)>', false);
});

test('csrf exemption remains limited to stripe webhook', function () {
    $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

    expect($bootstrap)->toContain("'stripe/webhook'")
        ->and($bootstrap)->not->toContain("'contact/send'")
        ->and($bootstrap)->not->toContain("'booking/confirm'")
        ->and($bootstrap)->not->toContain("'admin/'");
});

test('root diagnostic scripts are absent from the deployment tree', function () {
    $dangerousScripts = [
        'check_db_direct.php',
        'check_db_file.php',
        'check_db_settings.php',
        'check_env.php',
        'check_invoice.php',
        'check_invoice_59.php',
        'check_settings.php',
        'check_settings_direct.php',
        'diagnostic_report.php',
        'direct_query.php',
        'final_verification.php',
        'force_seed.php',
        'get_invoice_59.php',
        'inspect_invoice_59.php',
        'simple_check.php',
        'simple_db_check.php',
        'test_seeder.php',
        'test_settings.php',
        'verify_after_seed.php',
        'verify_before_seed.php',
        'verify_cleanup.php',
        'run_check.bat',
        'backup-database.bat',
        'storage/debug_settings.json',
    ];

    foreach ($dangerousScripts as $script) {
        expect(file_exists(base_path($script)))->toBeFalse($script.' should not ship in production.');
    }

    $htaccess = file_get_contents(base_path('.htaccess'));

    expect($htaccess)->toContain('Require all denied')
        ->and($htaccess)->toContain('direct_query')
        ->and($htaccess)->toContain('diagnostic_report')
        ->and($htaccess)->toContain('check_.*')
        ->and($htaccess)->toContain('verify_.*');
});

test('sensitive admin mutations are not exposed through get routes', function () {
    $admin = phase57User(['role' => 'admin']);
    $booking = phase57Booking();

    $urls = [
        route('admin.bookings.cancel', $booking),
        route('admin.bookings.start', $booking),
        route('admin.invoices.refund', ['invoice' => 1]),
        route('admin.users.ban', $admin),
        route('admin.security-deposit.release', $booking),
        route('admin.security-deposit.charge', $booking),
    ];

    foreach ($urls as $url) {
        $response = $this->actingAs($admin)->get($url);

        expect($response->getStatusCode())->toBeIn([404, 405]);
    }
});

test('admin booking invoice download is read only', function () {
    $admin = phase57User(['role' => 'admin']);
    $booking = phase57Booking();
    phase57Invoice($booking);

    expect($booking->fresh()->invoiced_at)->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.bookings.invoice', $booking))
        ->assertOk();

    expect($booking->fresh()->invoiced_at)->toBeNull();
});

test('private evidence error responses do not leak filesystem paths', function () {
    Storage::fake('local');
    Storage::fake('public');

    $admin = phase57User(['role' => 'admin']);
    $booking = phase57Booking();

    $inspection = BookingInspection::create([
        'booking_id' => $booking->id,
        'type' => 'checkin',
        'mileage' => 10,
        'fuel_level' => 80,
        'has_damage' => false,
        'created_by' => $admin->id,
    ]);

    $photo = BookingPhoto::create([
        'booking_inspection_id' => $inspection->id,
        'path' => 'booking-evidence/inspections/missing.jpg',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.bookings.inspection.photos.show', $photo))
        ->assertNotFound()
        ->assertDontSee(storage_path(), false)
        ->assertDontSee(base_path(), false);
});

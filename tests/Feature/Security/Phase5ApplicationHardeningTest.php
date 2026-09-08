<?php

use App\Models\Booking;
use App\Models\BookingDamage;
use App\Models\BookingInspection;
use App\Models\BookingPhoto;
use App\Models\Car;
use App\Models\Insurance;
use App\Models\Location;
use App\Models\User;
use App\Services\BookingService;
use App\Services\SecurityDepositService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

function phase512User(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'is_banned' => false,
    ], $attributes));
}

function phase512Location(): Location
{
    return Location::factory()->create([
        'name' => 'Phase 5.12 Desk',
        'city' => 'Casablanca',
    ]);
}

function phase512Car(?Location $location = null, array $attributes = []): Car
{
    $location ??= phase512Location();

    return Car::factory()->create(array_merge([
        'location_id' => $location->id,
        'image' => 'phase512-car.jpg',
        'gallery' => null,
        'security_deposit_amount' => 0,
    ], $attributes));
}

function phase512Booking(?User $user = null): Booking
{
    $user ??= phase512User();
    $location = phase512Location();
    $car = phase512Car($location);

    return Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'security_deposit_amount' => 1000,
        'security_deposit_intent_id' => 'pi_phase512_deposit',
    ]);
}

function phase512CarPayload(Location $location, array $overrides = []): array
{
    return array_merge([
        'brand' => 'Renault',
        'model' => 'Clio',
        'year' => 2024,
        'type' => 'Economy',
        'transmission' => 'Manual',
        'fuel_type' => 'Petrol',
        'seats' => 5,
        'doors' => 4,
        'luggage' => 2,
        'price_per_day' => 300,
        'image' => UploadedFile::fake()->image('car.jpg')->size(256),
        'location_id' => $location->id,
        'is_available' => true,
        'security_deposit_amount' => 0,
    ], $overrides);
}

function phase512Insurance(): Insurance
{
    return Insurance::create([
        'name' => 'Phase 5.12 Protection',
        'type' => 'basic',
        'description' => 'Fixture insurance',
        'fixed_price' => 100,
        'max_coverage' => 1000,
        'deductible' => 100,
        'excess_fee' => 0,
        'features' => ['Fixture'],
        'is_active' => true,
        'sort_order' => 1,
    ]);
}

function phase512WebpUpload(string $name = 'car.webp'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'phase512-webp-');
    file_put_contents($path, base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AA/vuUAAA='));

    return new UploadedFile($path, $name, 'image/webp', null, true);
}

test('admin booking exceptions are logged but not exposed in browser errors', function () {
    $admin = phase512User(['role' => 'admin']);
    $booking = phase512Booking();

    $this->mock(BookingService::class, function ($mock): void {
        $mock->shouldReceive('confirmBooking')
            ->once()
            ->andThrow(new RuntimeException('SQLSTATE[HY000] internal database host secret'));
    });

    $response = $this->actingAs($admin)
        ->from(route('admin.bookings.show', $booking))
        ->post(route('admin.bookings.confirm', $booking));

    $response->assertRedirect(route('admin.bookings.show', $booking));
    $response->assertSessionHasErrors();

    $errors = implode(' ', session('errors')->all());
    expect($errors)->toContain('Unable to confirm this booking')
        ->not->toContain('SQLSTATE')
        ->not->toContain('internal database host secret');
});

test('security deposit admin json errors do not expose raw exception details', function () {
    $admin = phase512User(['role' => 'admin']);
    $booking = phase512Booking();
    $secretLikeFixture = 'sk_live_'.'example';

    $this->mock(SecurityDepositService::class, function ($mock): void {
        $mock->shouldReceive('releaseSecurityDeposit')
            ->once()
            ->andThrow(new RuntimeException('Stripe internal secret '.('sk_live_'.'example')));
    });

    $this->actingAs($admin)
        ->postJson(route('admin.security-deposit.release', $booking))
        ->assertStatus(500)
        ->assertJson([
            'success' => false,
            'error' => 'Unable to release this security deposit. Please try again or review the logs.',
        ])
        ->assertDontSee($secretLikeFixture, false);
});

test('trusted proxy configuration no longer trusts wildcard proxies by default', function () {
    $middleware = file_get_contents(app_path('Http/Middleware/TrustProxies.php'));

    expect($middleware)->toContain('TRUSTED_PROXIES')
        ->and($middleware)->not->toContain("protected \$proxies = '*'");

    $this->get('/')->assertOk();
});

test('public car uploads accept supported image types and store them on the public disk', function () {
    Storage::fake('public');

    $admin = phase512User(['role' => 'admin']);
    $location = phase512Location();

    $uploads = [
        'jpg' => UploadedFile::fake()->image('car.jpg')->size(256),
        'png' => UploadedFile::fake()->image('car.png')->size(256),
        'webp' => phase512WebpUpload(),
    ];

    foreach ($uploads as $suffix => $upload) {
        $this->actingAs($admin)
            ->post(route('admin.cars.store'), phase512CarPayload($location, [
                'brand' => 'Renault'.$suffix,
                'image' => $upload,
            ]))
            ->assertRedirect(route('admin.cars.index'));

        $car = Car::where('brand', 'Renault'.$suffix)->firstOrFail();
        Storage::disk('public')->assertExists('cars/'.$car->image);
    }
});

test('public car uploads reject executable svg unsupported oversized and excessive gallery files', function () {
    Storage::fake('public');

    $admin = phase512User(['role' => 'admin']);
    $location = phase512Location();

    $this->actingAs($admin)
        ->post(route('admin.cars.store'), phase512CarPayload($location, [
            'image' => UploadedFile::fake()->create('shell.php', 1, 'application/x-php'),
        ]))
        ->assertSessionHasErrors('image');

    $this->actingAs($admin)
        ->post(route('admin.cars.store'), phase512CarPayload($location, [
            'image' => UploadedFile::fake()->createWithContent('car.svg', '<svg><script>alert(1)</script></svg>'),
        ]))
        ->assertSessionHasErrors('image');

    $this->actingAs($admin)
        ->post(route('admin.cars.store'), phase512CarPayload($location, [
            'image' => UploadedFile::fake()->image('huge.jpg')->size(5121),
        ]))
        ->assertSessionHasErrors('image');

    $car = phase512Car($location, [
        'gallery' => array_fill(0, 9, 'existing.jpg'),
    ]);

    $payload = phase512CarPayload($location, [
        'image' => null,
        'brand' => $car->brand,
        'model' => $car->model,
        'gallery' => [
            UploadedFile::fake()->image('one.jpg')->size(100),
            UploadedFile::fake()->image('two.jpg')->size(100),
        ],
    ]);
    unset($payload['image']);

    $this->actingAs($admin)
        ->put(route('admin.cars.update', $car), $payload)
        ->assertSessionHasErrors('gallery');
});

test('legacy evidence migration dry run execute and repeat are safe', function () {
    Storage::fake('public');
    Storage::fake('local');

    $admin = phase512User(['role' => 'admin']);
    $booking = phase512Booking();

    $inspection = BookingInspection::create([
        'booking_id' => $booking->id,
        'type' => 'checkin',
        'mileage' => 100,
        'fuel_level' => 80,
        'has_damage' => false,
        'created_by' => $admin->id,
    ]);

    Storage::disk('public')->put('inspections/legacy.jpg', 'inspection bytes');
    Storage::disk('public')->put('damages/legacy.jpg', 'damage bytes');

    $photo = BookingPhoto::create([
        'booking_inspection_id' => $inspection->id,
        'path' => 'inspections/legacy.jpg',
    ]);

    $damage = BookingDamage::create([
        'booking_id' => $booking->id,
        'stage' => 'checkout',
        'part' => 'door',
        'type' => 'scratch',
        'description' => 'Door scratch',
        'estimated_cost' => 100,
        'is_chargeable' => true,
        'photos' => ['damages/legacy.jpg'],
    ]);

    Artisan::call('booking-evidence:migrate-private');
    expect(Artisan::output())->toContain('DRY-RUN complete')
        ->and($photo->fresh()->path)->toBe('inspections/legacy.jpg')
        ->and($damage->fresh()->photos)->toBe(['damages/legacy.jpg']);

    Artisan::call('booking-evidence:migrate-private', ['--execute' => true]);
    $photo = $photo->fresh();
    $damage = $damage->fresh();

    expect($photo->path)->toStartWith('booking-evidence/inspections/')
        ->and($damage->photos[0])->toStartWith('booking-evidence/damages/');

    Storage::disk('local')->assertExists($photo->path);
    Storage::disk('local')->assertExists($damage->photos[0]);
    Storage::disk('public')->assertExists('inspections/legacy.jpg');
    Storage::disk('public')->assertExists('damages/legacy.jpg');

    $inspectionPath = $photo->path;
    $damagePath = $damage->photos[0];

    expect(Artisan::call('booking-evidence:migrate-private', ['--execute' => true]))->toBe(0)
        ->and($photo->fresh()->path)->toBe($inspectionPath)
        ->and($damage->fresh()->photos[0])->toBe($damagePath);

    Storage::disk('local')->assertExists($inspectionPath);
    Storage::disk('local')->assertExists($damagePath);
});

test('legacy evidence migration delete flag removes only verified public evidence sources', function () {
    Storage::fake('public');
    Storage::fake('local');

    $admin = phase512User(['role' => 'admin']);
    $booking = phase512Booking();

    $inspection = BookingInspection::create([
        'booking_id' => $booking->id,
        'type' => 'checkin',
        'mileage' => 100,
        'fuel_level' => 80,
        'has_damage' => false,
        'created_by' => $admin->id,
    ]);

    Storage::disk('public')->put('inspections/delete-after-verify.jpg', 'inspection bytes');
    Storage::disk('public')->put('damages/delete-after-verify.jpg', 'damage bytes');
    Storage::disk('public')->put('cars/public-car.jpg', 'car bytes');

    $photo = BookingPhoto::create([
        'booking_inspection_id' => $inspection->id,
        'path' => 'inspections/delete-after-verify.jpg',
    ]);

    $damage = BookingDamage::create([
        'booking_id' => $booking->id,
        'stage' => 'checkout',
        'part' => 'door',
        'type' => 'scratch',
        'description' => 'Door scratch',
        'estimated_cost' => 100,
        'is_chargeable' => true,
        'photos' => ['damages/delete-after-verify.jpg'],
    ]);

    Artisan::call('booking-evidence:migrate-private', [
        '--execute' => true,
        '--delete-public-after-verify' => true,
    ]);

    $photo = $photo->fresh();
    $damage = $damage->fresh();

    Storage::disk('local')->assertExists($photo->path);
    Storage::disk('local')->assertExists($damage->photos[0]);
    Storage::disk('public')->assertMissing('inspections/delete-after-verify.jpg');
    Storage::disk('public')->assertMissing('damages/delete-after-verify.jpg');
    Storage::disk('public')->assertExists('cars/public-car.jpg');
});

test('insurance selection post route is throttled without requiring authentication', function () {
    $car = phase512Car();
    $insurance = phase512Insurance();
    $key = '127.0.0.1';

    RateLimiter::clear($key);

    expect(Route::getRoutes()
        ->match(Request::create(route('insurance.store', $car, false), 'POST'))
        ->gatherMiddleware())->toContain('throttle:phase5-insurance');

    for ($attempt = 0; $attempt < 30; $attempt++) {
        $this->post(route('insurance.store', $car), [
            'insurance_id' => $insurance->id,
        ])->assertRedirect(route('cars.details', $car));
    }

    $this->post(route('insurance.store', $car), [
        'insurance_id' => $insurance->id,
    ])->assertStatus(429);

    RateLimiter::clear($key);
});

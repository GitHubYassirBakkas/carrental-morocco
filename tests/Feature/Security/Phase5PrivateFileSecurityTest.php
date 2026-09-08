<?php

use App\Models\Booking;
use App\Models\BookingDamage;
use App\Models\BookingInspection;
use App\Models\BookingPhoto;
use App\Models\Car;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function phase56User(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'is_banned' => false,
    ], $attributes));
}

function phase56Location(): Location
{
    return Location::create([
        'name' => 'Phase 5.6 Evidence Desk',
        'address' => '56 Storage Street',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000056',
        'email' => 'phase56@example.test',
        'opening_time' => '08:00',
        'closing_time' => '20:00',
        'is_active' => true,
    ]);
}

function phase56Car(?Location $location = null): Car
{
    $location ??= phase56Location();

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
        'image' => 'test-car.jpg',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => 0,
    ]);
}

function phase56Booking(?User $user = null): Booking
{
    $user ??= phase56User();
    $location = phase56Location();
    $car = phase56Car($location);

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
        'status' => Booking::STATUS_ACTIVE,
        'advance_payment_amount' => 300,
        'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PAID,
        'advance_payment_paid_at' => now(),
        'advance_payment_due_at' => now()->addDay(),
        'security_deposit_amount' => 0,
        'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_PENDING,
    ]);
}

function phase56Inspection(?Booking $booking = null): BookingInspection
{
    $booking ??= phase56Booking();

    return BookingInspection::create([
        'booking_id' => $booking->id,
        'type' => 'checkin',
        'mileage' => 100,
        'fuel_level' => 80,
        'has_damage' => false,
        'created_by' => phase56User(['role' => 'admin'])->id,
    ]);
}

test('guest and normal customer cannot access private inspection photo', function () {
    Storage::fake('local');

    $customer = phase56User();
    $booking = phase56Booking($customer);
    $inspection = phase56Inspection($booking);

    Storage::disk('local')->put('booking-evidence/inspections/photo.jpg', 'private image');

    $photo = BookingPhoto::create([
        'booking_inspection_id' => $inspection->id,
        'path' => 'booking-evidence/inspections/photo.jpg',
    ]);

    $this->get(route('admin.bookings.inspection.photos.show', $photo))
        ->assertRedirect(route('login'));

    $this->actingAs($customer)
        ->get(route('admin.bookings.inspection.photos.show', $photo))
        ->assertForbidden();
});

test('normal user cannot access another users booking damage photo', function () {
    Storage::fake('local');

    $userA = phase56User();
    $userB = phase56User();
    $booking = phase56Booking($userB);

    Storage::disk('local')->put('booking-evidence/damages/damage.jpg', 'private damage');

    $damage = BookingDamage::create([
        'booking_id' => $booking->id,
        'stage' => 'checkout',
        'part' => 'door',
        'type' => 'scratch',
        'description' => 'Door scratch',
        'estimated_cost' => 100,
        'is_chargeable' => true,
        'photos' => ['booking-evidence/damages/damage.jpg'],
    ]);

    $this->actingAs($userA)
        ->get(route('admin.bookings.damages.photos.show', [$damage, 0]))
        ->assertForbidden();
});

test('authorized admin can access private inspection and damage photos', function () {
    Storage::fake('local');

    $admin = phase56User(['role' => 'admin']);
    $booking = phase56Booking();
    $inspection = phase56Inspection($booking);

    Storage::disk('local')->put('booking-evidence/inspections/photo.jpg', 'private inspection');
    Storage::disk('local')->put('booking-evidence/damages/damage.jpg', 'private damage');

    $photo = BookingPhoto::create([
        'booking_inspection_id' => $inspection->id,
        'path' => 'booking-evidence/inspections/photo.jpg',
    ]);

    $damage = BookingDamage::create([
        'booking_id' => $booking->id,
        'stage' => 'checkout',
        'part' => 'door',
        'type' => 'scratch',
        'description' => 'Door scratch',
        'estimated_cost' => 100,
        'is_chargeable' => true,
        'photos' => ['booking-evidence/damages/damage.jpg'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.bookings.inspection.photos.show', $photo))
        ->assertOk()
        ->assertHeader('x-content-type-options', 'nosniff')
        ->assertHeader('cache-control', 'max-age=0, no-store, private');

    $this->actingAs($admin)
        ->get(route('admin.bookings.damages.photos.show', [$damage, 0]))
        ->assertOk()
        ->assertHeader('x-content-type-options', 'nosniff')
        ->assertHeader('cache-control', 'max-age=0, no-store, private');
});

test('inspection photo upload stores new files on private local disk', function () {
    Storage::fake('local');
    Storage::fake('public');

    $admin = phase56User(['role' => 'admin']);
    $inspection = phase56Inspection();

    $this->actingAs($admin)
        ->post(route('admin.bookings.inspection.photos.store', $inspection), [
            'photos' => [UploadedFile::fake()->image('inspection.jpg')->size(256)],
            'type' => 'front',
            'notes' => 'Fixture upload',
        ])
        ->assertSessionHas('success');

    $photo = $inspection->photos()->firstOrFail();

    expect($photo->path)->toStartWith('booking-evidence/inspections/');
    expect(basename($photo->path))->not->toContain('inspection');
    Storage::disk('local')->assertExists($photo->path);
    Storage::disk('public')->assertMissing($photo->path);
});

test('damage photo upload stores new files on private local disk', function () {
    Storage::fake('local');
    Storage::fake('public');

    $admin = phase56User(['role' => 'admin']);
    $booking = phase56Booking();

    $this->actingAs($admin)
        ->post(route('admin.bookings.damages.store', $booking), [
            'stage' => 'checkout',
            'part' => 'door',
            'type' => 'scratch',
            'description' => 'Door scratch',
            'estimated_cost' => 100,
            'is_chargeable' => true,
            'photos' => [UploadedFile::fake()->image('damage.jpg')->size(256)],
        ])
        ->assertSessionHas('success');

    $damage = $booking->damages()->firstOrFail();

    expect($damage->photos[0])->toStartWith('booking-evidence/damages/');
    expect(basename($damage->photos[0]))->not->toContain('damage');
    Storage::disk('local')->assertExists($damage->photos[0]);
    Storage::disk('public')->assertMissing($damage->photos[0]);
});

test('invalid oversized and dangerous inspection uploads are rejected', function () {
    Storage::fake('local');

    $admin = phase56User(['role' => 'admin']);
    $inspection = phase56Inspection();

    $this->actingAs($admin)
        ->post(route('admin.bookings.inspection.photos.store', $inspection), [
            'photos' => [UploadedFile::fake()->create('shell.php', 1, 'application/x-php')],
        ])
        ->assertSessionHasErrors('photos.0');

    $this->actingAs($admin)
        ->post(route('admin.bookings.inspection.photos.store', $inspection), [
            'photos' => [UploadedFile::fake()->image('huge.jpg')->size(5121)],
        ])
        ->assertSessionHasErrors('photos.0');

    $this->actingAs($admin)
        ->post(route('admin.bookings.inspection.photos.store', $inspection), [
            'photos' => [UploadedFile::fake()->create('proof.jpg.php', 1, 'text/plain')],
        ])
        ->assertSessionHasErrors('photos.0');

    expect($inspection->photos()->count())->toBe(0);
});

test('private photo route rejects traversal and missing files safely', function () {
    Storage::fake('local');
    Storage::fake('public');

    $admin = phase56User(['role' => 'admin']);
    $inspection = phase56Inspection();

    $traversalPhoto = BookingPhoto::create([
        'booking_inspection_id' => $inspection->id,
        'path' => '../../.env',
    ]);

    $missingPhoto = BookingPhoto::create([
        'booking_inspection_id' => $inspection->id,
        'path' => 'booking-evidence/inspections/missing.jpg',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.bookings.inspection.photos.show', $traversalPhoto))
        ->assertNotFound()
        ->assertDontSee(base_path(), false);

    $this->actingAs($admin)
        ->get(route('admin.bookings.inspection.photos.show', $missingPhoto))
        ->assertNotFound()
        ->assertDontSee(storage_path(), false);

    $this->actingAs($admin)
        ->get('/admin/inspections/photos/../../.env')
        ->assertNotFound();
});

test('legacy public evidence path is served only through authorized route', function () {
    Storage::fake('public');
    Storage::fake('local');

    $admin = phase56User(['role' => 'admin']);
    $inspection = phase56Inspection();

    Storage::disk('public')->put('inspections/legacy.jpg', 'legacy image');

    $photo = BookingPhoto::create([
        'booking_inspection_id' => $inspection->id,
        'path' => 'inspections/legacy.jpg',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.bookings.inspection.photos.show', $photo))
        ->assertOk()
        ->assertHeader('cache-control', 'max-age=0, no-store, private');
});

test('legacy public damage path is served only through authorized route', function () {
    Storage::fake('public');
    Storage::fake('local');

    $admin = phase56User(['role' => 'admin']);
    $booking = phase56Booking();

    Storage::disk('public')->put('damages/legacy.jpg', 'legacy damage');

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

    $this->get(route('admin.bookings.damages.photos.show', [$damage, 0]))
        ->assertRedirect(route('login'));

    $this->actingAs($booking->user)
        ->get(route('admin.bookings.damages.photos.show', [$damage, 0]))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.bookings.damages.photos.show', [$damage, 0]))
        ->assertOk()
        ->assertHeader('cache-control', 'max-age=0, no-store, private');
});

test('evidence controller rejects non evidence paths and arbitrary identifiers', function () {
    Storage::fake('public');
    Storage::fake('local');

    $admin = phase56User(['role' => 'admin']);
    $inspection = phase56Inspection();
    $booking = $inspection->booking;

    Storage::disk('public')->put('cars/public-car.jpg', 'car image');
    Storage::disk('local')->put('private/customer-documents/1/identity/front/doc.jpg', 'driver document');

    $publicCarPath = BookingPhoto::create([
        'booking_inspection_id' => $inspection->id,
        'path' => 'cars/public-car.jpg',
    ]);

    $privateDocumentPath = BookingPhoto::create([
        'booking_inspection_id' => $inspection->id,
        'path' => 'private/customer-documents/1/identity/front/doc.jpg',
    ]);

    $damage = BookingDamage::create([
        'booking_id' => $booking->id,
        'stage' => 'checkout',
        'part' => 'door',
        'type' => 'scratch',
        'description' => 'Door scratch',
        'estimated_cost' => 100,
        'is_chargeable' => true,
        'photos' => ['cars/public-car.jpg'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.bookings.inspection.photos.show', $publicCarPath))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('admin.bookings.inspection.photos.show', $privateDocumentPath))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('admin.bookings.damages.photos.show', [$damage, 0]))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('admin.bookings.damages.photos.show', [$damage, 1]))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get('/admin/inspections/photos/999999')
        ->assertNotFound();
});

test('public car image upload flow still stores marketing images on public disk', function () {
    Storage::fake('public');

    $admin = phase56User(['role' => 'admin']);
    $location = phase56Location();

    $this->actingAs($admin)
        ->post(route('admin.cars.store'), [
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
        ])
        ->assertRedirect(route('admin.cars.index'));

    $car = Car::where('brand', 'Renault')->where('model', 'Clio')->firstOrFail();

    Storage::disk('public')->assertExists('cars/'.$car->image);
});

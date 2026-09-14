<?php

use App\Models\Car;
use App\Models\EmailLog;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function portfolioAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function portfolioLocation(array $attributes = []): Location
{
    return Location::factory()->create(array_merge([
        'name' => 'Meknes Agency',
        'address' => 'Avenue Mohammed V',
        'city' => 'Meknes',
        'country' => 'Morocco',
        'postal_code' => '50000',
        'phone' => '+212535000000',
        'email' => 'meknes@example.test',
        'opening_time' => '08:00:00',
        'closing_time' => '20:00:00',
        'is_active' => true,
    ], $attributes));
}

function portfolioCarPayload(Location $location, array $overrides = []): array
{
    return array_merge([
        'brand' => 'Renault',
        'model' => 'Clio',
        'year' => 2024,
        'type' => 'Economy',
        'transmission' => 'Manual',
        'fuel_type' => 'Diesel',
        'seats' => 5,
        'doors' => 4,
        'luggage' => 4,
        'mileage' => 42000,
        'price_per_day' => 350,
        'image' => UploadedFile::fake()->image('clio.jpg')->size(256),
        'location_id' => $location->id,
        'is_available' => true,
        'security_deposit_amount' => 0,
    ], $overrides);
}

function portfolioLocationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Meknes Agency',
        'address' => 'Avenue Mohammed V',
        'city' => 'Meknes',
        'country' => 'Morocco',
        'postal_code' => '50000',
        'phone' => '+212535000000',
        'email' => 'meknes@example.test',
        'opening_time' => '08:00',
        'closing_time' => '20:00',
        'latitude' => 33.8935,
        'longitude' => -5.5473,
        'is_active' => true,
    ], $overrides);
}

test('admin car mileage validation rejects negative and excessive values', function () {
    Storage::fake('public');

    $admin = portfolioAdmin();
    $location = portfolioLocation();

    $this->actingAs($admin)
        ->post(route('admin.cars.store'), portfolioCarPayload($location, [
            'mileage' => -1,
        ]))
        ->assertSessionHasErrors('mileage');

    $this->actingAs($admin)
        ->post(route('admin.cars.store'), portfolioCarPayload($location, [
            'image' => UploadedFile::fake()->image('clio-2.jpg')->size(256),
            'mileage' => 2000001,
        ]))
        ->assertSessionHasErrors('mileage');
});

test('admin can store and update car mileage separately from luggage', function () {
    Storage::fake('public');

    $admin = portfolioAdmin();
    $location = portfolioLocation();

    $this->actingAs($admin)
        ->post(route('admin.cars.store'), portfolioCarPayload($location))
        ->assertRedirect(route('admin.cars.index'));

    $car = Car::where('brand', 'Renault')->where('model', 'Clio')->firstOrFail();

    expect($car->mileage)->toBe(42000)
        ->and($car->luggage)->toBe(4);

    $updatePayload = portfolioCarPayload($location, [
        'mileage' => 55000,
        'luggage' => 3,
    ]);
    unset($updatePayload['image']);

    $this->actingAs($admin)
        ->put(route('admin.cars.update', $car), $updatePayload)
        ->assertRedirect(route('admin.cars.index'));

    $car->refresh();

    expect($car->mileage)->toBe(55000)
        ->and($car->luggage)->toBe(3);
});

test('public car details display real mileage only when available', function () {
    $location = portfolioLocation();
    $carWithMileage = Car::factory()->create([
        'location_id' => $location->id,
        'brand' => 'Renault',
        'model' => 'Clio',
        'mileage' => 42000,
        'luggage' => 4,
    ]);
    $carWithoutMileage = Car::factory()->create([
        'location_id' => $location->id,
        'mileage' => null,
    ]);

    $this->get(route('cars.show', $carWithMileage))
        ->assertOk()
        ->assertSee('Mileage')
        ->assertSee('42,000 km')
        ->assertSee('Luggage Bags')
        ->assertSee('4');

    $this->get(route('cars.show', $carWithoutMileage))
        ->assertOk()
        ->assertDontSee('Mileage');
});

test('admin location coordinates validate and store on locations', function () {
    $admin = portfolioAdmin();

    $this->actingAs($admin)
        ->post(route('admin.locations.store'), portfolioLocationPayload([
            'latitude' => 91,
            'longitude' => -5.5473,
        ]))
        ->assertSessionHasErrors('latitude');

    $this->actingAs($admin)
        ->post(route('admin.locations.store'), portfolioLocationPayload())
        ->assertRedirect(route('admin.locations.index'));

    $location = Location::where('name', 'Meknes Agency')->firstOrFail();

    expect((float) $location->latitude)->toBe(33.8935)
        ->and((float) $location->longitude)->toBe(-5.5473);
});

test('public car details render map with coordinates and fallback without coordinates', function () {
    $located = portfolioLocation([
        'name' => 'Meknes Map Agency',
        'latitude' => 33.8935,
        'longitude' => -5.5473,
    ]);
    $missing = portfolioLocation([
        'name' => 'No Map Agency',
        'latitude' => null,
        'longitude' => null,
    ]);

    $carWithMap = Car::factory()->create(['location_id' => $located->id]);
    $carWithoutMap = Car::factory()->create(['location_id' => $missing->id]);

    $this->get(route('cars.show', $carWithMap))
        ->assertOk()
        ->assertSee('id="cd-agency-map"', false)
        ->assertSee('data-lat="33.89350000"', false)
        ->assertSee('data-lng="-5.54730000"', false)
        ->assertSee('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', false)
        ->assertSee('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', false);

    $this->get(route('cars.show', $carWithoutMap))
        ->assertOk()
        ->assertSee('Map location not available')
        ->assertDontSee('id="cd-agency-map"', false);
});

test('admin email log detail renders sandboxed preview without raw html injection', function () {
    $admin = portfolioAdmin();
    $emailLog = EmailLog::create([
        'to' => 'customer@example.test',
        'subject' => 'Booking confirmed',
        'content' => '<!DOCTYPE html><html><body><h1>Booking confirmed</h1><script>alert("x")</script></body></html>',
        'status' => 'sent',
        'message_id' => 'msg_portfolio_polish',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.email-logs.show', $emailLog))
        ->assertOk()
        ->assertSee('customer@example.test')
        ->assertSee('Booking confirmed')
        ->assertSee('msg_portfolio_polish')
        ->assertSee('title="Rendered email preview"', false)
        ->assertSee('sandbox=""', false)
        ->assertSee('srcdoc=', false)
        ->assertSee('&lt;!DOCTYPE html&gt;', false)
        ->assertDontSee('&amp;lt;!DOCTYPE html&amp;gt;', false)
        ->assertSee('View Source')
        ->assertSee('&lt;h1&gt;Booking confirmed&lt;/h1&gt;', false);

    $html = $response->getContent();

    expect($html)->not->toContain('<h1>Booking confirmed</h1>')
        ->and($html)->not->toContain('<script>alert("x")</script>');
});

test('admin email log preview wraps plain text safely for iframe srcdoc', function () {
    $admin = portfolioAdmin();
    $emailLog = EmailLog::create([
        'to' => 'customer@example.test',
        'subject' => 'Plain text notice',
        'content' => "Hello <Customer>\nYour booking is ready.",
        'status' => 'sent',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.email-logs.show', $emailLog))
        ->assertOk()
        ->assertSee('title="Rendered email preview"', false)
        ->assertSee('white-space:pre-wrap', false)
        ->assertSee('Hello &amp;lt;Customer&amp;gt;', false)
        ->assertDontSee('Hello <Customer>', false);

    expect($response->getContent())->not->toContain('<Customer>');
});

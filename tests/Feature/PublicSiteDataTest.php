<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Location;
use App\Models\Setting;
use App\Models\User;

function publicDataLocation(array $attributes = []): Location
{
    return Location::factory()->create(array_merge([
        'name' => 'Meknes Branch',
        'address' => 'Avenue Mohammed V, Meknes',
        'city' => 'Meknes',
        'country' => 'Morocco',
        'phone' => '+212 5 35 52 00 00',
        'email' => 'meknes@example.test',
        'opening_time' => '08:00:00',
        'closing_time' => '18:00:00',
        'is_active' => true,
    ], $attributes));
}

function publicDataCar(array $attributes = []): Car
{
    $location = $attributes['location'] ?? publicDataLocation();
    unset($attributes['location']);

    return Car::factory()->create(array_merge([
        'brand' => 'Dacia',
        'model' => 'Sandero',
        'type' => 'Economy',
        'transmission' => 'Manual',
        'is_available' => true,
        'location_id' => $location->id,
    ], $attributes));
}

function publicDataBrandSection(string $html): string
{
    preg_match('/<section class="home-brands">.*?<\/section>/s', $html, $matches);

    return $matches[0] ?? '';
}

function publicDataSetSetting(string $key, string $value): void
{
    Setting::query()->updateOrCreate(
        ['key' => $key],
        [
            'value' => $value,
            'type' => 'text',
            'group' => 'general',
            'autoload' => true,
            'is_public' => true,
        ],
    );
}

test('homepage brand slider displays distinct available database brands only', function () {
    $location = publicDataLocation();

    publicDataCar(['brand' => 'Dacia', 'model' => 'Sandero', 'location' => $location]);
    publicDataCar(['brand' => 'Dacia', 'model' => 'Logan', 'location' => $location]);
    publicDataCar(['brand' => 'Audi', 'model' => 'A3', 'location' => $location]);
    publicDataCar(['brand' => '   ', 'model' => 'Blank Brand', 'location' => $location]);
    publicDataCar(['brand' => 'Ferrari', 'model' => 'Unavailable', 'is_available' => false, 'location' => $location]);

    $html = $this->get(route('home'))->assertOk()->getContent();
    $brands = publicDataBrandSection($html);

    expect(substr_count($brands, '>Dacia<'))->toBe(1)
        ->and(substr_count($brands, '>Audi<'))->toBe(1)
        ->and($brands)->not->toContain('Ferrari')
        ->and($brands)->not->toContain('Bentley')
        ->and($brands)->not->toContain('Blank Brand');
});

test('new available car brand appears automatically in homepage brand slider', function () {
    $location = publicDataLocation();

    publicDataCar(['brand' => 'Dacia', 'location' => $location]);

    $firstBrands = publicDataBrandSection($this->get(route('home'))->assertOk()->getContent());
    expect($firstBrands)->not->toContain('Peugeot');

    publicDataCar(['brand' => 'Peugeot', 'model' => '208', 'location' => $location]);

    $updatedBrands = publicDataBrandSection($this->get(route('home'))->assertOk()->getContent());
    expect($updatedBrands)->toContain('Peugeot');
});

test('homepage brand links point to cars listing brand query', function () {
    publicDataCar(['brand' => 'Dacia']);

    $brands = publicDataBrandSection($this->get(route('home'))->assertOk()->getContent());

    expect($brands)->toContain('href="'.route('cars.index', ['brand' => 'Dacia']).'"');
});

test('cars brand filter returns exact matching available cars and preserves type filtering', function () {
    $location = publicDataLocation();

    publicDataCar(['brand' => 'Dacia', 'model' => 'Logan', 'type' => 'Sedan', 'location' => $location]);
    publicDataCar(['brand' => 'Dacia', 'model' => 'Duster', 'type' => 'SUV', 'location' => $location]);
    publicDataCar(['brand' => 'Audi', 'model' => 'A3', 'type' => 'Sedan', 'location' => $location]);

    $this->get(route('cars.index', ['brand' => 'Dacia']))
        ->assertOk()
        ->assertSee('Dacia Logan')
        ->assertSee('Dacia Duster')
        ->assertDontSee('Audi A3');

    $this->get(route('cars.index', ['brand' => 'Dacia', 'type' => 'Sedan']))
        ->assertOk()
        ->assertSee('Dacia Logan')
        ->assertDontSee('Dacia Duster')
        ->assertDontSee('Audi A3');
});

test('public stats use real available cars active locations and completed unique customers', function () {
    $location = publicDataLocation();
    publicDataLocation(['city' => 'Casablanca', 'name' => 'Casablanca Branch', 'is_active' => false]);

    $dacia = publicDataCar(['brand' => 'Dacia', 'model' => 'Sandero', 'location' => $location]);
    $audi = publicDataCar(['brand' => 'Audi', 'model' => 'A3', 'location' => $location]);
    publicDataCar(['brand' => 'BMW', 'model' => 'Hidden', 'is_available' => false, 'location' => $location]);

    $firstCustomer = User::factory()->create();
    $secondCustomer = User::factory()->create();
    $pendingCustomer = User::factory()->create();

    Booking::factory()->create(['user_id' => $firstCustomer->id, 'car_id' => $dacia->id, 'pickup_location_id' => $location->id, 'dropoff_location_id' => $location->id, 'status' => Booking::STATUS_COMPLETED]);
    Booking::factory()->create(['user_id' => $firstCustomer->id, 'car_id' => $audi->id, 'pickup_location_id' => $location->id, 'dropoff_location_id' => $location->id, 'status' => Booking::STATUS_COMPLETED]);
    Booking::factory()->create(['user_id' => $secondCustomer->id, 'car_id' => $dacia->id, 'pickup_location_id' => $location->id, 'dropoff_location_id' => $location->id, 'status' => Booking::STATUS_COMPLETED]);
    Booking::factory()->create(['user_id' => $pendingCustomer->id, 'car_id' => $dacia->id, 'pickup_location_id' => $location->id, 'dropoff_location_id' => $location->id, 'status' => Booking::STATUS_PENDING]);

    $home = $this->get(route('home'))->assertOk()->getContent();
    $about = $this->get(route('about'))->assertOk()->getContent();

    expect($home)->toContain('>2</strong><span>Cars Available</span>')
        ->and($home)->toContain('>2</strong><span>Happy Customers</span>')
        ->and($home)->toContain('>1</strong><span>Cities Covered</span>')
        ->and($about)->toContain('<span class="hstat-num">2</span>')
        ->and($about)->toContain('<span class="hstat-num">1</span>')
        ->and($home)->not->toContain('500+')
        ->and($home)->not->toContain('10K+')
        ->and($home)->not->toContain('12 Moroccan cities')
        ->and($about)->not->toContain('500+')
        ->and($about)->not->toContain('10K+')
        ->and($about)->not->toContain('12 Moroccan cities');
});

test('active Meknes location is public while inactive Casablanca is not coverage', function () {
    $location = publicDataLocation(['city' => 'Meknes']);
    publicDataLocation(['city' => 'Casablanca', 'name' => 'Casablanca Branch', 'is_active' => false]);
    publicDataCar(['location' => $location]);

    $home = $this->get(route('home'))->assertOk()->getContent();
    $about = $this->get(route('about'))->assertOk()->getContent();

    expect($home)->toContain('Meknes')
        ->and($home)->not->toContain('Casablanca')
        ->and($about)->toContain('Meknes')
        ->and($about)->not->toContain('Casablanca');
});

test('contact page uses settings first and falls back to primary active location', function () {
    $location = publicDataLocation([
        'address' => 'Meknes active address',
        'phone' => '+212 5 35 52 00 00',
        'email' => 'meknes@example.test',
    ]);

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('Meknes active address')
        ->assertSee('+212 5 35 52 00 00')
        ->assertSee('meknes@example.test');

    publicDataSetSetting('site_phone', '+212 5 35 00 11 22');
    publicDataSetSetting('site_email', 'office@example.test');
    publicDataSetSetting('site_address', 'Configured Meknes Office');

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('Configured Meknes Office')
        ->assertSee('+212 5 35 00 11 22')
        ->assertSee('office@example.test')
        ->assertDontSee($location->email);
});

test('footer keeps real routes and removes placeholders newsletter and missing policy links', function () {
    publicDataLocation(['city' => 'Meknes', 'address' => 'Meknes active address']);
    publicDataCar();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('href="'.route('home').'"', false)
        ->assertSee('href="'.route('cars.index').'"', false)
        ->assertSee('href="'.route('about').'"', false)
        ->assertSee('href="'.route('contact').'"', false)
        ->assertSee('Meknes active address')
        ->assertDontSee('href="#"', false)
        ->assertDontSee('Newsletter')
        ->assertDontSee('Subscribe')
        ->assertDontSee('FAQ')
        ->assertSee('href="'.route('legal.terms').'"', false)
        ->assertSee('href="'.route('legal.privacy').'"', false)
        ->assertSee('href="'.route('legal.notice').'"', false)
        ->assertDontSee('MESSAGES.HOME-BRANDS_LABEL');
});

test('about page removes fake business history and team content', function () {
    publicDataLocation(['city' => 'Meknes']);
    publicDataCar();

    $this->get(route('about'))
        ->assertOk()
        ->assertDontSee('Est. 2018')
        ->assertDontSee('since 2018')
        ->assertDontSee('Founded in Casablanca')
        ->assertDontSee('Youssef Amrani')
        ->assertDontSee('Sara El Fassi')
        ->assertDontSee('Karim Benali')
        ->assertDontSee('Leila Rachidi')
        ->assertDontSee('Founder & CEO')
        ->assertDontSee('12 Moroccan cities');
});

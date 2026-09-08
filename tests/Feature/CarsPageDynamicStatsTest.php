<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Location;

function carsStatsLocation(array $attributes = []): Location
{
    return Location::factory()->create(array_merge([
        'name' => 'Meknes Branch',
        'address' => 'Avenue Mohammed V, Meknes',
        'city' => 'Meknes',
        'country' => 'Morocco',
        'is_active' => true,
    ], $attributes));
}

function carsStatsCar(Location $location, array $attributes = []): Car
{
    return Car::factory()->create(array_merge([
        'brand' => 'Dacia',
        'model' => 'Sandero',
        'type' => 'Economy',
        'transmission' => 'Manual',
        'fuel_type' => 'Petrol',
        'location_id' => $location->id,
        'is_available' => true,
    ], $attributes));
}

function carsHeroStatsHtml(string $html): string
{
    preg_match('/<div class="hero-stats">.*?<\/div>\s*<\/div>\s*<\/div>/s', $html, $matches);

    return $matches[0] ?? '';
}

test('cars page hero shows real available cars count and no fake static claims', function () {
    $location = carsStatsLocation();

    foreach (range(1, 4) as $index) {
        carsStatsCar($location, [
            'brand' => $index <= 2 ? 'Dacia' : 'Renault',
            'model' => 'Model '.$index,
        ]);
    }

    carsStatsCar($location, [
        'brand' => 'BMW',
        'model' => 'Unavailable',
        'is_available' => false,
    ]);

    $stats = carsHeroStatsHtml($this->get(route('cars.index'))->assertOk()->getContent());

    expect($stats)->toContain('<strong>4</strong><span>Results Found</span>')
        ->and($stats)->toContain('<strong>4</strong><span>Cars Available</span>')
        ->and($stats)->toContain('<strong>Support</strong><span>Customer Support</span>')
        ->and($stats)->not->toContain('500+')
        ->and($stats)->not->toContain('24/7');
});

test('cars page filtered results stat uses paginator total while available cars stat stays global', function () {
    $location = carsStatsLocation();

    foreach (range(1, 11) as $index) {
        carsStatsCar($location, [
            'brand' => 'Dacia',
            'model' => 'Dacia '.$index,
        ]);
    }

    foreach (range(1, 3) as $index) {
        carsStatsCar($location, [
            'brand' => 'Audi',
            'model' => 'Audi '.$index,
        ]);
    }

    $stats = carsHeroStatsHtml($this->get(route('cars.index', ['brand' => 'Dacia']))->assertOk()->getContent());

    expect($stats)->toContain('<strong>11</strong><span>Results Found</span>')
        ->and($stats)->toContain('<strong>14</strong><span>Cars Available</span>')
        ->and($stats)->not->toContain('<strong>9</strong><span>Results Found</span>');
});

test('cars page filters still affect results found without breaking brand filtering', function () {
    $location = carsStatsLocation();

    carsStatsCar($location, ['brand' => 'Dacia', 'model' => 'Logan', 'type' => 'Sedan']);
    carsStatsCar($location, ['brand' => 'Dacia', 'model' => 'Duster', 'type' => 'SUV']);
    carsStatsCar($location, ['brand' => 'Audi', 'model' => 'A3', 'type' => 'Sedan']);

    $response = $this->get(route('cars.index', [
        'brand' => 'Dacia',
        'type' => ['Sedan'],
    ]))->assertOk();

    $stats = carsHeroStatsHtml($response->getContent());

    expect($stats)->toContain('<strong>1</strong><span>Results Found</span>')
        ->and($stats)->toContain('<strong>3</strong><span>Cars Available</span>');

    $response->assertSee('Dacia Logan')
        ->assertDontSee('Dacia Duster')
        ->assertDontSee('Audi A3');
});

test('cars page date availability filter affects results found', function () {
    $location = carsStatsLocation();
    $available = carsStatsCar($location, ['brand' => 'Dacia', 'model' => 'Free']);
    $booked = carsStatsCar($location, ['brand' => 'Dacia', 'model' => 'Booked']);

    Booking::factory()->create([
        'car_id' => $booked->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'start_date' => now()->addDays(5)->toDateString(),
        'end_date' => now()->addDays(7)->toDateString(),
        'status' => Booking::STATUS_CONFIRMED,
    ]);

    $response = $this->get(route('cars.index', [
        'pickup_date' => now()->addDays(6)->toDateString(),
        'return_date' => now()->addDays(8)->toDateString(),
    ]))->assertOk();

    $stats = carsHeroStatsHtml($response->getContent());

    expect($stats)->toContain('<strong>1</strong><span>Results Found</span>')
        ->and($stats)->toContain('<strong>2</strong><span>Cars Available</span>');

    $response->assertSee($available->brand.' '.$available->model)
        ->assertDontSee($booked->brand.' '.$booked->model);
});

test('cars page hero stats labels render in english french and arabic', function (string $locale, string $results, string $cars, string $supportValue, string $supportLabel) {
    $location = carsStatsLocation();
    carsStatsCar($location);

    $stats = carsHeroStatsHtml(
        $this->withSession(['locale' => $locale])
            ->get(route('cars.index'))
            ->assertOk()
            ->getContent()
    );

    expect($stats)->toContain($results)
        ->and($stats)->toContain($cars)
        ->and($stats)->toContain($supportValue)
        ->and($stats)->toContain($supportLabel)
        ->and($stats)->not->toContain('24/7')
        ->and($stats)->not->toContain('500+');
})->with([
    ['en', 'Results Found', 'Cars Available', 'Support', 'Customer Support'],
    ['fr', 'Résultats trouvés', 'Voitures disponibles', 'Assistance', 'Service client'],
    ['ar', 'النتائج المتاحة', 'السيارات المتاحة', 'الدعم', 'دعم العملاء'],
]);

test('cars index and details do not contain fake cars page business claims', function () {
    $location = carsStatsLocation();
    $car = carsStatsCar($location);

    $index = $this->get(route('cars.index'))->assertOk()->getContent();
    $details = $this->get(route('cars.show', $car))->assertOk()->getContent();

    expect($index)->not->toContain('500+')
        ->and($index)->not->toContain('24/7')
        ->and($details)->not->toContain('500+')
        ->and($details)->not->toContain('10K+')
        ->and($details)->not->toContain('24/7');
});

<?php

use App\Models\Car;
use App\Models\Location;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

function homepageCar(array $attributes = []): Car
{
    $location = $attributes['location'] ?? Location::factory()->create([
        'is_active' => true,
    ]);

    unset($attributes['location']);

    return Car::factory()->create(array_merge([
        'brand' => 'Test',
        'model' => 'Car',
        'type' => 'Sedan',
        'image' => 'test-car.jpg',
        'is_available' => true,
        'location_id' => $location->id,
    ], $attributes));
}

test('homepage displays available database cars', function () {
    homepageCar([
        'brand' => 'Dacia',
        'model' => 'Sandero',
        'type' => 'Economy',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Dacia Sandero')
        ->assertSee('Economy Cars');
});

test('homepage choose your car excludes unavailable cars', function () {
    homepageCar([
        'brand' => 'Visible',
        'model' => 'Available',
        'is_available' => true,
    ]);

    homepageCar([
        'brand' => 'Hidden',
        'model' => 'Unavailable',
        'is_available' => false,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Visible Available')
        ->assertDontSee('Hidden Unavailable');
});

test('homepage car card links to existing car details route', function () {
    $car = homepageCar([
        'brand' => 'Mercedes',
        'model' => 'C-Class',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('href="'.route('cars.show', $car).'"', false);
});

test('homepage view all cars remains linked to cars index', function () {
    homepageCar();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('href="'.route('cars.index').'"', false);
});

test('homepage car types are generated from real available car types', function () {
    homepageCar([
        'brand' => 'Rolls-Royce',
        'model' => 'Cullinan',
        'type' => 'Luxury',
    ]);

    homepageCar([
        'brand' => 'Renault',
        'model' => 'Clio',
        'type' => 'Economy',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Luxury Cars')
        ->assertSee('Economy Cars');
});

test('homepage does not show car types without available cars', function () {
    homepageCar([
        'brand' => 'Available',
        'model' => 'Sedan',
        'type' => 'Sedan',
        'is_available' => true,
    ]);

    homepageCar([
        'brand' => 'Unavailable',
        'model' => 'Coupe',
        'type' => 'Coupe',
        'is_available' => false,
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Sedan Cars')
        ->assertDontSee('Coupe Cars');
});

test('homepage generated luxury type URL points to existing filtered cars listing', function () {
    homepageCar([
        'type' => 'Luxury',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('href="'.route('cars.index', ['type' => 'Luxury']).'"', false);
});

test('cars type filter returns only matching available cars', function () {
    homepageCar([
        'brand' => 'Prestige',
        'model' => 'Phantom',
        'type' => 'Luxury',
        'is_available' => true,
    ]);

    homepageCar([
        'brand' => 'Budget',
        'model' => 'Sandero',
        'type' => 'Economy',
        'is_available' => true,
    ]);

    homepageCar([
        'brand' => 'Hidden',
        'model' => 'Luxury',
        'type' => 'Luxury',
        'is_available' => false,
    ]);

    $this->get(route('cars.index', ['type' => 'Luxury']))
        ->assertOk()
        ->assertSee('Prestige Phantom')
        ->assertDontSee('Budget Sandero')
        ->assertDontSee('Hidden Luxury');
});

test('representative type image uses existing car image URL architecture', function () {
    Storage::fake('public');
    Storage::disk('public')->put('cars/luxury-home.jpg', 'image');

    homepageCar([
        'type' => 'Luxury',
        'image' => 'luxury-home.jpg',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('/storage/cars/luxury-home.jpg', false);
});

test('dynamic homepage did not introduce car category persistence', function () {
    expect(Schema::hasTable('car_categories'))->toBeFalse()
        ->and(File::glob(database_path('migrations/*car_categories*')))->toBe([]);
});

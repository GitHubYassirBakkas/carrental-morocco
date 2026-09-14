<?php

use App\Models\Car;
use App\Models\Location;
use App\Models\Setting;
use App\Models\User;

function carPolicyAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function carPolicyLocation(): Location
{
    return Location::factory()->create([
        'name' => 'Meknes Branch',
        'city' => 'Meknes',
        'is_active' => true,
    ]);
}

function carPolicyCar(Location $location, array $attributes = []): Car
{
    return Car::factory()->create(array_merge([
        'brand' => 'Renault',
        'model' => 'Clio',
        'location_id' => $location->id,
        'fuel_policy' => 'Same to Same',
        'cancellation_policy' => 'Legacy per-car cancellation should not display',
    ], $attributes));
}

function carPolicySetRefundSettings(int $fullHours, int $partialHours, int $partialPercentage): void
{
    Setting::set('refund_cancellation_window_hours', $fullHours, 'number');
    Setting::set('refund_partial_refund_cutoff_hours', $partialHours, 'number');
    Setting::set('refund_partial_percentage', $partialPercentage, 'number');
}

test('admin car create and edit forms remove cancellation policy and keep fuel policy', function () {
    $admin = carPolicyAdmin();
    $location = carPolicyLocation();
    $car = carPolicyCar($location);

    $createHtml = $this->actingAs($admin)
        ->get(route('admin.cars.create'))
        ->assertOk()
        ->getContent();

    expect($createHtml)
        ->toContain('Fuel Policy')
        ->toContain('name="fuel_policy"')
        ->not->toContain('Cancellation Policy')
        ->not->toContain('name="cancellation_policy"');

    $editHtml = $this->actingAs($admin)
        ->get(route('admin.cars.edit', $car))
        ->assertOk()
        ->getContent();

    expect($editHtml)
        ->toContain('Fuel Policy')
        ->toContain('name="fuel_policy"')
        ->toContain('Same to Same')
        ->not->toContain('Cancellation Policy')
        ->not->toContain('name="cancellation_policy"');
});

test('public car details show global cancellation policy and per-car fuel policy', function () {
    $location = carPolicyLocation();
    $car = carPolicyCar($location, [
        'fuel_policy' => 'Full to Empty',
        'cancellation_policy' => 'No refund within 7 days',
    ]);

    carPolicySetRefundSettings(72, 12, 30);

    $html = $this->get(route('cars.show', $car))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('Cancellation Policy')
        ->toContain('Full refund:')
        ->toContain('72+ hours before pickup')
        ->toContain('Partial refund:')
        ->toContain('12-72 hours before pickup - 30%')
        ->toContain('No refund:')
        ->toContain('Less than 12 hours before pickup')
        ->not->toContain('No refund within 7 days')
        ->toContain('Fuel Policy')
        ->toContain('Full to Empty');

    carPolicySetRefundSettings(96, 36, 65);

    $updatedHtml = $this->get(route('cars.show', $car))
        ->assertOk()
        ->getContent();

    expect($updatedHtml)
        ->toContain('96+ hours before pickup')
        ->toContain('36-96 hours before pickup - 65%')
        ->toContain('Less than 36 hours before pickup')
        ->not->toContain('72+ hours before pickup')
        ->toContain('Full to Empty');
});

<?php

use App\Models\Car;
use App\Models\Insurance;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

function low6Car(): Car
{
    $location = Location::factory()->create([
        'name' => 'Low 6 Insurance Desk',
        'city' => 'Casablanca',
    ]);

    return Car::factory()->create([
        'location_id' => $location->id,
        'security_deposit_amount' => 0,
    ]);
}

function low6Insurance(array $attributes = []): Insurance
{
    return Insurance::create(array_merge([
        'name' => 'Low 6 Standard Protection',
        'type' => 'standard',
        'description' => 'Standard protection fixture',
        'fixed_price' => 120,
        'max_coverage' => 5000,
        'deductible' => 500,
        'excess_fee' => 0,
        'features' => ['Fixture roadside support'],
        'is_active' => true,
        'sort_order' => 1,
    ], $attributes));
}

function low6FinancialCounts(): array
{
    return [
        'bookings' => DB::table('bookings')->count(),
        'invoices' => DB::table('invoices')->count(),
        'payments' => DB::table('payments')->count(),
    ];
}

test('insurance selection post route is public csrf protected and throttled only on session mutation', function () {
    $car = low6Car();

    $storeMiddleware = Route::getRoutes()
        ->match(Request::create(route('insurance.store', $car, false), 'POST'))
        ->gatherMiddleware();

    $selectMiddleware = Route::getRoutes()
        ->match(Request::create(route('insurance.select', $car, false), 'GET'))
        ->gatherMiddleware();

    expect($storeMiddleware)->toContain('throttle:phase5-insurance')
        ->not->toContain('auth')
        ->and($selectMiddleware)->not->toContain('throttle:phase5-insurance')
        ->and(file_get_contents(base_path('bootstrap/app.php')))
        ->toContain('validateCsrfTokens')
        ->not->toContain('cars/{car}/insurance');
});

test('guest insurance selection updates the same session keys without database side effects', function () {
    $car = low6Car();
    $insurance = low6Insurance();
    $beforeCounts = low6FinancialCounts();

    $this->withServerVariables(['REMOTE_ADDR' => '10.6.0.1'])
        ->post(route('insurance.store', $car), [
            'insurance_id' => $insurance->id,
        ])
        ->assertRedirect(route('cars.details', $car))
        ->assertSessionHas('insurance_id', $insurance->id)
        ->assertSessionHas('insurance_name', $insurance->name)
        ->assertSessionHas('insurance_type', $insurance->type)
        ->assertSessionHas('insurance_price', $insurance->fixed_price)
        ->assertSessionHas('insurance_max_coverage', $insurance->max_coverage)
        ->assertSessionHas('insurance_deductible', $insurance->deductible)
        ->assertSessionHas('insurance_features', $insurance->features);

    expect(low6FinancialCounts())->toBe($beforeCounts);
});

test('authenticated insurance selection remains usable and updates session state', function () {
    $user = User::factory()->create([
        'role' => 'user',
        'is_banned' => false,
    ]);
    $car = low6Car();
    $insurance = low6Insurance([
        'name' => 'Low 6 Premium Protection',
        'type' => 'premium',
        'fixed_price' => 250,
    ]);

    RateLimiter::clear((string) $user->id);

    $this->actingAs($user)
        ->post(route('insurance.store', $car), [
            'insurance_id' => $insurance->id,
        ])
        ->assertRedirect(route('cars.details', $car))
        ->assertSessionHas('insurance_id', $insurance->id)
        ->assertSessionHas('insurance_type', 'premium');

    RateLimiter::clear((string) $user->id);
});

test('insurance selection allows normal repeated comparison below the limit', function () {
    $car = low6Car();
    $first = low6Insurance(['name' => 'Low 6 Basic Protection', 'type' => 'basic', 'fixed_price' => 0]);
    $second = low6Insurance(['name' => 'Low 6 Premium Protection', 'type' => 'premium', 'fixed_price' => 250]);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $insurance = $attempt % 2 === 0 ? $first : $second;

        $this->withServerVariables(['REMOTE_ADDR' => '10.6.0.2'])
            ->post(route('insurance.store', $car), [
                'insurance_id' => $insurance->id,
            ])
            ->assertRedirect(route('cars.details', $car))
            ->assertSessionHas('insurance_id', $insurance->id);
    }
});

test('insurance selection returns too many requests after focused limiter is exceeded', function () {
    $car = low6Car();
    $insurance = low6Insurance();
    $ip = '10.6.0.3';

    RateLimiter::clear($ip);

    for ($attempt = 0; $attempt < 30; $attempt++) {
        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post(route('insurance.store', $car), [
                'insurance_id' => $insurance->id,
            ])
            ->assertRedirect(route('cars.details', $car));
    }

    $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->post(route('insurance.store', $car), [
            'insurance_id' => $insurance->id,
        ])
        ->assertStatus(429);

    RateLimiter::clear($ip);
});

<?php

use App\Services\Pricing\BookingPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('booking pricing service returns a stable breakdown', function () {
    $service = new BookingPricingService;

    $breakdown = $service->breakdownForPreview(
        rentalPricePerDay: 400,
        days: 2,
        fixedProtectionPrice: 700,
        extras: ['dropoff_fee' => 200],
        discountAmount: 100
    );

    expect($breakdown['rental_amount'])->toBe(800.0)
        ->and($breakdown['insurance_amount'])->toBe(700.0)
        ->and($breakdown['protection_plan_amount'])->toBe(700.0)
        ->and($breakdown['extras_amount'])->toBe(200.0)
        ->and($breakdown['discount_amount'])->toBe(100.0)
        ->and($breakdown['total_amount'])->toBe(1600.0);
});

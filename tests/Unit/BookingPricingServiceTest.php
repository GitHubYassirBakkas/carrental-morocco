<?php

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Setting;
use App\Services\InvoiceService;
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

test('zero percent tax preserves current no tax pricing behavior', function () {
    Setting::set('tax_percentage', 0, 'number');

    $breakdown = (new BookingPricingService)->breakdown(
        rentalAmount: 1000,
        insuranceAmount: 200,
        extrasAmount: 50,
        discountAmount: 100
    );

    expect($breakdown['subtotal_amount'])->toBe(1250.0)
        ->and($breakdown['tax_amount'])->toBe(0.0)
        ->and($breakdown['total_amount'])->toBe(1150.0);
});

test('non zero tax produces consistent totals without double taxation', function () {
    Setting::set('tax_percentage', 20, 'number');

    $service = new BookingPricingService;
    $breakdown = $service->breakdown(
        rentalAmount: 1000,
        insuranceAmount: 200,
        extrasAmount: 50,
        discountAmount: 100
    );

    expect($breakdown['subtotal_amount'])->toBe(1250.0)
        ->and($breakdown['tax_amount'])->toBe(230.0)
        ->and($breakdown['total_amount'])->toBe(1380.0)
        ->and($breakdown['advance_payment_amount'])->toBe(414.0);

    $booking = Booking::factory()->create([
        'rental_price_per_day' => 500,
        'insurance_fixed_price' => 200,
        'discount_amount' => 100,
        'total_amount' => 1380,
    ]);

    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => 1250,
        'discount_amount' => 100,
        'tax_amount' => 230,
        'total_amount' => 1380,
    ]);

    $invoiceBreakdown = $service->breakdownForInvoice($invoice);

    expect($invoiceBreakdown['tax_amount'])->toBe(230.0)
        ->and($invoiceBreakdown['total_amount'])->toBe(1380.0);
});

test('fuel price per missing tank percent setting affects active fuel charge', function () {
    Setting::set('fuel_price_per_percent', 7.5, 'number');

    expect((new BookingPricingService)->calculateFuelCharge(10))->toBe(75.0);
});

test('fallback invoice preserves reconstructed historical tax without using current settings', function () {
    Setting::set('tax_percentage', 5, 'number');

    $location = Location::factory()->create();
    $booking = Booking::factory()->create([
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'rental_price_per_day' => 500,
        'insurance_fixed_price' => 0,
        'discount_amount' => 200,
        'total_amount' => 960,
    ]);

    $invoice = app(InvoiceService::class)->firstOrCreateForPaymentProcessing($booking);

    expect((float) $invoice->subtotal)->toBe(1000.0)
        ->and((float) $invoice->discount_amount)->toBe(200.0)
        ->and((float) $invoice->tax_amount)->toBe(160.0)
        ->and((float) $invoice->total_amount)->toBe(960.0);
});

test('existing invoice historical tax is not recalculated with current settings', function () {
    Setting::set('tax_percentage', 35, 'number');

    $booking = Booking::factory()->create([
        'rental_price_per_day' => 500,
        'insurance_fixed_price' => 0,
        'discount_amount' => 200,
        'total_amount' => 960,
    ]);
    Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => 1000,
        'discount_amount' => 200,
        'tax_amount' => 160,
        'total_amount' => 960,
    ]);

    $totals = app(BookingPricingService::class)->calculateInvoiceTotalsForBooking($booking);

    expect($totals['subtotal_amount'])->toBe(1000.0)
        ->and($totals['discount_amount'])->toBe(200.0)
        ->and($totals['tax_amount'])->toBe(160.0)
        ->and($totals['total_amount'])->toBe(960.0);
});

test('legacy invoice-less booking with unknown extras is not double taxed', function () {
    Setting::set('tax_percentage', 20, 'number');

    $pickupLocation = Location::factory()->create();
    $dropoffLocation = Location::factory()->create();
    $booking = Booking::factory()->create([
        'pickup_location_id' => $pickupLocation->id,
        'dropoff_location_id' => $dropoffLocation->id,
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'rental_price_per_day' => 500,
        'insurance_fixed_price' => 0,
        'discount_amount' => 0,
        'total_amount' => 1200,
    ]);

    $totals = app(BookingPricingService::class)->calculateInvoiceTotalsForBooking($booking);

    expect($totals['tax_amount'])->toBe(0.0)
        ->and($totals['total_amount'])->toBe(1200.0);
});

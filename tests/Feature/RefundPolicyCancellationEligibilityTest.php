<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\RefundPolicyService;
use Carbon\Carbon;

beforeEach(function () {
    Setting::updateOrCreate(['key' => 'refund_cancellation_window_hours'], [
        'value' => 48,
        'type' => 'number',
        'group' => 'refund',
        'label' => 'Full Refund Before Pickup (Hours)',
        'description' => 'Scheduled pickup must be at least this many hours away for a full rental payment refund',
        'autoload' => true,
        'is_public' => false,
    ]);

    Setting::updateOrCreate(['key' => 'refund_partial_refund_cutoff_hours'], [
        'value' => 24,
        'type' => 'number',
        'group' => 'refund',
        'label' => 'Partial Refund Until Pickup (Hours)',
        'description' => 'Scheduled pickup must be at least this many hours away for the configured partial refund',
        'autoload' => true,
        'is_public' => false,
    ]);

    Setting::updateOrCreate(['key' => 'refund_free_cancellation_enabled'], [
        'value' => true,
        'type' => 'boolean',
        'group' => 'refund',
        'label' => 'Free Cancellation Enabled',
        'description' => 'Legacy toggle retained for backward compatibility',
        'autoload' => true,
        'is_public' => false,
    ]);
});

function refundPolicyLocation(): Location
{
    return Location::create([
        'name' => 'Casablanca Downtown',
        'address' => '1 Test Street',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000000',
        'email' => 'casa@example.com',
        'opening_time' => '08:00',
        'closing_time' => '20:00',
        'is_active' => true,
    ]);
}

function refundPolicyBooking(string $status, Carbon $startDate): Booking
{
    $user = User::factory()->create();
    $location = refundPolicyLocation();
    $car = Car::create([
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
        'image' => 'cars/test.jpg',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => 0,
    ]);

    return Booking::create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'start_date' => $startDate,
        'end_date' => $startDate->copy()->addDays(2),
        'rental_price_per_day' => 500,
        'insurance_fixed_price' => 0,
        'total_amount' => 1000,
        'status' => $status,
        'advance_payment_amount' => 300,
        'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PENDING,
        'security_deposit_amount' => 0,
    ]);
}

function refundPolicyFeatureAttachPayment(Booking $booking, float $amount = 1000, ?Carbon $paidAt = null): Invoice
{
    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => $amount,
        'tax_amount' => 0,
        'total_amount' => $amount,
        'status' => Invoice::STATUS_PAID,
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'amount' => $amount,
        'method' => 'card',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'paid_at' => $paidAt ?? now(),
    ]);

    return $invoice;
}

test('customer cancellation eligibility is based on cancellable customer statuses', function () {
    $service = new RefundPolicyService;

    $expectations = [
        Booking::STATUS_PENDING => true,
        Booking::STATUS_CONFIRMED => true,
        Booking::STATUS_ACTIVE => false,
        Booking::STATUS_COMPLETED => false,
        Booking::STATUS_CANCELLED => false,
    ];

    foreach ($expectations as $status => $expected) {
        $booking = refundPolicyBooking($status, Carbon::now()->addHours(2));

        expect($service->canCustomerCancel($booking))->toBe($expected);
    }
});

test('confirmed booking near pickup gets partial refund in the default pickup-based band', function () {
    $service = new RefundPolicyService;
    $booking = refundPolicyBooking(Booking::STATUS_CONFIRMED, Carbon::now()->addHours(24)->addMinute());
    refundPolicyFeatureAttachPayment($booking, 1000, now()->subHour());

    $policy = $service->evaluateCancellation($booking->fresh('invoice.payments'), 'customer');

    expect($policy['can_cancel'])->toBeTrue()
        ->and($policy['refund_amount'])->toBe(500.0)
        ->and($policy['refund_type'])->toBe('partial')
        ->and($policy['within_full_refund_window'])->toBeFalse();
});

test('cancellation at least 48 hours before pickup returns full paid rental amount', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-18 10:00:00'));

    $service = new RefundPolicyService;
    $booking = refundPolicyBooking(Booking::STATUS_CONFIRMED, Carbon::parse('2026-08-20 10:00:00'));
    refundPolicyFeatureAttachPayment($booking, 1250, Carbon::now());

    expect($service->calculateRefundAmount($booking, 1250))->toBe(1250.0);

    Carbon::setTestNow();
});

test('cancellation between partial cutoff and full refund window uses configured partial refund percentage', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-18 10:01:00'));

    $service = new RefundPolicyService;
    $booking = refundPolicyBooking(Booking::STATUS_CONFIRMED, Carbon::parse('2026-08-20 10:00:00'));
    $booking->update(['advance_payment_amount' => 375]);
    refundPolicyFeatureAttachPayment($booking, 1250, Carbon::now());

    expect($service->calculateRefundAmount($booking->fresh(), 1250))->toBe(625.0);

    Carbon::setTestNow();
});

test('security deposit amount is excluded from rental refund calculation', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-18 10:00:00'));

    $service = new RefundPolicyService;
    $booking = refundPolicyBooking(Booking::STATUS_CONFIRMED, Carbon::parse('2026-08-20 10:00:00'));
    $booking->update([
        'total_amount' => 1000,
        'security_deposit_amount' => 500,
    ]);
    refundPolicyFeatureAttachPayment($booking, 1000, Carbon::now()->subHour());

    expect($service->calculateRefundAmount($booking->fresh(), 1000))->toBe(1000.0);

    Carbon::setTestNow();
});

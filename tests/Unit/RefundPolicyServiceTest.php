<?php

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\RefundPolicyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-08-20 12:00:00'));

    // Set up default settings
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

    Setting::updateOrCreate(['key' => 'refund_no_refund_enabled'], [
        'value' => true,
        'type' => 'boolean',
        'group' => 'refund',
        'label' => 'No Refund Enabled',
        'description' => 'Enable no refund after pickup',
        'autoload' => true,
        'is_public' => false,
    ]);

    Setting::updateOrCreate(['key' => 'refund_default_method'], [
        'value' => 'cash',
        'type' => 'text',
        'group' => 'refund',
        'label' => 'Default Refund Method',
        'description' => 'Fallback refund method',
        'autoload' => true,
        'is_public' => false,
    ]);

    Setting::updateOrCreate(['key' => 'refund_partial_percentage'], [
        'value' => 50,
        'type' => 'number',
        'group' => 'refund',
        'label' => 'Partial Refund Percentage',
        'description' => 'Percentage for partial refunds (NOT USED in Phase 3.6)',
        'autoload' => true,
        'is_public' => false,
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

function refundPolicyBookingWithInvoice(float $paidAmount = 0, string $paymentMethod = 'cash'): array
{
    $booking = Booking::factory()->create();
    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'total_amount' => max(1000, $paidAmount),
    ]);

    if ($paidAmount > 0) {
        Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'user_id' => $booking->user_id,
            'type' => 'payment',
            'amount' => $paidAmount,
            'method' => $paymentMethod,
            'status' => 'completed',
        ]);
    }

    return [$booking->fresh(), $invoice->fresh()];
}

function refundPolicyUnitAttachCompletedPayment(
    Booking $booking,
    float $amount = 1000,
    string $method = 'card',
    ?Carbon $paidAt = null,
    array $overrides = []
): Invoice {
    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'total_amount' => max(1000, $amount),
    ]);

    Payment::factory()->create(array_merge([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'type' => Payment::TYPE_PAYMENT,
        'amount' => $amount,
        'method' => $method,
        'status' => Payment::STATUS_COMPLETED,
        'paid_at' => $paidAt ?? now(),
    ], $overrides));

    return $invoice;
}

test('customer can cancel pending booking based on cancellable status', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'pending',
        'start_date' => Carbon::now()->addHours(50),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);

    expect($service->canCustomerCancel($booking))->toBeTrue();
});

test('customer can cancel confirmed booking based on cancellable status', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addHours(48),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);

    expect($service->canCustomerCancel($booking))->toBeTrue();
});

test('customer can cancel confirmed booking while pickup band uses configured partial percentage', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addHours(47),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', Carbon::now());

    expect($service->canCustomerCancel($booking))->toBeTrue()
        ->and($service->calculateRefundAmount($booking, 1000))->toBe(500.0);
});

test('customer cannot cancel active booking', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'active',
        'start_date' => Carbon::now()->subHours(1),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);

    expect($service->canCustomerCancel($booking))->toBeFalse();
});

test('customer cannot cancel completed booking', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'completed',
        'start_date' => Carbon::now()->subDays(5),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);

    expect($service->canCustomerCancel($booking))->toBeFalse();
});

test('customer cannot cancel cancelled booking', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'cancelled',
        'start_date' => Carbon::now()->subDays(1),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);

    expect($service->canCustomerCancel($booking))->toBeFalse();
});

test('admin can cancel pending booking', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'pending',
        'start_date' => Carbon::now()->addHours(10),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);

    expect($service->canAdminCancel($booking))->toBeTrue();
});

test('admin can cancel active booking', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'active',
        'start_date' => Carbon::now()->subHours(1),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);

    expect($service->canAdminCancel($booking))->toBeTrue();
});

test('admin cannot cancel completed booking', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'completed',
        'start_date' => Carbon::now()->subDays(5),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);

    expect($service->canAdminCancel($booking))->toBeFalse();
});

test('booking is refund eligible when paid amount > 0', function () {
    $service = new RefundPolicyService;

    [$booking] = refundPolicyBookingWithInvoice(1000);

    expect($service->isRefundEligible($booking))->toBeTrue();
});

test('booking is not refund eligible when paid amount = 0', function () {
    $service = new RefundPolicyService;

    [$booking] = refundPolicyBookingWithInvoice(0);

    expect($service->isRefundEligible($booking))->toBeFalse();
});

test('booking is not refund eligible when no invoice', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create();

    expect($service->isRefundEligible($booking))->toBeFalse();
});

test('default pickup-based cancellation bands are applied at exact boundaries', function (string $offset, float $expectedRefund, string $expectedType) {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->add($offset),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', Carbon::now());

    $policy = $service->evaluateCancellation($booking->fresh('invoice.payments'), 'customer');

    expect($policy['refund_amount'])->toBe($expectedRefund)
        ->and($policy['refund_type'])->toBe($expectedType);
})->with([
    '48h01m before pickup' => ['48 hours 1 minute', 1000.0, 'full'],
    '48h00m before pickup' => ['48 hours', 1000.0, 'full'],
    '47h59m before pickup' => ['47 hours 59 minutes', 500.0, 'partial'],
    '24h01m before pickup' => ['24 hours 1 minute', 500.0, 'partial'],
    '24h00m before pickup' => ['24 hours', 500.0, 'partial'],
    '23h59m before pickup' => ['23 hours 59 minutes', 0.0, 'none'],
]);

test('configured pickup-based cancellation bands use admin settings', function (string $offset, float $expectedRefund, string $expectedType) {
    Setting::set('refund_cancellation_window_hours', 72, 'number');
    Setting::set('refund_partial_refund_cutoff_hours', 12, 'number');
    Setting::set('refund_partial_percentage', 30, 'number');

    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->add($offset),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', Carbon::now());

    $policy = $service->evaluateCancellation($booking->fresh('invoice.payments'), 'customer');

    expect($policy['refund_amount'])->toBe($expectedRefund)
        ->and($policy['refund_type'])->toBe($expectedType);
})->with([
    '80h before pickup' => ['80 hours', 1000.0, 'full'],
    '50h before pickup' => ['50 hours', 300.0, 'partial'],
    '12h before pickup' => ['12 hours', 300.0, 'partial'],
    '11h59m before pickup' => ['11 hours 59 minutes', 0.0, 'none'],
]);

test('refund policy uses booking start date instead of payment or booking timestamps for the band', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addHours(23)->addMinutes(59),
        'advance_payment_amount' => 300,
        'advance_payment_paid_at' => Carbon::now()->subHour(),
        'total_amount' => 1000,
        'created_at' => Carbon::now()->subMinutes(10),
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', Carbon::now());

    $policy = $service->evaluateCancellation($booking->fresh('invoice.payments'), 'customer');

    expect($policy['refund_amount'])->toBe(0.0)
        ->and($policy['refund_type'])->toBe('none')
        ->and($policy['hours_before_pickup'])->toBeGreaterThan(23.9)
        ->and($policy['hours_before_pickup'])->toBeLessThan(24);
});

test('later payment timestamps do not change the pickup-based partial band', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addHours(47)->addMinutes(59),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', Carbon::now());

    $refundAmount = $service->calculateRefundAmount($booking, 1000);

    expect($refundAmount)->toBe(500.0);
});

test('legacy refund toggles do not override the pickup-based three-band policy', function () {
    Setting::set('refund_free_cancellation_enabled', false, 'boolean');
    Setting::set('refund_no_refund_enabled', false, 'boolean');

    $service = new RefundPolicyService;

    $fullRefundBooking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addHours(60),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    refundPolicyUnitAttachCompletedPayment($fullRefundBooking, 1000, 'card', Carbon::now()->subDays(5));

    $noRefundBooking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addHours(23)->addMinutes(59),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    refundPolicyUnitAttachCompletedPayment($noRefundBooking, 1000, 'card', Carbon::now());

    expect($service->calculateRefundAmount($fullRefundBooking, 1000))->toBe(1000.0)
        ->and($service->calculateRefundAmount($noRefundBooking, 1000))->toBe(0.0);
});

test('early termination refund for active booking', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'active',
        'start_date' => Carbon::now()->subHours(24),
        'end_date' => Carbon::now()->addDays(4),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);

    // 5-day rental, used 1 day, should refund 4/5
    $refundAmount = $service->calculateRefundAmount($booking, 1000);

    expect($refundAmount)->toBeGreaterThan(0);
    expect($refundAmount)->toBeLessThan(1000);
});

test('refund method uses original payment method', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create();
    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
    ]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'type' => 'payment',
        'method' => 'card',
        'status' => 'completed',
    ]);

    $refundMethod = $service->determineRefundMethod($booking);

    expect($refundMethod)->toBe('card');
});

test('refund method uses fallback when original payment method missing', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create();
    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
    ]);

    DB::statement('PRAGMA ignore_check_constraints = ON');
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'type' => 'payment',
        'method' => '',
        'status' => 'completed',
    ]);
    DB::statement('PRAGMA ignore_check_constraints = OFF');

    $refundMethod = $service->determineRefundMethod($booking);

    expect($refundMethod)->toBe('cash'); // Fallback
});

test('refund method uses fallback when no payment record', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create();
    Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
    ]);

    $refundMethod = $service->determineRefundMethod($booking);

    expect($refundMethod)->toBe('cash'); // Fallback
});

test('fallback refund method does not override original cash or card payments', function (string $originalMethod) {
    Setting::set('refund_default_method', 'bank_transfer', 'text');

    $service = new RefundPolicyService;

    $booking = Booking::factory()->create();
    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
    ]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'type' => 'payment',
        'method' => $originalMethod,
        'status' => 'completed',
    ]);

    expect($service->determineRefundMethod($booking))->toBe($originalMethod);
})->with(['card', 'cash']);

test('configured fallback refund method is used only when original channel is unknown', function () {
    Setting::set('refund_default_method', 'bank_transfer', 'text');

    $service = new RefundPolicyService;

    $booking = Booking::factory()->create();
    Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
    ]);

    expect($service->determineRefundMethod($booking))->toBe('bank_transfer');
});

test('refund type is full when refund amount >= original amount', function () {
    $service = new RefundPolicyService;

    $invoice = Invoice::factory()->create();
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'type' => 'payment',
        'amount' => 1000,
        'status' => 'completed',
    ]);

    $refundType = $service->determineRefundType($invoice, 1000);

    expect($refundType)->toBe('full');
});

test('refund type is partial when refund amount < original amount', function () {
    $service = new RefundPolicyService;

    $invoice = Invoice::factory()->create();
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'type' => 'payment',
        'amount' => 1000,
        'status' => 'completed',
    ]);

    $refundType = $service->determineRefundType($invoice, 700);

    expect($refundType)->toBe('partial');
});

test('refund type is none when refund amount = 0', function () {
    $service = new RefundPolicyService;

    $invoice = Invoice::factory()->create();
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'type' => 'payment',
        'amount' => 1000,
        'status' => 'completed',
    ]);

    $refundType = $service->determineRefundType($invoice, 0);

    expect($refundType)->toBe('none');
});

test('evaluate cancellation returns complete policy decision', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addHours(50),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'total_amount' => 1000,
    ]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'type' => 'payment',
        'amount' => 1000,
        'method' => 'card',
        'status' => 'completed',
    ]);

    $policy = $service->evaluateCancellation($booking, 'customer');

    expect($policy)->toHaveKeys([
        'can_cancel',
        'refund_eligible',
        'refund_type',
        'refund_amount',
        'refund_method',
        'policy_reason',
        'hours_before_pickup',
        'cancellation_deadline',
        'refund_window_started_at',
        'refund_window_ends_at',
        'hours_since_confirmation',
        'within_full_refund_window',
    ]);

    expect($policy['can_cancel'])->toBeTrue();
    expect($policy['refund_eligible'])->toBeTrue();
    expect($policy['refund_type'])->toBe('full');
    expect($policy['refund_amount'])->toBe(1000.0);
    expect($policy['refund_method'])->toBe('card');
    expect($policy['within_full_refund_window'])->toBeTrue();
    expect($policy['policy_reason'])->toContain('before pickup');
});

test('full refund never exceeds actual completed rental payment', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addHours(60),
        'advance_payment_amount' => 330,
        'total_amount' => 1100,
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 330, 'card', Carbon::now());

    expect($service->calculateRefundAmount($booking, 330))->toBe(330.0);
});

test('pending failed refund and security deposit rows do not change the pickup-based refund band', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addHours(47)->addMinutes(59),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'total_amount' => 1000,
    ]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_PENDING,
        'paid_at' => Carbon::now()->subHour(),
    ]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_FAILED,
        'paid_at' => Carbon::now()->subHour(),
    ]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'type' => Payment::TYPE_REFUND,
        'status' => Payment::STATUS_COMPLETED,
        'paid_at' => Carbon::now()->subHour(),
    ]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'type' => 'security_deposit_charge',
        'status' => Payment::STATUS_COMPLETED,
        'paid_at' => Carbon::now()->subHour(),
    ]);

    expect($service->calculateRefundAmount($booking, 1000))->toBe(500.0);
});

test('security deposit remains excluded from actual refundable rental paid amount', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addHours(60),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
        'security_deposit_amount' => 500,
    ]);
    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'total_amount' => 1000,
    ]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'type' => Payment::TYPE_PAYMENT,
        'amount' => 1000,
        'method' => 'card',
        'status' => Payment::STATUS_COMPLETED,
    ]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'type' => 'security_deposit_charge',
        'amount' => 500,
        'method' => 'card',
        'status' => Payment::STATUS_COMPLETED,
    ]);

    $policy = $service->evaluateCancellation($booking->fresh('invoice.payments'), 'customer');

    expect($booking->fresh()->invoice->paid_amount)->toBe(1000.0)
        ->and($policy['refund_amount'])->toBe(1000.0);
});

test('refund_partial_percentage setting affects partial refund calculation', function () {
    // Set partial percentage to 100%
    Setting::set('refund_partial_percentage', 100, 'number');

    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addHours(24),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', Carbon::now());

    $refundAmount = $service->calculateRefundAmount($booking, 1000);

    expect($refundAmount)->toBe(1000.0);
});

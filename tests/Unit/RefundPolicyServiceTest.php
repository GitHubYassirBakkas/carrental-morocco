<?php

use App\Models\Booking;
use App\Models\BookingStateTransition;
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
        'label' => 'Cancellation Window Hours',
        'description' => 'Hours after payment confirmation for free cancellation',
        'autoload' => true,
        'is_public' => false,
    ]);

    Setting::updateOrCreate(['key' => 'refund_free_cancellation_enabled'], [
        'value' => true,
        'type' => 'boolean',
        'group' => 'refund',
        'label' => 'Free Cancellation Enabled',
        'description' => 'Enable free cancellation during grace period',
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

test('customer can cancel confirmed booking after grace period while refund remains partial', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addHours(47),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', Carbon::now()->subHours(49));

    expect($service->canCustomerCancel($booking))->toBeTrue()
        ->and($service->calculateRefundAmount($booking, 1000))->toBe(700.0);
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

test('card payment cancelled 1 hour after confirmation gets full refund even when pickup is tomorrow', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addDay(),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', Carbon::now()->subHour());

    $refundAmount = $service->calculateRefundAmount($booking, 1000);

    expect($refundAmount)->toBe(1000.0);
});

test('partial refund after payment confirmation grace period preserves paid minus advance formula', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addDays(10),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', Carbon::now()->subHours(49));

    $refundAmount = $service->calculateRefundAmount($booking, 1000);

    expect($refundAmount)->toBe(700.0); // 1000 - 300
});

test('confirmed booking after pickup still uses confirmation grace period for partial decision', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->subHours(1),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', Carbon::now()->subHours(49));

    $refundAmount = $service->calculateRefundAmount($booking, 1000);

    expect($refundAmount)->toBe(700.0);
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
    expect($policy['policy_reason'])->toContain('payment confirmation grace period');
});

test('boundary case: exactly 48h after payment confirmation gets full refund', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addDay(),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', Carbon::now()->subHours(48));

    $refundAmount = $service->calculateRefundAmount($booking, 1000);

    expect($refundAmount)->toBe(1000.0); // Full refund at boundary
});

test('boundary case: 47h 59min after payment confirmation gets full refund', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addDay(),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', Carbon::now()->subHours(47)->subMinutes(59));

    $refundAmount = $service->calculateRefundAmount($booking, 1000);

    expect($refundAmount)->toBe(1000.0);
});

test('boundary case: 48h and 1s after payment confirmation gets partial refund', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addDay(),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', Carbon::now()->subHours(48)->subSecond());

    $refundAmount = $service->calculateRefundAmount($booking, 1000);

    expect($refundAmount)->toBe(700.0);
});

test('pickup day before pickup time gets full refund when payment confirmation was recent', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addHours(2),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', Carbon::now()->subHour());

    $refundAmount = $service->calculateRefundAmount($booking, 1000);

    expect($refundAmount)->toBe(1000.0);
});

test('cash booking grace period starts when admin records completed cash payment', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addDay(),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
        'created_at' => Carbon::now()->subDays(5),
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'cash', Carbon::now()->subHour());

    expect($service->calculateRefundAmount($booking, 1000))->toBe(1000.0);
});

test('full refund during grace period never exceeds actual completed rental payment', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addDay(),
        'advance_payment_amount' => 330,
        'total_amount' => 1100,
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 330, 'card', Carbon::now()->subHour());

    expect($service->calculateRefundAmount($booking, 330))->toBe(330.0);
});

test('pending and failed payments do not start the confirmation grace period', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addDay(),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
        'created_at' => Carbon::now()->subHours(49),
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

    expect($service->calculateRefundAmount($booking, 1000))->toBe(700.0);
});

test('refund rows do not count as rental payment confirmation timestamps', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addDay(),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
        'created_at' => Carbon::now()->subHours(49),
    ]);
    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'total_amount' => 1000,
    ]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'type' => Payment::TYPE_REFUND,
        'status' => Payment::STATUS_COMPLETED,
        'paid_at' => Carbon::now()->subHour(),
    ]);

    expect($service->calculateRefundAmount($booking, 1000))->toBe(700.0);
});

test('security deposit records do not affect rental refund grace period', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addDay(),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
        'created_at' => Carbon::now()->subHours(49),
    ]);
    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'total_amount' => 1000,
    ]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $booking->user_id,
        'type' => 'security_deposit_charge',
        'status' => Payment::STATUS_COMPLETED,
        'paid_at' => Carbon::now()->subHour(),
    ]);

    expect($service->calculateRefundAmount($booking, 1000))->toBe(700.0);
});

test('legacy fallback uses advance payment paid timestamp when completed payment paid_at is missing', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addDay(),
        'advance_payment_amount' => 300,
        'advance_payment_paid_at' => Carbon::now()->subHour(),
        'total_amount' => 1000,
        'created_at' => Carbon::now()->subDays(5),
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', null, [
        'paid_at' => null,
        'created_at' => Carbon::now()->subDays(5),
    ]);

    expect($service->calculateRefundAmount($booking, 1000))->toBe(1000.0);
});

test('legacy fallback uses completed payment created timestamp when paid_at and booking paid timestamp are missing', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addDay(),
        'advance_payment_amount' => 300,
        'advance_payment_paid_at' => null,
        'total_amount' => 1000,
        'created_at' => Carbon::now()->subDays(5),
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', null, [
        'paid_at' => null,
        'created_at' => Carbon::now()->subHour(),
    ]);

    expect($service->calculateRefundAmount($booking, 1000))->toBe(1000.0);
});

test('legacy fallback uses accepted pending to confirmed transition before booking creation time', function () {
    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addDay(),
        'advance_payment_amount' => 300,
        'advance_payment_paid_at' => null,
        'total_amount' => 1000,
        'created_at' => Carbon::now()->subDays(5),
    ]);
    BookingStateTransition::create([
        'booking_id' => $booking->id,
        'from_status' => Booking::STATUS_PENDING,
        'to_status' => Booking::STATUS_CONFIRMED,
        'source' => 'test',
        'accepted' => true,
        'created_at' => Carbon::now()->subHour(),
    ]);

    expect($service->calculateRefundAmount($booking, 1000))->toBe(1000.0);
});

test('refund_partial_percentage setting does NOT affect refund calculation', function () {
    // Set partial percentage to 100%
    Setting::where('key', 'refund_partial_percentage')->update(['value' => 100]);

    $service = new RefundPolicyService;

    $booking = Booking::factory()->create([
        'status' => 'confirmed',
        'start_date' => Carbon::now()->addHours(24),
        'advance_payment_amount' => 300,
        'total_amount' => 1000,
    ]);
    refundPolicyUnitAttachCompletedPayment($booking, 1000, 'card', Carbon::now()->subHours(49));

    $refundAmount = $service->calculateRefundAmount($booking, 1000);

    // Should still use paid - advance (700), not percentage (1000)
    expect($refundAmount)->toBe(700.0);
});

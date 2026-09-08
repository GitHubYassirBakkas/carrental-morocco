<?php

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\User;

function high3User(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'is_banned' => false,
    ], $attributes));
}

function high3Booking(?User $user = null, array $attributes = []): Booking
{
    $user ??= high3User();

    return Booking::factory()->create(array_merge([
        'user_id' => $user->id,
        'status' => Booking::STATUS_PENDING,
        'total_amount' => 1000,
        'advance_payment_amount' => 300,
        'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PENDING,
    ], $attributes));
}

function high3Invoice(Booking $booking, float $total = 1000): Invoice
{
    return Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => $total,
        'tax_amount' => 0,
        'total_amount' => $total,
        'status' => Invoice::STATUS_PENDING,
    ]);
}

function high3CompletedPayment(Invoice $invoice, float $amount, string $method = 'cash', string $type = Payment::TYPE_PAYMENT): Payment
{
    return Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $invoice->user_id,
        'amount' => $amount,
        'method' => $method,
        'type' => $type,
        'status' => Payment::STATUS_COMPLETED,
    ]);
}

test('booking-level admin cash payment succeeds normally using the remaining invoice balance', function () {
    $admin = high3User(['role' => 'admin']);
    $booking = high3Booking();

    $this->actingAs($admin)
        ->from(route('admin.bookings.index'))
        ->post(route('admin.payments.cash', $booking))
        ->assertRedirect(route('admin.bookings.index'))
        ->assertSessionHas('success', 'Cash payment recorded successfully.');

    $booking->refresh();
    $invoice = $booking->invoice;
    $payment = $invoice->payments()->first();

    expect(Invoice::where('booking_id', $booking->id)->count())->toBe(1)
        ->and($invoice->payments()->where('type', Payment::TYPE_PAYMENT)->where('status', Payment::STATUS_COMPLETED)->count())->toBe(1)
        ->and((float) $payment->amount)->toBe(1000.0)
        ->and($payment->method)->toBe('cash')
        ->and($payment->transaction_id)->toStartWith('CASH-')
        ->and((float) $invoice->fresh()->paid_amount)->toBe(1000.0)
        ->and($invoice->fresh()->status)->toBe(Invoice::STATUS_PAID)
        ->and($booking->status)->toBe(Booking::STATUS_CONFIRMED)
        ->and($booking->advance_payment_status)->toBe(Booking::ADVANCE_PAYMENT_STATUS_PAID);
});

test('booking-level admin cash amount is current remaining invoice balance instead of stale booking total', function () {
    $admin = high3User(['role' => 'admin']);
    $booking = high3Booking(attributes: ['total_amount' => 1000]);
    $invoice = high3Invoice($booking, total: 800);

    $this->actingAs($admin)
        ->from(route('admin.bookings.index'))
        ->post(route('admin.payments.cash', $booking))
        ->assertRedirect(route('admin.bookings.index'));

    $payment = $invoice->payments()->where('type', Payment::TYPE_PAYMENT)->first();

    expect((float) $payment->amount)->toBe(800.0)
        ->and((float) $invoice->fresh()->paid_amount)->toBe(800.0)
        ->and((float) $invoice->fresh()->paid_amount)->toBeLessThanOrEqual((float) $invoice->total_amount);
});

test('repeated booking-level admin cash post creates one completed payment and one notification', function () {
    $admin = high3User(['role' => 'admin']);
    $booking = high3Booking();

    $this->actingAs($admin)->post(route('admin.payments.cash', $booking))->assertRedirect();
    $this->actingAs($admin)->post(route('admin.payments.cash', $booking))->assertRedirect();

    $invoice = $booking->fresh()->invoice;

    expect($invoice->payments()
        ->where('method', 'cash')
        ->where('type', Payment::TYPE_PAYMENT)
        ->where('status', Payment::STATUS_COMPLETED)
        ->count())->toBe(1)
        ->and((float) $invoice->fresh()->paid_amount)->toBe(1000.0)
        ->and(Notification::where('type', 'payment_success')
            ->whereJsonContains('data->booking_id', $booking->id)
            ->count())->toBe(1);
});

test('fully paid invoice receives no additional booking-level admin cash payment', function () {
    $admin = high3User(['role' => 'admin']);
    $booking = high3Booking();
    $invoice = high3Invoice($booking);
    high3CompletedPayment($invoice, 1000);

    $this->actingAs($admin)
        ->from(route('admin.bookings.index'))
        ->post(route('admin.payments.cash', $booking))
        ->assertRedirect(route('admin.bookings.index'))
        ->assertSessionHas('success', 'Invoice is already fully paid. No additional cash payment was recorded.');

    expect($invoice->payments()
        ->where('method', 'cash')
        ->where('type', Payment::TYPE_PAYMENT)
        ->where('status', Payment::STATUS_COMPLETED)
        ->count())->toBe(1)
        ->and((float) $invoice->fresh()->paid_amount)->toBe(1000.0)
        ->and($invoice->fresh()->status)->toBe(Invoice::STATUS_PAID);
});

test('existing partial payment plus booking-level admin cash pays only the remaining balance', function () {
    $admin = high3User(['role' => 'admin']);
    $booking = high3Booking();
    $invoice = high3Invoice($booking);
    high3CompletedPayment($invoice, 300);

    $this->actingAs($admin)
        ->from(route('admin.bookings.index'))
        ->post(route('admin.payments.cash', $booking))
        ->assertRedirect(route('admin.bookings.index'));

    $cashPayments = $invoice->payments()
        ->where('method', 'cash')
        ->where('type', Payment::TYPE_PAYMENT)
        ->where('status', Payment::STATUS_COMPLETED)
        ->orderBy('amount')
        ->pluck('amount')
        ->map(fn ($amount): float => (float) $amount)
        ->all();

    expect($cashPayments)->toBe([300.0, 700.0])
        ->and((float) $invoice->fresh()->paid_amount)->toBe(1000.0)
        ->and((float) $invoice->fresh()->paid_amount)->toBeLessThanOrEqual((float) $invoice->total_amount);
});

test('non admin cannot use the booking-level admin cash endpoint', function () {
    $customer = high3User();
    $booking = high3Booking();

    $this->actingAs($customer)
        ->post(route('admin.payments.cash', $booking))
        ->assertForbidden();

    expect($booking->fresh()->invoice)->toBeNull()
        ->and(Payment::count())->toBe(0);
});

test('separate admin invoice partial payment flow remains supported', function () {
    $admin = high3User(['role' => 'admin']);
    $booking = high3Booking();
    $invoice = high3Invoice($booking);

    $this->actingAs($admin)
        ->from(route('admin.invoices.show', $invoice))
        ->post(route('admin.invoices.payment', $invoice), [
            'amount' => 250,
            'method' => 'cash',
            'notes' => 'Legitimate partial payment',
        ])
        ->assertRedirect(route('admin.invoices.show', $invoice));

    expect($invoice->payments()->where('method', 'cash')->where('type', Payment::TYPE_PAYMENT)->count())->toBe(1)
        ->and((float) $invoice->fresh()->paid_amount)->toBe(250.0)
        ->and($invoice->fresh()->status)->toBe(Invoice::STATUS_PARTIAL)
        ->and($booking->fresh()->status)->toBe(Booking::STATUS_PENDING);
});

test('security deposit payment rows are not counted as rental cash paid amount', function () {
    $admin = high3User(['role' => 'admin']);
    $booking = high3Booking();
    $invoice = high3Invoice($booking);
    high3CompletedPayment($invoice, 1000, 'cash', 'security_deposit_charge');

    $this->actingAs($admin)
        ->from(route('admin.bookings.index'))
        ->post(route('admin.payments.cash', $booking))
        ->assertRedirect(route('admin.bookings.index'));

    expect($invoice->payments()->where('type', 'security_deposit_charge')->count())->toBe(1)
        ->and($invoice->payments()->where('type', Payment::TYPE_PAYMENT)->where('method', 'cash')->count())->toBe(1)
        ->and((float) $invoice->fresh()->paid_amount)->toBe(1000.0)
        ->and((float) $invoice->fresh()->paid_amount)->toBeLessThanOrEqual((float) $invoice->total_amount);
});

test('unrelated bookings can still receive booking-level admin cash payments normally', function () {
    $admin = high3User(['role' => 'admin']);
    $firstBooking = high3Booking();
    $secondBooking = high3Booking();

    $this->actingAs($admin)->post(route('admin.payments.cash', $firstBooking))->assertRedirect();
    $this->actingAs($admin)->post(route('admin.payments.cash', $secondBooking))->assertRedirect();

    expect($firstBooking->fresh()->invoice->payments()->where('type', Payment::TYPE_PAYMENT)->count())->toBe(1)
        ->and($secondBooking->fresh()->invoice->payments()->where('type', Payment::TYPE_PAYMENT)->count())->toBe(1)
        ->and(Payment::where('method', 'cash')->where('type', Payment::TYPE_PAYMENT)->count())->toBe(2);
});

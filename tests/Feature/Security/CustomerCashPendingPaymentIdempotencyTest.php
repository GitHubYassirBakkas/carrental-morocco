<?php

use App\Mail\BookingPendingMail;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Mail;

function medium1User(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'is_banned' => false,
    ], $attributes));
}

function medium1Booking(?User $user = null, array $attributes = []): Booking
{
    $user ??= medium1User();

    return Booking::factory()->create(array_merge([
        'user_id' => $user->id,
        'status' => Booking::STATUS_PENDING,
        'total_amount' => 1000,
        'advance_payment_amount' => 300,
        'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PENDING,
        'security_deposit_amount' => 0,
        'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_PENDING,
    ], $attributes));
}

function medium1Invoice(Booking $booking, float $total = 1000): Invoice
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

function medium1Payment(
    Invoice $invoice,
    float $amount,
    string $method = 'cash',
    string $type = Payment::TYPE_PAYMENT,
    string $status = Payment::STATUS_PENDING,
    ?string $transactionId = null,
): Payment {
    return Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $invoice->user_id,
        'amount' => $amount,
        'method' => $method,
        'type' => $type,
        'status' => $status,
        'transaction_id' => $transactionId,
        'paid_at' => $status === Payment::STATUS_COMPLETED ? now() : null,
    ]);
}

beforeEach(function () {
    Mail::fake();
});

test('customer can create a normal pending cash payment request', function () {
    $customer = medium1User();
    $booking = medium1Booking($customer);

    $this->actingAs($customer)
        ->post(route('payments.store', $booking), [
            'payment_method' => 'cash',
        ])
        ->assertRedirect(route('bookings.success', $booking))
        ->assertSessionHas('success', 'Booking created! Please visit our agency within 24 hours.');

    $invoice = $booking->fresh()->invoice;
    $payment = $invoice->payments()->first();

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->payments()->where('method', 'cash')->where('type', Payment::TYPE_PAYMENT)->where('status', Payment::STATUS_PENDING)->count())->toBe(1)
        ->and((float) $payment->amount)->toBe(1000.0)
        ->and($payment->transaction_id)->toBeNull()
        ->and($payment->paid_at)->toBeNull()
        ->and((float) $invoice->fresh()->paid_amount)->toBe(0.0)
        ->and($invoice->fresh()->status)->toBe(Invoice::STATUS_PENDING)
        ->and($booking->fresh()->advance_payment_status)->toBe(Booking::ADVANCE_PAYMENT_STATUS_PENDING);

    Mail::assertSent(BookingPendingMail::class, 1);
});

test('repeated identical customer cash posts leave exactly one pending row', function () {
    $customer = medium1User();
    $booking = medium1Booking($customer);

    $this->actingAs($customer)->post(route('payments.store', $booking), ['payment_method' => 'cash'])->assertRedirect();
    $this->actingAs($customer)->post(route('payments.store', $booking), ['payment_method' => 'cash'])->assertRedirect();

    $invoice = $booking->fresh()->invoice;

    expect($invoice->payments()
        ->where('method', 'cash')
        ->where('type', Payment::TYPE_PAYMENT)
        ->where('status', Payment::STATUS_PENDING)
        ->count())->toBe(1)
        ->and(Payment::count())->toBe(1);

    Mail::assertSent(BookingPendingMail::class, 1);
});

test('browser refresh or retry reuses the existing pending cash request', function () {
    $customer = medium1User();
    $booking = medium1Booking($customer);

    $this->actingAs($customer)
        ->from(route('payments.show', $booking))
        ->post(route('payments.store', $booking), ['payment_method' => 'cash'])
        ->assertRedirect(route('bookings.success', $booking));

    $firstPaymentId = $booking->fresh()->invoice->payments()->value('id');

    $this->actingAs($customer)
        ->from(route('bookings.success', $booking))
        ->post(route('payments.store', $booking), ['payment_method' => 'cash'])
        ->assertRedirect(route('bookings.success', $booking))
        ->assertSessionHas('success', 'Cash payment request already exists. Please visit our agency within 24 hours.');

    $invoice = $booking->fresh()->invoice;

    expect($invoice->payments()->count())->toBe(1)
        ->and($invoice->payments()->value('id'))->toBe($firstPaymentId);
});

test('deterministic stale retry path leaves one pending cash request under sqlite', function () {
    $customer = medium1User();
    $booking = medium1Booking($customer);
    $service = app(PaymentService::class);

    $first = $service->recordPendingCashRequestForBooking($booking, $customer->id, 'Cash on delivery');
    $second = $service->recordPendingCashRequestForBooking($booking, $customer->id, 'Cash on delivery');

    expect($first['created'])->toBeTrue()
        ->and($second['created'])->toBeFalse()
        ->and($second['already_pending'])->toBeTrue()
        ->and($second['payment']->id)->toBe($first['payment']->id)
        ->and($booking->fresh()->invoice->payments()->count())->toBe(1);
});

test('an existing pending cash row is reused without creating a duplicate', function () {
    $customer = medium1User();
    $booking = medium1Booking($customer);
    $invoice = medium1Invoice($booking);
    $existing = medium1Payment($invoice, 1000);

    $this->actingAs($customer)
        ->post(route('payments.store', $booking), ['payment_method' => 'cash'])
        ->assertRedirect(route('bookings.success', $booking));

    expect($invoice->payments()->count())->toBe(1)
        ->and($invoice->payments()->first()->id)->toBe($existing->id);

    Mail::assertNothingSent();
});

test('completed and failed cash rows are not treated as pending duplicates', function () {
    $customer = medium1User();
    $booking = medium1Booking($customer);
    $invoice = medium1Invoice($booking);
    medium1Payment($invoice, 250, status: Payment::STATUS_COMPLETED, transactionId: 'CASH-COMPLETED');
    medium1Payment($invoice, 100, status: Payment::STATUS_FAILED, transactionId: 'CASH-FAILED');

    $this->actingAs($customer)
        ->post(route('payments.store', $booking), ['payment_method' => 'cash'])
        ->assertRedirect(route('bookings.success', $booking));

    $pendingCash = $invoice->payments()
        ->where('method', 'cash')
        ->where('type', Payment::TYPE_PAYMENT)
        ->where('status', Payment::STATUS_PENDING)
        ->first();

    expect($pendingCash)->toBeInstanceOf(Payment::class)
        ->and((float) $pendingCash->amount)->toBe(750.0)
        ->and((float) $invoice->fresh()->paid_amount)->toBe(250.0)
        ->and((float) $invoice->fresh()->paid_amount)->toBeLessThanOrEqual((float) $invoice->total_amount);
});

test('unrelated booking can create its own pending cash request', function () {
    $firstCustomer = medium1User();
    $secondCustomer = medium1User();
    $firstBooking = medium1Booking($firstCustomer);
    $secondBooking = medium1Booking($secondCustomer);

    $this->actingAs($firstCustomer)->post(route('payments.store', $firstBooking), ['payment_method' => 'cash'])->assertRedirect();
    $this->actingAs($secondCustomer)->post(route('payments.store', $secondBooking), ['payment_method' => 'cash'])->assertRedirect();

    expect($firstBooking->fresh()->invoice->payments()->where('status', Payment::STATUS_PENDING)->count())->toBe(1)
        ->and($secondBooking->fresh()->invoice->payments()->where('status', Payment::STATUS_PENDING)->count())->toBe(1)
        ->and(Payment::where('method', 'cash')->where('type', Payment::TYPE_PAYMENT)->where('status', Payment::STATUS_PENDING)->count())->toBe(2);
});

test('another customer cannot create a cash request for a booking they do not own', function () {
    $owner = medium1User();
    $otherCustomer = medium1User();
    $booking = medium1Booking($owner);

    $this->actingAs($otherCustomer)
        ->post(route('payments.store', $booking), ['payment_method' => 'cash'])
        ->assertForbidden();

    expect($booking->fresh()->invoice)->toBeNull()
        ->and(Payment::count())->toBe(0);
});

test('stripe payment rows are not matched as pending cash duplicates', function () {
    $customer = medium1User();
    $booking = medium1Booking($customer);
    $invoice = medium1Invoice($booking);
    medium1Payment($invoice, 1000, method: 'stripe', status: Payment::STATUS_PENDING, transactionId: 'pi_pending_cash_idem');

    $this->actingAs($customer)
        ->post(route('payments.store', $booking), ['payment_method' => 'cash'])
        ->assertRedirect(route('bookings.success', $booking));

    expect($invoice->payments()->where('method', 'stripe')->count())->toBe(1)
        ->and($invoice->payments()->where('method', 'cash')->where('type', Payment::TYPE_PAYMENT)->where('status', Payment::STATUS_PENDING)->count())->toBe(1);
});

test('security deposit rows are not matched or counted as pending rental cash payments', function () {
    $customer = medium1User();
    $booking = medium1Booking($customer);
    $invoice = medium1Invoice($booking);
    medium1Payment($invoice, 1000, type: 'security_deposit_charge', status: Payment::STATUS_COMPLETED, transactionId: 'pi_deposit_cash_idem');

    $this->actingAs($customer)
        ->post(route('payments.store', $booking), ['payment_method' => 'cash'])
        ->assertRedirect(route('bookings.success', $booking));

    $pendingCash = $invoice->payments()
        ->where('method', 'cash')
        ->where('type', Payment::TYPE_PAYMENT)
        ->where('status', Payment::STATUS_PENDING)
        ->first();

    expect($invoice->payments()->where('type', 'security_deposit_charge')->count())->toBe(1)
        ->and($pendingCash)->toBeInstanceOf(Payment::class)
        ->and((float) $pendingCash->amount)->toBe(1000.0)
        ->and((float) $invoice->fresh()->paid_amount)->toBe(0.0);
});

test('customer cash amount is authoritative server-side invoice balance', function () {
    $customer = medium1User();
    $booking = medium1Booking($customer, ['total_amount' => 1000]);
    $invoice = medium1Invoice($booking, total: 800);

    $this->actingAs($customer)
        ->post(route('payments.store', $booking), [
            'payment_method' => 'cash',
            'amount' => 9999,
        ])
        ->assertRedirect(route('bookings.success', $booking));

    $payment = $invoice->payments()->first();

    expect((float) $payment->amount)->toBe(800.0)
        ->and((float) $payment->amount)->toBeLessThanOrEqual((float) $invoice->total_amount);
});

test('existing admin cash flow remains unaffected', function () {
    $admin = medium1User(['role' => 'admin']);
    $booking = medium1Booking();

    $this->actingAs($admin)
        ->from(route('admin.bookings.index'))
        ->post(route('admin.payments.cash', $booking))
        ->assertRedirect(route('admin.bookings.index'))
        ->assertSessionHas('success', 'Cash payment recorded successfully.');

    $invoice = $booking->fresh()->invoice;

    expect($invoice->payments()
        ->where('method', 'cash')
        ->where('type', Payment::TYPE_PAYMENT)
        ->where('status', Payment::STATUS_COMPLETED)
        ->count())->toBe(1)
        ->and($invoice->payments()->where('status', Payment::STATUS_PENDING)->count())->toBe(0)
        ->and((float) $invoice->fresh()->paid_amount)->toBe(1000.0);
});

<?php

use App\Mail\AdminPaymentConfirmedMail;
use App\Mail\BookingPendingMail;
use App\Mail\CashRefundProcessedMail;
use App\Mail\StripeRefundProcessedMail;
use App\Mail\TicketCreatedMail;
use App\Mail\TicketRepliedMail;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\BookingService;
use App\Services\RefundService;
use App\Services\StripeRefundGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

function medium7User(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'is_banned' => false,
    ], $attributes));
}

function medium7Booking(?User $user = null, array $attributes = []): Booking
{
    $user ??= medium7User();

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

function medium7Invoice(Booking $booking, float $total = 1000): Invoice
{
    return Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => $total,
        'tax_amount' => 0,
        'total_amount' => $total,
        'status' => Invoice::STATUS_PAID,
    ]);
}

function medium7CompletedPayment(Invoice $invoice, float $amount, string $method = 'cash', ?string $transactionId = null): Payment
{
    return Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'user_id' => $invoice->user_id,
        'amount' => $amount,
        'method' => $method,
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => $transactionId ?? ($method === 'cash' ? 'CASH-MEDIUM7' : 'pi_medium7_refund'),
        'paid_at' => now(),
    ]);
}

function medium7FailNextMailSend(): void
{
    $pendingMail = Mockery::mock(\Illuminate\Mail\PendingMail::class);
    $pendingMail->shouldReceive('send')
        ->once()
        ->andThrow(new RuntimeException('mail transport down'));

    Mail::shouldReceive('to')
        ->once()
        ->andReturn($pendingMail);
}

function medium7StripeRefundService(string $refundId = 're_medium7_refund'): RefundService
{
    $gateway = Mockery::mock(StripeRefundGateway::class);
    $gateway->shouldReceive('create')
        ->once()
        ->andReturn(\Stripe\Refund::constructFrom(['id' => $refundId]));

    return new RefundService(
        app('App\Services\InvoiceService'),
        app('App\Services\PaymentService'),
        app('App\Services\RefundReceiptService'),
        app('App\Services\RefundPolicyService'),
        $gateway,
        app('App\Services\PaymentIdempotencyService'),
        app('App\Services\NotificationService')
    );
}

test('booking approval persists and returns success when post transaction mail fails', function () {
    Log::spy();
    $booking = medium7Booking();
    medium7FailNextMailSend();

    $confirmed = app(BookingService::class)->confirmBooking($booking);

    expect($confirmed->status)->toBe(Booking::STATUS_CONFIRMED)
        ->and($booking->fresh()->status)->toBe(Booking::STATUS_CONFIRMED)
        ->and($booking->fresh()->invoice)->toBeInstanceOf(Invoice::class)
        ->and(Notification::where('type', 'booking_approved')->whereJsonContains('data->booking_id', $booking->id)->count())->toBe(1);

    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $message, array $context) => $message === 'Failed to send confirmation email'
            && $context['booking_id'] === $booking->id
            && $context['mailable'] === AdminPaymentConfirmedMail::class);
});

test('booking approval still sends mail on the normal success path', function () {
    Mail::fake();
    $booking = medium7Booking();

    app(BookingService::class)->confirmBooking($booking);

    Mail::assertSent(AdminPaymentConfirmedMail::class, 1);
});

test('customer cash pending payment remains persisted when pending mail fails', function () {
    Log::spy();
    $customer = medium7User();
    $booking = medium7Booking($customer);
    medium7FailNextMailSend();

    $this->actingAs($customer)
        ->post(route('payments.store', $booking), ['payment_method' => 'cash'])
        ->assertRedirect(route('bookings.success', $booking))
        ->assertSessionHas('success');

    $invoice = $booking->fresh()->invoice;

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->payments()->where('method', 'cash')->where('type', Payment::TYPE_PAYMENT)->where('status', Payment::STATUS_PENDING)->count())->toBe(1)
        ->and(Payment::count())->toBe(1);

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context) => $message === 'Booking pending cash email failed.'
            && $context['booking_id'] === $booking->id
            && $context['mailable'] === BookingPendingMail::class);
});

test('ticket creation remains successful when confirmation mail fails', function () {
    Log::spy();
    $user = medium7User();
    medium7FailNextMailSend();

    $this->actingAs($user)
        ->post(route('tickets.store'), [
            'subject' => 'Need help with booking',
            'category' => 'booking',
            'message' => 'Please help me update my booking details.',
        ])
        ->assertRedirect(route('tickets.index'))
        ->assertSessionHas('success');

    $ticket = Ticket::first();

    expect(Ticket::count())->toBe(1)
        ->and(TicketMessage::count())->toBe(1)
        ->and($ticket->user_id)->toBe($user->id);

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context) => $message === 'Ticket creation email failed.'
            && $context['ticket_id'] === $ticket->id
            && $context['mailable'] === TicketCreatedMail::class);
});

test('admin ticket reply remains successful when reply mail fails', function () {
    Log::spy();
    $customer = medium7User();
    $admin = medium7User(['role' => 'admin']);
    $ticket = Ticket::create([
        'user_id' => $customer->id,
        'ticket_number' => Ticket::generateTicketNumber(),
        'subject' => 'Payment question',
        'category' => 'payment',
        'status' => 'open',
        'priority' => 'medium',
    ]);
    medium7FailNextMailSend();

    $this->actingAs($admin)
        ->post(route('admin.support.reply', $ticket), [
            'message' => 'We have checked this for you.',
            'status' => 'in_progress',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($ticket->fresh()->status)->toBe('in_progress')
        ->and(TicketMessage::where('ticket_id', $ticket->id)->where('is_admin', true)->count())->toBe(1)
        ->and(Notification::where('type', 'support_ticket_reply')->whereJsonContains('data->ticket_id', $ticket->id)->count())->toBe(1);

    $message = TicketMessage::where('ticket_id', $ticket->id)->where('is_admin', true)->first();

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $logMessage, array $context) => $logMessage === 'Support ticket reply email failed.'
            && $context['ticket_id'] === $ticket->id
            && $context['ticket_message_id'] === $message->id
            && $context['mailable'] === TicketRepliedMail::class);
});

test('stripe refund financial state remains successful when receipt mail fails', function () {
    Log::spy();
    $booking = medium7Booking(attributes: [
        'status' => Booking::STATUS_CONFIRMED,
        'rental_payment_intent_id' => 'pi_medium7_refund',
    ]);
    $invoice = medium7Invoice($booking, 1000);
    medium7CompletedPayment($invoice, 1000, 'card', 'pi_medium7_refund');
    medium7FailNextMailSend();

    $refund = medium7StripeRefundService()->processRefund($invoice, 250, 'Post-write mail failure test');

    expect($refund->exists)->toBeTrue()
        ->and($refund->status)->toBe(Payment::STATUS_COMPLETED)
        ->and($refund->type)->toBe(Payment::TYPE_REFUND)
        ->and($refund->stripe_refund_id)->toBe('re_medium7_refund')
        ->and($refund->email_sent_at)->toBeNull()
        ->and(Payment::where('type', Payment::TYPE_REFUND)->count())->toBe(1);

    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $message, array $context) => $message === 'Failed to send Stripe refund email'
            && $context['payment_id'] === $refund->id
            && $context['mailable'] === StripeRefundProcessedMail::class);
});

test('cash refund financial state remains successful when receipt mail fails', function () {
    Log::spy();
    $booking = medium7Booking(attributes: ['status' => Booking::STATUS_CONFIRMED]);
    $invoice = medium7Invoice($booking, 1000);
    medium7CompletedPayment($invoice, 1000, 'cash', 'CASH-MEDIUM7-REFUND');
    medium7FailNextMailSend();

    $refund = app(RefundService::class)->processCashRefund($invoice, 250, 'Cash post-write mail failure test');

    expect($refund->exists)->toBeTrue()
        ->and($refund->status)->toBe(Payment::STATUS_COMPLETED)
        ->and($refund->type)->toBe(Payment::TYPE_REFUND)
        ->and($refund->method)->toBe('cash')
        ->and($refund->email_sent_at)->toBeNull()
        ->and(Payment::where('type', Payment::TYPE_REFUND)->count())->toBe(1);

    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $message, array $context) => $message === 'Failed to send cash refund email'
            && $context['payment_id'] === $refund->id
            && $context['mailable'] === CashRefundProcessedMail::class);
});

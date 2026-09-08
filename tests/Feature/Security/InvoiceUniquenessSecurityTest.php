<?php

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\BookingService;
use App\Services\InvoiceService;
use App\Services\Pricing\BookingPricingService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Mail;

function high4User(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'is_banned' => false,
    ], $attributes));
}

function high4Booking(?User $user = null, array $attributes = []): Booking
{
    $user ??= high4User();

    return Booking::factory()->create(array_merge([
        'user_id' => $user->id,
        'status' => Booking::STATUS_PENDING,
        'total_amount' => 1000,
    ], $attributes));
}

test('one booking can have only one invoice at the database level', function () {
    $booking = high4Booking();

    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'total_amount' => 1000,
    ]);

    expect(fn () => Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'total_amount' => 1000,
    ]))->toThrow(QueryException::class);

    expect(Invoice::where('booking_id', $booking->id)->count())->toBe(1)
        ->and((float) $invoice->fresh()->total_amount)->toBe(1000.0)
        ->and((float) $invoice->fresh()->paid_amount)->toBe(0.0);
});

test('repeated InvoiceService calls return the same invoice without duplicating totals or payments', function () {
    $booking = high4Booking();
    $service = app(InvoiceService::class);

    $first = $service->createForConfirmedBooking($booking);
    Payment::factory()->create([
        'invoice_id' => $first->id,
        'user_id' => $booking->user_id,
        'amount' => 250,
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
    ]);

    $second = $service->createForConfirmedBooking($booking->fresh());

    expect($second->id)->toBe($first->id)
        ->and(Invoice::where('booking_id', $booking->id)->count())->toBe(1)
        ->and((float) $second->fresh()->total_amount)->toBe((float) $first->fresh()->total_amount)
        ->and((float) $second->fresh()->paid_amount)->toBe(250.0);
});

test('InvoiceService reuses the existing invoice when a duplicate-key race is detected', function () {
    $booking = high4Booking();

    $service = new class(app(BookingPricingService::class)) extends InvoiceService
    {
        public ?int $raceWinnerId = null;

        protected function createInvoiceRecord(array $attributes): Invoice
        {
            if ($this->raceWinnerId === null) {
                $winner = Invoice::create(array_merge($attributes, [
                    'subtotal' => 777,
                    'tax_amount' => 0,
                    'total_amount' => 777,
                ]));

                $this->raceWinnerId = $winner->id;
            }

            return parent::createInvoiceRecord($attributes);
        }
    };

    $invoice = $service->firstOrCreateForPaymentProcessing($booking);

    expect($service->raceWinnerId)->not->toBeNull()
        ->and($invoice->id)->toBe($service->raceWinnerId)
        ->and(Invoice::where('booking_id', $booking->id)->count())->toBe(1)
        ->and((float) $invoice->total_amount)->toBe(777.0);
});

test('unrelated bookings can each have their own invoice', function () {
    $service = app(InvoiceService::class);
    $firstBooking = high4Booking();
    $secondBooking = high4Booking();

    $firstInvoice = $service->firstOrCreateForPaymentPage($firstBooking);
    $secondInvoice = $service->firstOrCreateForPaymentPage($secondBooking);

    expect($firstInvoice->id)->not->toBe($secondInvoice->id)
        ->and(Invoice::count())->toBe(2)
        ->and($firstInvoice->booking_id)->toBe($firstBooking->id)
        ->and($secondInvoice->booking_id)->toBe($secondBooking->id);
});

test('normal booking confirmation still creates exactly one invoice', function () {
    Mail::fake();

    $booking = high4Booking();

    app(BookingService::class)->confirmBooking($booking);

    expect($booking->fresh()->status)->toBe(Booking::STATUS_CONFIRMED)
        ->and(Invoice::where('booking_id', $booking->id)->count())->toBe(1)
        ->and($booking->fresh()->invoice)->toBeInstanceOf(Invoice::class);
});

test('payment-page invoice creation still works and remains idempotent', function () {
    $booking = high4Booking();
    $service = app(InvoiceService::class);

    $first = $service->firstOrCreateForPaymentPage($booking);
    $second = $service->firstOrCreateForPaymentPage($booking->fresh());

    expect($first->id)->toBe($second->id)
        ->and(Invoice::where('booking_id', $booking->id)->count())->toBe(1)
        ->and((float) $second->fresh()->paid_amount)->toBe(0.0);
});

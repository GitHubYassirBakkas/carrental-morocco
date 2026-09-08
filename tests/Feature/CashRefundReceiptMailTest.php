<?php

use App\Mail\CashRefundProcessedMail;
use App\Mail\StripeRefundProcessedMail;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payment;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Support\Facades\Mail;

function cashRefundMailFixture(string $paymentMethod = 'cash'): array
{
    $user = User::factory()->create();

    $location = Location::create([
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

    $booking = Booking::create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'start_date' => now()->addDays(3),
        'end_date' => now()->addDays(5),
        'rental_price_per_day' => 500,
        'insurance_fixed_price' => 0,
        'total_amount' => 1000,
        'status' => Booking::STATUS_CONFIRMED,
        'advance_payment_amount' => 300,
        'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PAID,
        'security_deposit_amount' => 0,
        'rental_payment_intent_id' => $paymentMethod === 'cash' ? null : 'pi_refund_mail_test',
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 1000,
        'tax_amount' => 0,
        'total_amount' => 1000,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 1000,
        'method' => $paymentMethod,
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => $paymentMethod === 'cash' ? 'CASH-MAIL-FIXTURE' : 'pi_refund_mail_test',
        'paid_at' => now(),
    ]);

    return compact('user', 'booking', 'invoice');
}

test('cash full refund email has exactly one pdf attachment with booking filename and mime type', function () {
    Mail::fake();

    ['booking' => $booking, 'invoice' => $invoice] = cashRefundMailFixture('cash');

    $refund = app(RefundService::class)->processCashRefund($invoice, 1000, 'Cash full refund test');

    Mail::assertSent(CashRefundProcessedMail::class, function (CashRefundProcessedMail $mail) use ($booking, $refund) {
        expect($mail->payment->id)->toBe($refund->id)
            ->and($mail->attachments())->toHaveCount(1);

        $mail->assertHasAttachedData(
            $mail->pdfContents,
            'refund-receipt-booking-'.$booking->id.'.pdf',
            ['mime' => 'application/pdf']
        );

        return true;
    });
});

test('stripe refund email has zero attachments', function () {
    ['invoice' => $invoice] = cashRefundMailFixture('card');

    $refund = Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $invoice->user_id,
        'amount' => 1000,
        'method' => 'stripe',
        'type' => Payment::TYPE_REFUND,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 're_refund_mail_test',
        'stripe_refund_id' => 're_refund_mail_test',
        'paid_at' => now(),
    ]);

    $mail = new StripeRefundProcessedMail($refund, 'full', 1000);

    expect($mail->attachments())->toBe([]);
});

<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\User;
use App\Services\RefundService;
use App\Services\StripeRefundGateway;
use Illuminate\Support\Facades\Mail;

function createRefundCommunicationFixture(string $paymentMethod = 'card'): array
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
        'price_per_day' => 700,
        'image' => 'cars/test.jpg',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => 0,
    ]);

    $transactionId = $paymentMethod === 'cash' ? 'CASH-FIXTURE' : 'pi_fixture_resend';

    $booking = Booking::create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'total_amount' => 2100,
        'status' => Booking::STATUS_CONFIRMED,
        'rental_payment_intent_id' => $paymentMethod === 'cash' ? null : $transactionId,
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 2100,
        'tax_amount' => 0,
        'total_amount' => 2100,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => $paymentMethod,
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => $transactionId,
        'paid_at' => now(),
    ]);

    return compact('user', 'invoice');
}

test('Stripe refund success sends email', function () {
    Mail::fake();

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
        'price_per_day' => 700,
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
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'total_amount' => 2100,
        'status' => Booking::STATUS_CONFIRMED,
        'rental_payment_intent_id' => 'pi_test_123',
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 2100,
        'tax_amount' => 0,
        'total_amount' => 2100,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'card',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'pi_test_123',
        'paid_at' => now(),
    ]);

    $mockGateway = Mockery::mock(StripeRefundGateway::class);
    $mockGateway->shouldReceive('create')
        ->once()
        ->with(Mockery::type('string'), Mockery::type('int'), Mockery::type('string'), Mockery::type('array'))
        ->andReturn(\Stripe\Refund::constructFrom(['id' => 're_test_refund_123']));

    $refundService = new RefundService(
        app('App\Services\InvoiceService'),
        app('App\Services\PaymentService'),
        app('App\Services\RefundReceiptService'),
        app('App\Services\RefundPolicyService'),
        $mockGateway,
        app('App\Services\PaymentIdempotencyService'),
        app('App\Services\NotificationService')
    );

    $refund = $refundService->processRefund($invoice, 2100, 'Test refund');

    Mail::assertSent(\App\Mail\StripeRefundProcessedMail::class, function ($mail) use ($refund) {
        return $mail->payment->id === $refund->id
            && $mail->attachments() === [];
    });
});

test('Stripe refund email contains refund information', function () {
    Mail::fake();

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
        'price_per_day' => 700,
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
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'total_amount' => 2100,
        'status' => Booking::STATUS_CONFIRMED,
        'rental_payment_intent_id' => 'pi_test_456',
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 2100,
        'tax_amount' => 0,
        'total_amount' => 2100,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'card',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'pi_test_456',
        'paid_at' => now(),
    ]);

    $mockGateway = Mockery::mock(StripeRefundGateway::class);
    $mockGateway->shouldReceive('create')
        ->once()
        ->andReturn(\Stripe\Refund::constructFrom(['id' => 're_test_456']));

    $refundService = new RefundService(
        app('App\Services\InvoiceService'),
        app('App\Services\PaymentService'),
        app('App\Services\RefundReceiptService'),
        app('App\Services\RefundPolicyService'),
        $mockGateway,
        app('App\Services\PaymentIdempotencyService'),
        app('App\Services\NotificationService')
    );

    $refund = $refundService->processRefund($invoice, 1050, 'Partial refund');

    Mail::assertSent(\App\Mail\StripeRefundProcessedMail::class, function ($mail) use ($refund) {
        $rendered = $mail->render();

        return $mail->payment->id === $refund->id &&
               $mail->refundType === 'partial' &&
               $mail->originalAmount === 2100.0 &&
               str_contains($rendered, 're_test_456') &&
               str_contains($rendered, 'Partial Refund') &&
               str_contains($rendered, 'Card / Stripe') &&
               str_contains($rendered, '1,050.00 MAD') &&
               str_contains($rendered, '5-10 business days') &&
               str_contains($rendered, 'Please keep this email for your records.') &&
               ! str_contains($rendered, 'PDF is attached');
    });
});

test('Stripe refund email does not generate or attach a PDF receipt', function () {
    Mail::fake();

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
        'price_per_day' => 700,
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
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'total_amount' => 2100,
        'status' => Booking::STATUS_CONFIRMED,
        'rental_payment_intent_id' => 'pi_test_pdf',
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 2100,
        'tax_amount' => 0,
        'total_amount' => 2100,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'card',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'pi_test_pdf',
        'paid_at' => now(),
    ]);

    $mockGateway = Mockery::mock(StripeRefundGateway::class);
    $mockGateway->shouldReceive('create')
        ->once()
        ->andReturn(\Stripe\Refund::constructFrom(['id' => 're_test_pdf_789']));

    $mockReceiptService = Mockery::mock(\App\Services\RefundReceiptService::class);
    $mockReceiptService->shouldReceive('generateRefundReceiptForEmail')->never();

    $refundService = new RefundService(
        app('App\Services\InvoiceService'),
        app('App\Services\PaymentService'),
        $mockReceiptService,
        app('App\Services\RefundPolicyService'),
        $mockGateway,
        app('App\Services\PaymentIdempotencyService'),
        app('App\Services\NotificationService')
    );

    $refund = $refundService->processRefund($invoice, 2100, 'Test');

    expect($refund->stripe_refund_id)->toBe('re_test_pdf_789');

    Mail::assertSent(\App\Mail\StripeRefundProcessedMail::class, function ($mail) use ($refund) {
        return $mail->payment->id === $refund->id
            && $mail->attachments() === [];
    });
});

test('Stripe refund uses dynamic payment method instead of CASH', function () {
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
        'price_per_day' => 700,
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
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'total_amount' => 2100,
        'status' => Booking::STATUS_CONFIRMED,
        'rental_payment_intent_id' => 'pi_test_method',
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 2100,
        'tax_amount' => 0,
        'total_amount' => 2100,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'card',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'pi_test_method',
        'paid_at' => now(),
    ]);

    $mockGateway = Mockery::mock(StripeRefundGateway::class);
    $mockGateway->shouldReceive('create')
        ->once()
        ->andReturn(\Stripe\Refund::constructFrom(['id' => 're_test_method']));

    $refundService = new RefundService(
        app('App\Services\InvoiceService'),
        app('App\Services\PaymentService'),
        app('App\Services\RefundReceiptService'),
        app('App\Services\RefundPolicyService'),
        $mockGateway,
        app('App\Services\PaymentIdempotencyService'),
        app('App\Services\NotificationService')
    );

    $refund = $refundService->processRefund($invoice, 2100, 'Test');

    expect($refund->method)->toBe('stripe');
    expect($refund->method)->not->toBe('cash');
});

test('Stripe refund creates refund_success notification', function () {
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
        'price_per_day' => 700,
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
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'total_amount' => 2100,
        'status' => Booking::STATUS_CONFIRMED,
        'rental_payment_intent_id' => 'pi_test_notif',
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 2100,
        'tax_amount' => 0,
        'total_amount' => 2100,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'card',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'pi_test_notif',
        'paid_at' => now(),
    ]);

    $mockGateway = Mockery::mock(StripeRefundGateway::class);
    $mockGateway->shouldReceive('create')
        ->once()
        ->andReturn(\Stripe\Refund::constructFrom(['id' => 're_test_notif']));

    $refundService = new RefundService(
        app('App\Services\InvoiceService'),
        app('App\Services\PaymentService'),
        app('App\Services\RefundReceiptService'),
        app('App\Services\RefundPolicyService'),
        $mockGateway,
        app('App\Services\PaymentIdempotencyService'),
        app('App\Services\NotificationService')
    );

    $refund = $refundService->processRefund($invoice, 2100, 'Test');

    $notification = Notification::where('user_id', $user->id)
        ->where('type', 'refund_success')
        ->whereJsonContains('data->payment_id', $refund->id)
        ->first();

    expect($notification)->not->toBeNull();
    expect($notification->data['payment_id'])->toBe($refund->id);
    expect($notification->data['stripe_refund_id'])->toBe('re_test_notif');
});

test('Email failure does not rollback successful refund', function () {
    Mail::fake();
    Mail::shouldReceive('to')
        ->andThrow(new Exception('Email sending failed'));

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
        'price_per_day' => 700,
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
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'total_amount' => 2100,
        'status' => Booking::STATUS_CONFIRMED,
        'rental_payment_intent_id' => 'pi_test_fail_email',
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 2100,
        'tax_amount' => 0,
        'total_amount' => 2100,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'card',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'pi_test_fail_email',
        'paid_at' => now(),
    ]);

    $mockGateway = Mockery::mock(StripeRefundGateway::class);
    $mockGateway->shouldReceive('create')
        ->once()
        ->andReturn(\Stripe\Refund::constructFrom(['id' => 're_test_fail_email']));

    $refundService = new RefundService(
        app('App\Services\InvoiceService'),
        app('App\Services\PaymentService'),
        app('App\Services\RefundReceiptService'),
        app('App\Services\RefundPolicyService'),
        $mockGateway,
        app('App\Services\PaymentIdempotencyService'),
        app('App\Services\NotificationService')
    );

    $refund = $refundService->processRefund($invoice, 2100, 'Test');

    // Refund should still be successful despite email failure
    expect($refund->status)->toBe(Payment::STATUS_COMPLETED);
    expect($refund->stripe_refund_id)->toBe('re_test_fail_email');
});

test('Existing cash refund email still works', function () {
    Mail::fake();

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
        'price_per_day' => 700,
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
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'total_amount' => 2100,
        'status' => Booking::STATUS_CONFIRMED,
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 2100,
        'tax_amount' => 0,
        'total_amount' => 2100,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'cash',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'CASH-123',
        'paid_at' => now(),
    ]);

    $refundService = app(RefundService::class);

    $this->actingAs($user);

    $refund = $refundService->processCashRefund($invoice, 1050, 'Cash refund test');

    Mail::assertSent(\App\Mail\CashRefundProcessedMail::class, function ($mail) use ($refund) {
        return $mail->payment->id === $refund->id
            && count($mail->attachments()) === 1;
    });
});

test('Existing cash refund PDF still works', function () {
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
        'price_per_day' => 700,
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
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'total_amount' => 2100,
        'status' => Booking::STATUS_CONFIRMED,
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 2100,
        'tax_amount' => 0,
        'total_amount' => 2100,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'cash',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'CASH-456',
        'paid_at' => now(),
    ]);

    $refundService = app(RefundService::class);

    $this->actingAs($user);

    $refund = $refundService->processCashRefund($invoice, 1050, 'Cash test');

    $pdfService = app('App\Services\RefundReceiptService');
    $pdf = $pdfService->generateRefundReceipt($refund);

    expect($pdf)->not->toBeNull();
});

test('Resending a Stripe refund email does not attach a PDF', function () {
    Mail::fake();

    ['user' => $user, 'invoice' => $invoice] = createRefundCommunicationFixture('card');

    $refund = Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 1050,
        'method' => 'stripe',
        'type' => Payment::TYPE_REFUND,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 're_resend_stripe',
        'stripe_refund_id' => 're_resend_stripe',
        'paid_at' => now(),
        'email_sent_at' => now()->subMinute(),
    ]);

    $mockReceiptService = Mockery::mock(\App\Services\RefundReceiptService::class);
    $mockReceiptService->shouldReceive('generateRefundReceiptForEmail')->never();

    $refundService = new RefundService(
        app('App\Services\InvoiceService'),
        app('App\Services\PaymentService'),
        $mockReceiptService,
        app('App\Services\RefundPolicyService'),
        Mockery::mock(StripeRefundGateway::class),
        app('App\Services\PaymentIdempotencyService'),
        app('App\Services\NotificationService')
    );

    $refundService->resendRefundEmail($refund);

    Mail::assertSent(\App\Mail\StripeRefundProcessedMail::class, function ($mail) use ($refund) {
        return $mail->payment->id === $refund->id
            && $mail->attachments() === [];
    });
    Mail::assertNotSent(\App\Mail\CashRefundProcessedMail::class);
});

test('Resending a cash refund email still attaches a PDF', function () {
    Mail::fake();

    ['user' => $user, 'invoice' => $invoice] = createRefundCommunicationFixture('cash');

    $refund = Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 1050,
        'method' => 'cash',
        'type' => Payment::TYPE_REFUND,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'CASH-REF-RESEND',
        'paid_at' => now(),
        'email_sent_at' => now()->subMinute(),
    ]);

    $pdfPath = tempnam(sys_get_temp_dir(), 'refund-test-');
    file_put_contents($pdfPath, '%PDF-1.4 test refund receipt');

    $mockReceiptService = Mockery::mock(\App\Services\RefundReceiptService::class);
    $mockReceiptService->shouldReceive('generateRefundReceiptForEmail')
        ->once()
        ->with($refund)
        ->andReturn($pdfPath);

    $refundService = new RefundService(
        app('App\Services\InvoiceService'),
        app('App\Services\PaymentService'),
        $mockReceiptService,
        app('App\Services\RefundPolicyService'),
        Mockery::mock(StripeRefundGateway::class),
        app('App\Services\PaymentIdempotencyService'),
        app('App\Services\NotificationService')
    );

    $refundService->resendRefundEmail($refund);

    Mail::assertSent(\App\Mail\CashRefundProcessedMail::class, function ($mail) use ($refund) {
        return $mail->payment->id === $refund->id
            && count($mail->attachments()) === 1;
    });
    Mail::assertNotSent(\App\Mail\StripeRefundProcessedMail::class);
    expect(file_exists($pdfPath))->toBeFalse();
});

test('Duplicate refund.succeeded webhook does not create duplicate notification', function () {
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
        'price_per_day' => 700,
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
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'total_amount' => 2100,
        'status' => Booking::STATUS_CONFIRMED,
        'rental_payment_intent_id' => 'pi_test_dup_webhook',
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 2100,
        'tax_amount' => 0,
        'total_amount' => 2100,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'card',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'pi_test_dup_webhook',
        'paid_at' => now(),
    ]);

    // Create refund record manually (simulating synchronous refund)
    $refund = Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'stripe',
        'type' => Payment::TYPE_REFUND,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 're_test_dup_webhook',
        'stripe_refund_id' => 're_test_dup_webhook',
        'paid_at' => now(),
    ]);

    // Create notification (simulating synchronous flow)
    Notification::create([
        'user_id' => $user->id,
        'type' => 'refund_success',
        'title' => 'Refund Processed Successfully',
        'message' => 'Your refund of 2100.00 MAD for booking #'.$booking->id.' has been processed successfully.',
        'data' => [
            'payment_id' => $refund->id,
            'booking_id' => $booking->id,
        ],
    ]);

    $paymentProcessor = app('App\Domain\Payment\PaymentProcessor');

    // Process refund.succeeded webhook first time
    $event1 = (object) [
        'id' => 'evt_test_1',
        'type' => 'refund.succeeded',
    ];

    $refundObject = (object) [
        'id' => 're_test_dup_webhook',
    ];

    $result1 = $paymentProcessor->syncRentalRefundFromWebhook('refund.succeeded', $refundObject, 'evt_test_1');

    expect($result1['action'])->toBe('processed');

    // Process same refund.succeeded webhook second time
    $event2 = (object) [
        'id' => 'evt_test_2',
        'type' => 'refund.succeeded',
    ];

    $result2 = $paymentProcessor->syncRentalRefundFromWebhook('refund.succeeded', $refundObject, 'evt_test_2');

    expect($result2['action'])->toBe('processed');

    // Assert only one notification exists
    $notificationCount = Notification::where('user_id', $user->id)
        ->where('type', 'refund_success')
        ->whereJsonContains('data->payment_id', $refund->id)
        ->count();

    expect($notificationCount)->toBe(1);

    // Assert no duplicate Payment refund record
    $refundCount = Payment::where('invoice_id', $invoice->id)
        ->where('type', Payment::TYPE_REFUND)
        ->count();

    expect($refundCount)->toBe(1);
});

test('Synchronous refund + later refund.succeeded webhook', function () {
    Mail::fake();

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
        'price_per_day' => 700,
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
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'total_amount' => 2100,
        'status' => Booking::STATUS_CONFIRMED,
        'rental_payment_intent_id' => 'pi_test_sync_webhook',
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 2100,
        'tax_amount' => 0,
        'total_amount' => 2100,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'card',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'pi_test_sync_webhook',
        'paid_at' => now(),
    ]);

    $mockGateway = Mockery::mock(StripeRefundGateway::class);
    $mockGateway->shouldReceive('create')
        ->once()
        ->andReturn(\Stripe\Refund::constructFrom(['id' => 're_test_sync_webhook']));

    $refundService = new RefundService(
        app('App\Services\InvoiceService'),
        app('App\Services\PaymentService'),
        app('App\Services\RefundReceiptService'),
        app('App\Services\RefundPolicyService'),
        $mockGateway,
        app('App\Services\PaymentIdempotencyService'),
        app('App\Services\NotificationService')
    );

    // Execute synchronous refund
    $refund = $refundService->processRefund($invoice, 2100, 'Test');

    // Assert email_sent_at is populated
    expect($refund->email_sent_at)->not->toBeNull();

    // Assert refund_success notification exists
    $notification = Notification::where('user_id', $user->id)
        ->where('type', 'refund_success')
        ->whereJsonContains('data->payment_id', $refund->id)
        ->first();

    expect($notification)->not->toBeNull();

    // Assert email was sent once
    Mail::assertSent(\App\Mail\StripeRefundProcessedMail::class, 1);

    // Process refund.succeeded webhook
    $paymentProcessor = app('App\Domain\Payment\PaymentProcessor');

    $event = (object) [
        'id' => 'evt_test_sync',
        'type' => 'refund.succeeded',
    ];

    $refundObject = (object) [
        'id' => 're_test_sync_webhook',
    ];

    $result = $paymentProcessor->syncRentalRefundFromWebhook('refund.succeeded', $refundObject, 'evt_test_sync');

    expect($result['action'])->toBe('processed');

    // Assert email count remains exactly 1
    Mail::assertSent(\App\Mail\StripeRefundProcessedMail::class, 1);

    // Assert notification count remains exactly 1
    $notificationCount = Notification::where('user_id', $user->id)
        ->where('type', 'refund_success')
        ->whereJsonContains('data->payment_id', $refund->id)
        ->count();

    expect($notificationCount)->toBe(1);

    // Assert Payment remains completed
    $refund->refresh();
    expect($refund->status)->toBe(Payment::STATUS_COMPLETED);

    // Assert invoice remains correctly synchronized
    $invoice->refresh();
    expect($invoice->status)->toBe(Invoice::STATUS_REFUNDED);
});

test('refund.failed webhook creates refund_failed notification', function () {
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
        'price_per_day' => 700,
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
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'total_amount' => 2100,
        'status' => Booking::STATUS_CONFIRMED,
        'rental_payment_intent_id' => 'pi_test_failed_webhook',
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 2100,
        'tax_amount' => 0,
        'total_amount' => 2100,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'card',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'pi_test_failed_webhook',
        'paid_at' => now(),
    ]);

    // Create refund record with pending status (simulating refund initiated)
    $refund = Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'stripe',
        'type' => Payment::TYPE_REFUND,
        'status' => Payment::STATUS_PENDING,
        'transaction_id' => 're_test_failed',
        'stripe_refund_id' => 're_test_failed',
        'paid_at' => null,
    ]);

    $paymentProcessor = app('App\Domain\Payment\PaymentProcessor');

    $event = (object) [
        'id' => 'evt_test_failed',
        'type' => 'refund.failed',
    ];

    $refundObject = (object) [
        'id' => 're_test_failed',
        'failure_reason' => 'Card declined',
    ];

    $result = $paymentProcessor->syncRentalRefundFromWebhook('refund.failed', $refundObject, 'evt_test_failed');

    expect($result['action'])->toBe('processed');

    // Assert status = failed
    $refund->refresh();
    expect($refund->status)->toBe(Payment::STATUS_FAILED);

    // Assert failure reason is stored
    expect($refund->notes)->toContain('Stripe refund failed: Card declined');

    // Assert refund_failed notification exists
    $notification = Notification::where('user_id', $user->id)
        ->where('type', 'refund_failed')
        ->whereJsonContains('data->payment_id', $refund->id)
        ->first();

    expect($notification)->not->toBeNull();
    expect($notification->data['failure_reason'])->toBe('Card declined');

    // Assert refund_success notification does NOT exist
    $successNotification = Notification::where('user_id', $user->id)
        ->where('type', 'refund_success')
        ->whereJsonContains('data->payment_id', $refund->id)
        ->first();

    expect($successNotification)->toBeNull();
});

test('Duplicate refund.failed webhook does not create duplicate notification', function () {
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
        'price_per_day' => 700,
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
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'total_amount' => 2100,
        'status' => Booking::STATUS_CONFIRMED,
        'rental_payment_intent_id' => 'pi_test_dup_failed',
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 2100,
        'tax_amount' => 0,
        'total_amount' => 2100,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'card',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'pi_test_dup_failed',
        'paid_at' => now(),
    ]);

    $refund = Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'stripe',
        'type' => Payment::TYPE_REFUND,
        'status' => Payment::STATUS_PENDING,
        'transaction_id' => 're_test_dup_failed',
        'stripe_refund_id' => 're_test_dup_failed',
        'paid_at' => null,
    ]);

    $paymentProcessor = app('App\Domain\Payment\PaymentProcessor');

    $refundObject = (object) [
        'id' => 're_test_dup_failed',
        'failure_reason' => 'Card declined',
    ];

    // Process refund.failed webhook first time
    $event1 = (object) [
        'id' => 'evt_test_dup_failed_1',
        'type' => 'refund.failed',
    ];

    $result1 = $paymentProcessor->syncRentalRefundFromWebhook('refund.failed', $refundObject, 'evt_test_dup_failed_1');

    expect($result1['action'])->toBe('processed');

    // Process same refund.failed webhook second time
    $event2 = (object) [
        'id' => 'evt_test_dup_failed_2',
        'type' => 'refund.failed',
    ];

    $result2 = $paymentProcessor->syncRentalRefundFromWebhook('refund.failed', $refundObject, 'evt_test_dup_failed_2');

    expect($result2['action'])->toBe('processed');

    // Assert only one refund_failed notification exists
    $notificationCount = Notification::where('user_id', $user->id)
        ->where('type', 'refund_failed')
        ->whereJsonContains('data->payment_id', $refund->id)
        ->count();

    expect($notificationCount)->toBe(1);
});

test('Communication idempotency - duplicate refund request', function () {
    Mail::fake();

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
        'price_per_day' => 700,
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
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(3),
        'total_amount' => 2100,
        'status' => Booking::STATUS_CONFIRMED,
        'rental_payment_intent_id' => 'pi_test_idem',
    ]);

    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'subtotal' => 2100,
        'tax_amount' => 0,
        'total_amount' => 2100,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
    ]);

    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'card',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'pi_test_idem',
        'paid_at' => now(),
    ]);

    $mockGateway = Mockery::mock(StripeRefundGateway::class);
    $mockGateway->shouldReceive('create')
        ->once()
        ->andReturn(\Stripe\Refund::constructFrom(['id' => 're_test_idem']));

    $refundService = new RefundService(
        app('App\Services\InvoiceService'),
        app('App\Services\PaymentService'),
        app('App\Services\RefundReceiptService'),
        app('App\Services\RefundPolicyService'),
        $mockGateway,
        app('App\Services\PaymentIdempotencyService'),
        app('App\Services\NotificationService')
    );

    // Call refund first time
    $refund1 = $refundService->processRefund($invoice, 2100, 'Test');

    expect($refund1->email_sent_at)->not->toBeNull();

    // Call refund second time (should return existing record)
    $refund2 = $refundService->processRefund($invoice, 2100, 'Test');

    expect($refund2->id)->toBe($refund1->id);

    // Assert only one email is sent
    Mail::assertSent(\App\Mail\StripeRefundProcessedMail::class, 1);

    // Assert only one notification exists
    $notificationCount = Notification::where('user_id', $user->id)
        ->where('type', 'refund_success')
        ->whereJsonContains('data->payment_id', $refund1->id)
        ->count();

    expect($notificationCount)->toBe(1);

    // Assert email_sent_at remains populated
    $refund1->refresh();
    expect($refund1->email_sent_at)->not->toBeNull();
});

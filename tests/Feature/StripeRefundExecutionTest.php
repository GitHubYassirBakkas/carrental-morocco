<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payment;
use App\Models\User;
use App\Services\RefundService;
use App\Services\StripeRefundGateway;

test('card rental refund calls Stripe with correct PaymentIntent ID', function () {
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
        'rental_payment_intent_id' => 'pi_test_1234567890',
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

    // Create a card payment record
    Payment::create([
        'invoice_id' => $invoice->id,
        'user_id' => $user->id,
        'amount' => 2100,
        'method' => 'card',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'pi_test_1234567890',
        'paid_at' => now(),
    ]);

    // Mock Stripe refund gateway
    $mockGateway = Mockery::mock(StripeRefundGateway::class);
    $mockGateway->shouldReceive('create')
        ->once()
        ->with(
            'pi_test_1234567890',
            210000, // 2100 * 100 cents
            'stripe_refund:pi_test_1234567890:210000',
            [
                'booking_id' => (string) $booking->id,
                'invoice_id' => (string) $invoice->id,
                'type' => 'rental_refund',
            ]
        )
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

    expect($refund->method)->toBe('stripe');
    expect($refund->stripe_refund_id)->toBe('re_test_refund_123');
    expect($refund->transaction_id)->toBe('re_test_refund_123');
    expect((float) $refund->amount)->toBe(2100.0);
    expect($refund->status)->toBe(Payment::STATUS_COMPLETED);
});

test('correct amount is converted to Stripe cents', function () {
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
        ->with(Mockery::on(function ($paymentIntentId) {
            return $paymentIntentId === 'pi_test_123';
        }), Mockery::on(function ($amountCents) {
            return $amountCents === 105000; // 1050 * 100
        }), Mockery::type('string'), Mockery::type('array'))
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

    expect((float) $refund->amount)->toBe(1050.0);
});

test('Stripe refund ID is stored in payment record', function () {
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
        'rental_payment_intent_id' => 'pi_test_789',
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
        'transaction_id' => 'pi_test_789',
        'paid_at' => now(),
    ]);

    $mockGateway = Mockery::mock(StripeRefundGateway::class);
    $mockGateway->shouldReceive('create')
        ->once()
        ->with(Mockery::type('string'), Mockery::type('int'), Mockery::type('string'), Mockery::type('array'))
        ->andReturn(\Stripe\Refund::constructFrom(['id' => 're_test_stripe_id_xyz']));

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

    expect($refund->stripe_refund_id)->toBe('re_test_stripe_id_xyz');

    // Verify it's persisted in database
    $dbRefund = Payment::find($refund->id);
    expect($dbRefund->stripe_refund_id)->toBe('re_test_stripe_id_xyz');
});

test('duplicate admin refund request does not create duplicate Stripe refund', function () {
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
        'rental_payment_intent_id' => 'pi_test_duplicate',
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
        'transaction_id' => 'pi_test_duplicate',
        'paid_at' => now(),
    ]);

    $mockGateway = Mockery::mock(StripeRefundGateway::class);
    $mockGateway->shouldReceive('create')
        ->once()
        ->with(Mockery::type('string'), Mockery::type('int'), Mockery::type('string'), Mockery::type('array'))
        ->andReturn(\Stripe\Refund::constructFrom(['id' => 're_test_duplicate']));

    $refundService = new RefundService(
        app('App\Services\InvoiceService'),
        app('App\Services\PaymentService'),
        app('App\Services\RefundReceiptService'),
        app('App\Services\RefundPolicyService'),
        $mockGateway,
        app('App\Services\PaymentIdempotencyService'),
        app('App\Services\NotificationService')
    );

    // First refund
    $refund1 = $refundService->processRefund($invoice, 2100, 'First');
    expect($refund1->stripe_refund_id)->toBe('re_test_duplicate');

    // Second refund with same amount - should return existing record
    $refund2 = $refundService->processRefund($invoice, 2100, 'Second');
    expect($refund2->id)->toBe($refund1->id);
    expect($refund2->stripe_refund_id)->toBe('re_test_duplicate');

    // Verify only one refund record exists
    $refundCount = Payment::where('invoice_id', $invoice->id)
        ->where('type', Payment::TYPE_REFUND)
        ->count();
    expect($refundCount)->toBe(1);
});

test('Stripe failure does not create successful refund record', function () {
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
        'rental_payment_intent_id' => 'pi_test_fail',
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
        'transaction_id' => 'pi_test_fail',
        'paid_at' => now(),
    ]);

    $mockGateway = Mockery::mock(StripeRefundGateway::class);
    $mockGateway->shouldReceive('create')
        ->once()
        ->with(Mockery::type('string'), Mockery::type('int'), Mockery::type('string'), Mockery::type('array'))
        ->andThrow(new Exception('Stripe API error: Card declined'));

    $refundService = new RefundService(
        app('App\Services\InvoiceService'),
        app('App\Services\PaymentService'),
        app('App\Services\RefundReceiptService'),
        app('App\Services\RefundPolicyService'),
        $mockGateway,
        app('App\Services\PaymentIdempotencyService'),
        app('App\Services\NotificationService')
    );

    expect(fn () => $refundService->processRefund($invoice, 2100, 'Test'))
        ->toThrow(Exception::class, 'Stripe refund failed');

    // Verify no refund record was created
    $refundCount = Payment::where('invoice_id', $invoice->id)
        ->where('type', Payment::TYPE_REFUND)
        ->count();
    expect($refundCount)->toBe(0);
});

test('Stale idempotency record allows retry after 5 minutes', function () {
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
        'rental_payment_intent_id' => 'pi_test_stale',
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
        'transaction_id' => 'pi_test_stale',
        'paid_at' => now(),
    ]);

    // Create a stale processing record (older than 5 minutes)
    $staleIdempotency = \App\Models\PaymentIdempotencyKey::create([
        'idempotency_key' => 'stripe_refund:pi_test_stale:210000',
        'event_id' => null,
        'payment_intent_id' => 'pi_test_stale',
        'status' => 'processing',
    ]);
    $staleIdempotency->forceFill([
        'created_at' => now()->subMinutes(10),
        'updated_at' => now()->subMinutes(10),
    ])->save();

    $mockGateway = Mockery::mock(StripeRefundGateway::class);
    $mockGateway->shouldReceive('create')
        ->once()
        ->with(Mockery::type('string'), Mockery::type('int'), Mockery::type('string'), Mockery::type('array'))
        ->andReturn(\Stripe\Refund::constructFrom(['id' => 're_test_stale']));

    $refundService = new RefundService(
        app('App\Services\InvoiceService'),
        app('App\Services\PaymentService'),
        app('App\Services\RefundReceiptService'),
        app('App\Services\RefundPolicyService'),
        $mockGateway,
        app('App\Services\PaymentIdempotencyService'),
        app('App\Services\NotificationService')
    );

    // This should succeed despite the stale processing record
    $refund = $refundService->processRefund($invoice, 2100, 'Test');

    expect($refund->status)->toBe(Payment::STATUS_COMPLETED);
    expect($refund->stripe_refund_id)->toBe('re_test_stale');
});

test('Recent processing record blocks retry', function () {
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
        'rental_payment_intent_id' => 'pi_test_recent',
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
        'transaction_id' => 'pi_test_recent',
        'paid_at' => now(),
    ]);

    // Create a recent processing record (less than 5 minutes)
    \App\Models\PaymentIdempotencyKey::create([
        'idempotency_key' => 'stripe_refund:pi_test_recent:210000',
        'event_id' => null,
        'payment_intent_id' => 'pi_test_recent',
        'status' => 'processing',
        'created_at' => now()->subMinutes(2), // 2 minutes ago
    ]);

    $mockGateway = Mockery::mock(StripeRefundGateway::class);
    $mockGateway->shouldReceive('create')
        ->never(); // Should not be called due to blocking

    $refundService = new RefundService(
        app('App\Services\InvoiceService'),
        app('App\Services\PaymentService'),
        app('App\Services\RefundReceiptService'),
        app('App\Services\RefundPolicyService'),
        $mockGateway,
        app('App\Services\PaymentIdempotencyService'),
        app('App\Services\NotificationService')
    );

    // This should be blocked due to recent processing record
    expect(fn () => $refundService->processRefund($invoice, 2100, 'Test'))
        ->toThrow(Exception::class, 'Refund is currently being processed, please wait');
});

test('cash refund still works independently', function () {
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

    expect($refund->method)->toBe('cash');
    expect($refund->stripe_refund_id)->toBeNull();
    expect((float) $refund->amount)->toBe(1050.0);
    expect($refund->status)->toBe(Payment::STATUS_COMPLETED);
});

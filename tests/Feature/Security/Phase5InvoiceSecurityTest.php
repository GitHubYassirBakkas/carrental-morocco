<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payment;
use App\Models\User;

function phase53User(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'is_banned' => false,
    ], $attributes));
}

function phase53Location(): Location
{
    return Location::create([
        'name' => 'Casablanca Invoice Desk',
        'address' => '10 Security Street',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000001',
        'email' => 'invoices@example.test',
        'opening_time' => '08:00',
        'closing_time' => '20:00',
        'is_active' => true,
    ]);
}

function phase53Car(?Location $location = null): Car
{
    $location ??= phase53Location();

    return Car::create([
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
        'image' => 'test-car.jpg',
        'is_available' => true,
        'location_id' => $location->id,
        'security_deposit_amount' => 0,
    ]);
}

function phase53Booking(?User $user = null, array $attributes = []): Booking
{
    $user ??= phase53User();
    $location = phase53Location();
    $car = phase53Car($location);

    return Booking::create(array_merge([
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
        'advance_payment_paid_at' => now(),
        'advance_payment_due_at' => now()->addDay(),
        'security_deposit_amount' => 0,
        'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_PENDING,
    ], $attributes));
}

function phase53Invoice(Booking $booking, array $attributes = []): Invoice
{
    return Invoice::create(array_merge([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => 1000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 1000,
        'status' => Invoice::STATUS_PAID,
        'issued_at' => now(),
        'due_date' => now()->addDay(),
    ], $attributes));
}

function phase53Payment(Invoice $invoice, array $attributes = []): Payment
{
    return Payment::create(array_merge([
        'invoice_id' => $invoice->id,
        'user_id' => $invoice->user_id,
        'amount' => 1000,
        'method' => 'cash',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'phase53-'.$invoice->id,
        'paid_at' => now(),
        'notes' => 'Phase 5.3 security fixture',
    ], $attributes));
}

function phase53InvoiceFixture(?User $user = null): Invoice
{
    $booking = phase53Booking($user);
    $invoice = phase53Invoice($booking);
    phase53Payment($invoice);

    return $invoice;
}

test('customer can view their own invoice without admin actions', function () {
    $user = phase53User();
    $invoice = phase53InvoiceFixture($user);

    $response = $this->actingAs($user)->get(route('customer.invoices.show', $invoice));

    $response->assertOk();
    $response->assertSee('Invoice', false);
    $response->assertSee('#'.str_pad($invoice->id, 4, '0', STR_PAD_LEFT), false);
    $response->assertDontSee(route('admin.invoices.payment', $invoice), false);
    $response->assertDontSee(route('admin.invoices.refund', $invoice), false);
});

test('customer can download their own invoice', function () {
    $user = phase53User();
    $invoice = phase53InvoiceFixture($user);

    $response = $this->actingAs($user)->get(route('customer.invoices.download', $invoice));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('invoice-'.$invoice->id.'.pdf');
});

test('customer cannot view or download another customers invoice', function () {
    $userA = phase53User();
    $userB = phase53User();
    $invoice = phase53InvoiceFixture($userB);

    $this->actingAs($userA)
        ->get(route('customer.invoices.show', $invoice))
        ->assertForbidden();

    $this->actingAs($userA)
        ->get(route('customer.invoices.download', $invoice))
        ->assertForbidden();
});

test('guest is blocked from customer invoice view and download routes', function () {
    $invoice = phase53InvoiceFixture();

    $this->get(route('customer.invoices.show', $invoice))
        ->assertRedirect(route('login'));

    $this->get(route('customer.invoices.download', $invoice))
        ->assertRedirect(route('login'));
});

test('customer cannot access admin invoice endpoints or invoice operations', function () {
    $customer = phase53User();
    $invoice = phase53InvoiceFixture($customer);

    $this->actingAs($customer)
        ->get(route('admin.invoices.show', $invoice))
        ->assertForbidden();

    $this->actingAs($customer)
        ->post(route('admin.invoices.payment', $invoice), [
            'amount' => 10,
            'method' => 'cash',
        ])
        ->assertForbidden();

    $this->actingAs($customer)
        ->post(route('admin.invoices.refund', $invoice), [
            'amount' => 10,
            'reason' => 'not allowed',
        ])
        ->assertForbidden();
});

test('admin invoice endpoint remains available to admins', function () {
    $admin = phase53User(['role' => 'admin']);
    $invoice = phase53InvoiceFixture();

    $this->actingAs($admin)
        ->get(route('admin.invoices.show', $invoice))
        ->assertOk();
});

test('payment history invoice link uses customer invoice route', function () {
    $customer = phase53User();
    $invoice = phase53InvoiceFixture($customer);

    $response = $this->actingAs($customer)->get(route('profile.payment-history'));

    $response->assertOk();
    $response->assertSee(route('customer.invoices.download', $invoice), false);
    $response->assertDontSee(route('admin.invoices.show', $invoice), false);
});

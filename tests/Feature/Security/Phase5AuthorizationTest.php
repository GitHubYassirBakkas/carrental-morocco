<?php

use App\Models\Booking;
use App\Models\BookingDamage;
use App\Models\BookingInspection;
use App\Models\BookingPhoto;
use App\Models\Car;
use App\Models\Coupon;
use App\Models\CustomerProfile;
use App\Models\EmailLog;
use App\Models\Insurance;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

function phase5User(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'is_banned' => false,
    ], $attributes));
}

function phase5RegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Phase 5 Registrant',
        'email' => 'phase5-registrant@example.test',
        'phone' => '+212600000050',
        'address' => '50 Authorization Avenue',
        'city' => 'Marrakech',
        'country' => 'Morocco',
        'postal_code' => '40000',
        'date_of_birth' => now()->subYears(32)->toDateString(),
        'driving_license_number' => 'PHASE5-DL',
        'driving_license_country' => 'Morocco',
        'driving_license_issue_date' => now()->subYears(6)->toDateString(),
        'driving_license_expiry_date' => now()->addYears(4)->toDateString(),
        'driving_license_front' => UploadedFile::fake()->image('license-front.jpg'),
        'driving_license_back' => UploadedFile::fake()->image('license-back.jpg'),
        'identity_front' => UploadedFile::fake()->image('identity-front.jpg'),
        'identity_back' => UploadedFile::fake()->image('identity-back.jpg'),
        'password' => 'password',
        'password_confirmation' => 'password',
    ], $overrides);
}

function phase5Location(): Location
{
    return Location::create([
        'name' => 'Casablanca Security Desk',
        'address' => '1 Audit Avenue',
        'city' => 'Casablanca',
        'country' => 'Morocco',
        'postal_code' => '20000',
        'phone' => '+212600000000',
        'email' => 'security@example.test',
        'opening_time' => '08:00',
        'closing_time' => '20:00',
        'is_active' => true,
    ]);
}

function phase5Car(?Location $location = null): Car
{
    $location ??= phase5Location();

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

function phase5Booking(?User $user = null, array $attributes = []): Booking
{
    $user ??= phase5User();
    $location = phase5Location();
    $car = phase5Car($location);

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
        'status' => Booking::STATUS_PENDING,
        'advance_payment_amount' => 300,
        'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PENDING,
        'advance_payment_due_at' => now()->addDay(),
        'security_deposit_amount' => 0,
        'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_PENDING,
    ], $attributes));
}

function phase5Invoice(Booking $booking, array $attributes = []): Invoice
{
    return Invoice::create(array_merge([
        'booking_id' => $booking->id,
        'user_id' => $booking->user_id,
        'subtotal' => 1000,
        'tax_amount' => 0,
        'total_amount' => 1000,
        'status' => Invoice::STATUS_PENDING,
        'issued_at' => now(),
        'due_date' => now()->addDay(),
    ], $attributes));
}

function phase5Payment(Invoice $invoice, array $attributes = []): Payment
{
    return Payment::create(array_merge([
        'invoice_id' => $invoice->id,
        'user_id' => $invoice->user_id,
        'amount' => 1000,
        'method' => 'cash',
        'type' => Payment::TYPE_PAYMENT,
        'status' => Payment::STATUS_COMPLETED,
        'transaction_id' => 'phase5-'.$invoice->id,
        'paid_at' => now(),
        'notes' => 'Phase 5 security fixture',
    ], $attributes));
}

function phase5AdminRouteFixtures(): array
{
    $admin = phase5User(['role' => 'admin']);
    $customer = phase5User();
    $booking = phase5Booking($customer);
    $invoice = phase5Invoice($booking);
    $payment = phase5Payment($invoice);
    $ticket = Ticket::create([
        'user_id' => $customer->id,
        'ticket_number' => 'TKT-PHASE5',
        'subject' => 'Authorization audit',
        'category' => 'other',
        'status' => 'open',
        'priority' => 'medium',
    ]);
    TicketMessage::create([
        'ticket_id' => $ticket->id,
        'user_id' => $customer->id,
        'message' => 'Security fixture message',
        'is_admin' => false,
    ]);
    $review = Review::create([
        'user_id' => $customer->id,
        'car_id' => $booking->car_id,
        'booking_id' => $booking->id,
        'rating' => 5,
        'comment' => 'Security fixture review.',
        'is_approved' => false,
    ]);
    $insurance = Insurance::create([
        'name' => 'Phase 5 Basic',
        'type' => 'basic',
        'description' => 'Security fixture insurance',
        'fixed_price' => 0,
        'max_coverage' => 1000,
        'deductible' => 0,
        'excess_fee' => 0,
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $coupon = Coupon::create([
        'code' => 'PHASE5',
        'category' => 'welcome',
        'discount_type' => 'fixed',
        'discount_value' => 50,
        'max_uses_per_user' => 1,
        'valid_from' => now()->subDay(),
        'valid_until' => now()->addMonth(),
        'is_active' => true,
    ]);
    $emailLog = EmailLog::create([
        'user_id' => $customer->id,
        'booking_id' => $booking->id,
        'to' => 'customer@example.test',
        'subject' => 'Security fixture email',
        'content' => 'Security fixture email body',
        'status' => 'failed',
    ]);
    $inspection = BookingInspection::create([
        'booking_id' => $booking->id,
        'type' => 'checkin',
        'mileage' => 10,
        'fuel_level' => 80,
        'has_damage' => false,
        'created_by' => $admin->id,
    ]);
    $photo = BookingPhoto::create([
        'booking_inspection_id' => $inspection->id,
        'path' => 'booking-evidence/inspections/phase5.jpg',
    ]);
    $damage = BookingDamage::create([
        'booking_id' => $booking->id,
        'stage' => 'checkout',
        'part' => 'door',
        'type' => 'scratch',
        'description' => 'Security fixture damage',
        'estimated_cost' => 100,
        'is_chargeable' => true,
        'photos' => ['booking-evidence/damages/phase5.jpg'],
    ]);
    $customerProfile = CustomerProfile::create([
        'user_id' => $customer->id,
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'driving_license_number' => 'PHASE5-DOC',
        'driving_license_country' => 'Morocco',
        'driving_license_issue_date' => now()->subYears(5)->toDateString(),
        'driving_license_expiry_date' => now()->addYears(5)->toDateString(),
        'driving_license_front_path' => "private/customer-documents/{$customer->id}/driving-license/front/front.jpg",
        'driving_license_back_path' => "private/customer-documents/{$customer->id}/driving-license/back/back.jpg",
        'identity_front_path' => "private/customer-documents/{$customer->id}/identity/front/front.jpg",
        'identity_back_path' => "private/customer-documents/{$customer->id}/identity/back/back.jpg",
    ]);

    return [
        'booking' => $booking,
        'car' => $booking->car,
        'customerProfile' => $customerProfile,
        'coupon' => $coupon,
        'damage' => $damage,
        'document' => 'driving-license-front',
        'emailLog' => $emailLog,
        'inspection' => $inspection,
        'insurance' => $insurance,
        'invoice' => $invoice,
        'location' => $booking->pickupLocation,
        'payment' => $payment,
        'photo' => $photo,
        'photoIndex' => 0,
        'review' => $review,
        'ticket' => $ticket,
        'user' => $customer,
    ];
}

function phase5AdminRoutes()
{
    return collect(Route::getRoutes())
        ->filter(fn (LaravelRoute $route) => str_starts_with($route->uri(), 'admin'))
        ->filter(fn (LaravelRoute $route) => filled($route->getName()))
        ->values();
}

function phase5RouteParameters(LaravelRoute $route, array $fixtures): array
{
    $parameters = [];

    foreach ($route->parameterNames() as $parameter) {
        $parameters[$parameter] = $fixtures[$parameter];
    }

    return $parameters;
}

function phase5RouteMethod(LaravelRoute $route): string
{
    foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
        if (in_array($method, $route->methods(), true)) {
            return $method;
        }
    }

    return 'GET';
}

test('guest users cannot access authenticated customer routes', function () {
    $booking = phase5Booking();
    $ticket = Ticket::create([
        'user_id' => $booking->user_id,
        'ticket_number' => 'TKT-GUEST',
        'subject' => 'Guest boundary',
        'category' => 'other',
        'status' => 'open',
        'priority' => 'medium',
    ]);
    $notification = Notification::create([
        'user_id' => $booking->user_id,
        'type' => 'phase5',
        'title' => 'Phase 5',
        'message' => 'Guest boundary',
        'is_read' => false,
    ]);

    $routes = [
        ['GET', route('dashboard')],
        ['GET', route('profile.edit')],
        ['PATCH', route('profile.update')],
        ['GET', route('account')],
        ['POST', route('account.update')],
        ['GET', route('my_booking.index')],
        ['GET', route('bookings.show', $booking)],
        ['GET', route('bookings.success', $booking)],
        ['POST', route('bookings.cancel', $booking)],
        ['GET', route('payments.show', $booking)],
        ['POST', route('payments.store', $booking)],
        ['GET', route('reviews.create', $booking)],
        ['POST', route('reviews.store', $booking)],
        ['POST', route('tickets.store')],
        ['GET', route('tickets.index')],
        ['GET', route('tickets.show', $ticket)],
        ['POST', route('tickets.reply', $ticket)],
        ['GET', route('notifications.index')],
        ['PATCH', route('notifications.read', $notification)],
        ['PATCH', route('notifications.read-all')],
        ['GET', route('wishlist.index')],
        ['POST', route('wishlist.store', $booking->car)],
        ['DELETE', route('wishlist.destroy', $booking->car)],
    ];

    foreach ($routes as [$method, $uri]) {
        $this->call($method, $uri)->assertRedirect(route('login'));
    }
});

test('guest users cannot access any admin route', function () {
    $fixtures = phase5AdminRouteFixtures();

    foreach (phase5AdminRoutes() as $route) {
        $uri = route($route->getName(), phase5RouteParameters($route, $fixtures));

        $this->call(phase5RouteMethod($route), $uri)->assertRedirect(route('login'));
    }
});

test('normal customers receive forbidden responses for every admin route', function () {
    $customer = phase5User();
    $fixtures = phase5AdminRouteFixtures();

    foreach (phase5AdminRoutes() as $route) {
        $uri = route($route->getName(), phase5RouteParameters($route, $fixtures));

        $this->actingAs($customer)
            ->call(phase5RouteMethod($route), $uri)
            ->assertForbidden();
    }
});

test('customers cannot access or mutate another customers booking workflow resources', function () {
    $userA = phase5User();
    $userB = phase5User();
    $booking = phase5Booking($userB, ['status' => Booking::STATUS_COMPLETED]);

    $this->actingAs($userA)->get(route('bookings.show', $booking))->assertForbidden();
    $this->actingAs($userA)->get(route('bookings.success', $booking))->assertForbidden();
    $this->actingAs($userA)->post(route('bookings.cancel', $booking))->assertForbidden();
    $this->actingAs($userA)->get(route('payments.show', $booking))->assertForbidden();
    $this->actingAs($userA)->post(route('payments.store', $booking), [
        'payment_method' => 'cash',
    ])->assertForbidden();
    $this->actingAs($userA)->get(route('reviews.create', $booking))->assertForbidden();
    $this->actingAs($userA)->post(route('reviews.store', $booking), [
        'rating' => 5,
        'comment' => 'This should not be accepted.',
    ])->assertForbidden();
});

test('customers cannot access or reply to another customers support ticket', function () {
    $userA = phase5User();
    $userB = phase5User();
    $ticket = Ticket::create([
        'user_id' => $userB->id,
        'ticket_number' => 'TKT-IDOR',
        'subject' => 'Ticket ownership',
        'category' => 'other',
        'status' => 'open',
        'priority' => 'medium',
    ]);

    $this->actingAs($userA)->get(route('tickets.show', $ticket))->assertForbidden();
    $this->actingAs($userA)->post(route('tickets.reply', $ticket), [
        'message' => 'Unauthorized reply',
    ])->assertForbidden();
});

test('customers cannot mark another customers notification as read', function () {
    $userA = phase5User();
    $userB = phase5User();
    $notification = Notification::create([
        'user_id' => $userB->id,
        'type' => 'phase5',
        'title' => 'Phase 5',
        'message' => 'Notification ownership',
        'is_read' => false,
    ]);

    $this->actingAs($userA)
        ->patch(route('notifications.read', $notification))
        ->assertForbidden();

    expect($notification->fresh()->is_read)->toBeFalse();
});

test('registration and profile updates cannot escalate role or ban state', function () {
    Storage::fake('local');

    $this->post(route('register'), phase5RegistrationPayload([
        'role' => 'admin',
        'is_banned' => true,
    ]));

    $registered = User::where('email', 'phase5-registrant@example.test')->firstOrFail();

    expect($registered->role)->toBe('user')
        ->and((bool) $registered->is_banned)->toBeFalse();

    $user = phase5User([
        'email' => 'phase5-profile@example.test',
        'role' => 'user',
        'is_banned' => false,
    ]);

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => 'Phase 5 Profile',
        'email' => 'phase5-profile@example.test',
        'phone' => '+212600000000',
        'role' => 'admin',
        'is_banned' => true,
    ])->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->role)->toBe('user')
        ->and((bool) $user->is_banned)->toBeFalse();
});

test('payment history only lists payments owned by the authenticated customer', function () {
    $userA = phase5User();
    $userB = phase5User();

    $bookingA = phase5Booking($userA);
    $bookingB = phase5Booking($userB);
    phase5Payment(phase5Invoice($bookingA), ['transaction_id' => 'phase5-owned-payment']);
    phase5Payment(phase5Invoice($bookingB), ['transaction_id' => 'phase5-other-payment']);

    $this->actingAs($userA)
        ->get(route('profile.payment-history'))
        ->assertOk()
        ->assertSee('#'.$bookingA->id)
        ->assertDontSee('#'.$bookingB->id);
});

test('admin dashboard get request does not cancel overdue bookings', function () {
    $admin = phase5User(['role' => 'admin']);
    $booking = phase5Booking(null, [
        'status' => Booking::STATUS_PENDING,
        'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PENDING,
        'advance_payment_due_at' => now()->subHour(),
    ]);

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

    expect($booking->fresh()->status)->toBe(Booking::STATUS_PENDING);
});

test('scheduled overdue cancellation command still cancels overdue bookings', function () {
    $booking = phase5Booking(null, [
        'status' => Booking::STATUS_PENDING,
        'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PENDING,
        'advance_payment_due_at' => now()->subHour(),
    ]);

    $this->artisan('bookings:cancel-overdue')
        ->expectsOutput('Cancelled 1 overdue bookings')
        ->assertExitCode(0);

    expect($booking->fresh()->status)->toBe(Booking::STATUS_CANCELLED);
});

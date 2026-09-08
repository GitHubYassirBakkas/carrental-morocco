<?php

use App\Models\Booking;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payment;
use App\Models\User;
use App\Services\StripePaymentIntentGateway;

function low4User(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'is_banned' => false,
    ], $attributes));
}

function low4Booking(?User $user = null, bool $paid = true): Booking
{
    $user ??= low4User();
    $location = Location::factory()->create();
    $car = Car::factory()->create([
        'location_id' => $location->id,
        'security_deposit_amount' => 0,
    ]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'pickup_location_id' => $location->id,
        'dropoff_location_id' => $location->id,
        'total_amount' => 1000,
        'security_deposit_amount' => 0,
    ]);

    $invoice = Invoice::factory()->create([
        'booking_id' => $booking->id,
        'user_id' => $user->id,
        'total_amount' => 1000,
        'status' => $paid ? Invoice::STATUS_PAID : Invoice::STATUS_PENDING,
    ]);

    if ($paid) {
        Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => 1000,
            'method' => 'card',
            'type' => Payment::TYPE_PAYMENT,
            'status' => Payment::STATUS_COMPLETED,
            'paid_at' => now(),
        ]);
    }

    return $booking;
}

test('primary layouts include viewport metadata for responsive rendering', function () {
    foreach ([
        resource_path('views/layouts/app.blade.php'),
        resource_path('views/layouts/auth.blade.php'),
        resource_path('views/layouts/guest.blade.php'),
        resource_path('views/admin/layouts/app.blade.php'),
        resource_path('views/admin/layout.blade.php'),
    ] as $layout) {
        expect(file_get_contents($layout))
            ->toContain('name="viewport"')
            ->toContain('width=device-width');
    }
});

test('mobile navigation has a burger menu and real route-backed customer links', function () {
    $navbar = file_get_contents(resource_path('views/partials/navbar.blade.php'));

    expect($navbar)->toContain('mobileMenuOpen')
        ->toContain('class="md:hidden w-9 h-9')
        ->toContain("route('home')")
        ->toContain("route('cars.index')")
        ->toContain("route('about')")
        ->toContain("route('contact')")
        ->not->toContain('<a href="#"                         class="mobile-link">{{ __(\'messages.contact\') }}</a>');
});

test('wide admin list tables use horizontal overflow wrappers', function () {
    $views = [
        resource_path('views/admin/bookings/index.blade.php') => 'min-w',
        resource_path('views/admin/invoices/index.blade.php') => 'min-w-[760px]',
        resource_path('views/admin/users/index.blade.php') => 'min-w-[900px]',
        resource_path('views/admin/email-logs/index.blade.php') => 'min-w-[820px]',
        resource_path('views/admin/support/index.blade.php') => 'min-w-[860px]',
        resource_path('views/admin/refunds/index.blade.php') => 'overflow-x-auto',
        resource_path('views/admin/coupons/index.blade.php') => 'overflow-x-auto',
        resource_path('views/admin/reviews/index.blade.php') => 'overflow-x-auto',
        resource_path('views/admin/locations/index.blade.php') => 'overflow-x-auto',
        resource_path('views/admin/cars/index.blade.php') => 'ac-table-wrap',
    ];

    foreach ($views as $view => $expected) {
        $html = file_get_contents($view);

        expect(str_contains($html, 'overflow-x-auto') || str_contains($html, 'ac-table-wrap'))->toBeTrue();

        expect($html)->toContain($expected);
    }
});

test('customer forms and narrow-screen structures render responsive markup', function () {
    $customer = low4User();
    $booking = low4Booking($customer, paid: false);

    $stripe = \Mockery::mock(StripePaymentIntentGateway::class);
    $stripe->shouldReceive('create')
        ->once()
        ->andReturn((object) [
            'id' => 'pi_low4_rental',
            'client_secret' => 'pi_low4_rental_secret',
            'status' => 'requires_payment_method',
        ]);
    $this->app->instance(StripePaymentIntentGateway::class, $stripe);

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('name="viewport"', false)
        ->assertSee('auth-form', false);

    $this->get(route('register'))
        ->assertOk()
        ->assertSee('auth-form', false);

    $this->actingAs($customer)
        ->get(route('profile.driver.edit'))
        ->assertOk()
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertSee('grid grid-cols-1 md:grid-cols-2', false)
        ->assertSee('driver-document-input', false);

    $this->actingAs($customer)
        ->get(route('payments.show', $booking))
        ->assertOk()
        ->assertSee('grid grid-cols-1 lg:grid-cols-3', false)
        ->assertSee('p-5 sm:p-8', false)
        ->assertSee('id="card-element"', false);
});

test('tickets and notifications use mobile-safe wrapping structures', function () {
    expect(file_get_contents(resource_path('views/tickets/index.blade.php')))
        ->toContain('flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4')
        ->toContain('grid grid-cols-2 sm:grid-cols-4');

    expect(file_get_contents(resource_path('views/notifications/index.blade.php')))
        ->toContain('flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4')
        ->toContain('text-3xl sm:text-5xl')
        ->toContain('flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2')
        ->toContain('flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3');
});

test('important admin pages still render for authorized admins', function () {
    $admin = low4User(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($admin)->get(route('admin.bookings.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.invoices.index'))->assertOk()->assertSee('overflow-x-auto', false);
    $this->actingAs($admin)->get(route('admin.support.index'))->assertOk()->assertSee('overflow-x-auto', false);
    $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee('overflow-x-auto', false);
    $this->actingAs($admin)->get(route('admin.coupons.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.cars.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.refunds.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.reviews.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.settings.index'))->assertOk();
});

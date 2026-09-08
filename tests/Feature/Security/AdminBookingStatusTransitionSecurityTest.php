<?php

use App\Models\Booking;
use App\Models\BookingInspection;
use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

function high2User(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'role' => 'user',
        'is_banned' => false,
    ], $attributes));
}

function high2Booking(?User $user = null, array $attributes = []): Booking
{
    $user ??= high2User();

    return Booking::factory()->create(array_merge([
        'user_id' => $user->id,
        'status' => Booking::STATUS_PENDING,
    ], $attributes));
}

function high2VerifiedDriverProfile(User $user, User $admin): CustomerProfile
{
    $paths = [
        'driving_license_front_path' => "private/customer-documents/{$user->id}/driving-license/front/front.jpg",
        'driving_license_back_path' => "private/customer-documents/{$user->id}/driving-license/back/back.jpg",
        'identity_front_path' => "private/customer-documents/{$user->id}/identity/front/front.jpg",
        'identity_back_path' => "private/customer-documents/{$user->id}/identity/back/back.jpg",
    ];

    foreach ($paths as $path) {
        Storage::disk('local')->put($path, 'document');
    }

    $profile = CustomerProfile::create(array_merge([
        'user_id' => $user->id,
        'date_of_birth' => now()->subYears(30)->toDateString(),
        'driving_license_number' => 'HIGH2-DL',
        'driving_license_country' => 'Morocco',
        'driving_license_issue_date' => now()->subYears(5)->toDateString(),
        'driving_license_expiry_date' => now()->addYears(5)->toDateString(),
    ], $paths));

    $profile->forceFill([
        'driver_verification_status' => CustomerProfile::STATUS_VERIFIED,
        'driver_verification_submitted_at' => now()->subDay(),
        'driver_verified_at' => now()->subHour(),
        'driver_verified_by' => $admin->id,
    ])->save();

    return $profile->fresh();
}

test('generic admin booking status update route is not registered', function () {
    expect(Route::has('admin.bookings.update'))->toBeFalse();
});

test('admin cannot directly write sensitive booking statuses through the legacy generic endpoint', function (string $targetStatus) {
    $admin = high2User(['role' => 'admin']);
    $booking = high2Booking();

    $this->actingAs($admin)
        ->put("/admin/bookings/{$booking->id}", [
            'status' => $targetStatus,
        ])
        ->assertStatus(405);

    expect($booking->fresh()->status)->toBe(Booking::STATUS_PENDING);
})->with([
    'pending no-op write' => Booking::STATUS_PENDING,
    'confirmed bypass' => Booking::STATUS_CONFIRMED,
    'cancelled bypass' => Booking::STATUS_CANCELLED,
    'active bypass' => Booking::STATUS_ACTIVE,
    'completed bypass' => Booking::STATUS_COMPLETED,
]);

test('non admin cannot use admin booking transition actions', function () {
    $customer = high2User();
    $booking = high2Booking();

    $this->actingAs($customer)
        ->post(route('admin.bookings.confirm', $booking))
        ->assertForbidden();

    expect($booking->fresh()->status)->toBe(Booking::STATUS_PENDING);
});

test('cancelled and completed bookings cannot be reopened through the legacy generic endpoint', function (string $initialStatus, string $targetStatus) {
    $admin = high2User(['role' => 'admin']);
    $booking = high2Booking(attributes: ['status' => $initialStatus]);

    $this->actingAs($admin)
        ->put("/admin/bookings/{$booking->id}", [
            'status' => $targetStatus,
        ])
        ->assertStatus(405);

    expect($booking->fresh()->status)->toBe($initialStatus);
})->with([
    'cancelled to pending' => [Booking::STATUS_CANCELLED, Booking::STATUS_PENDING],
    'cancelled to confirmed' => [Booking::STATUS_CANCELLED, Booking::STATUS_CONFIRMED],
    'completed to pending' => [Booking::STATUS_COMPLETED, Booking::STATUS_PENDING],
    'completed to active' => [Booking::STATUS_COMPLETED, Booking::STATUS_ACTIVE],
]);

test('admin cancellation uses the service-backed cancellation flow', function () {
    $admin = high2User(['role' => 'admin']);
    $booking = high2Booking(attributes: [
        'status' => Booking::STATUS_CONFIRMED,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.bookings.show', $booking))
        ->post(route('admin.bookings.cancel', $booking))
        ->assertRedirect(route('admin.bookings.show', $booking));

    $booking->refresh();

    expect($booking->status)->toBe(Booking::STATUS_CANCELLED)
        ->and($booking->car->fresh()->is_available)->toBeTrue();

    expect(Notification::where('user_id', $booking->user_id)
        ->where('type', 'booking_cancelled')
        ->whereJsonContains('data->booking_id', $booking->id)
        ->exists())->toBeTrue();
});

test('admin confirmation still uses the service-backed confirmation flow', function () {
    Mail::fake();

    $admin = high2User(['role' => 'admin']);
    $booking = high2Booking();

    $this->actingAs($admin)
        ->from(route('admin.bookings.show', $booking))
        ->post(route('admin.bookings.confirm', $booking))
        ->assertRedirect(route('admin.bookings.show', $booking));

    $booking->refresh();

    expect($booking->status)->toBe(Booking::STATUS_CONFIRMED)
        ->and($booking->advance_payment_due_at)->not->toBeNull()
        ->and($booking->invoice)->toBeInstanceOf(Invoice::class);

    expect(Notification::where('user_id', $booking->user_id)
        ->where('type', 'booking_approved')
        ->whereJsonContains('data->booking_id', $booking->id)
        ->exists())->toBeTrue();
});

test('admin start action cannot bypass driver verification and inspection requirements', function () {
    $admin = high2User(['role' => 'admin']);
    $booking = high2Booking(attributes: [
        'status' => Booking::STATUS_CONFIRMED,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.bookings.show', $booking))
        ->post(route('admin.bookings.start', $booking))
        ->assertRedirect(route('admin.bookings.show', $booking))
        ->assertSessionHasErrors();

    expect($booking->fresh()->status)->toBe(Booking::STATUS_CONFIRMED);
});

test('admin complete action cannot bypass checkout inspection requirements', function () {
    $admin = high2User(['role' => 'admin']);
    $booking = high2Booking(attributes: [
        'status' => Booking::STATUS_ACTIVE,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.bookings.show', $booking))
        ->post(route('admin.bookings.complete', $booking))
        ->assertRedirect(route('admin.bookings.show', $booking))
        ->assertSessionHasErrors();

    expect($booking->fresh()->status)->toBe(Booking::STATUS_ACTIVE);
});

test('existing dedicated start and complete actions still work when business prerequisites are met', function () {
    Storage::fake('local');

    $admin = high2User(['role' => 'admin']);
    $customer = high2User();
    high2VerifiedDriverProfile($customer, $admin);
    $booking = high2Booking($customer, [
        'status' => Booking::STATUS_CONFIRMED,
    ]);

    BookingInspection::factory()->create([
        'booking_id' => $booking->id,
        'type' => 'checkin',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.bookings.show', $booking))
        ->post(route('admin.bookings.start', $booking))
        ->assertRedirect(route('admin.bookings.show', $booking))
        ->assertSessionHasNoErrors();

    expect($booking->fresh()->status)->toBe(Booking::STATUS_ACTIVE);

    BookingInspection::factory()->create([
        'booking_id' => $booking->id,
        'type' => 'checkout',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.bookings.show', $booking))
        ->post(route('admin.bookings.complete', $booking))
        ->assertRedirect(route('admin.bookings.show', $booking))
        ->assertSessionHasNoErrors();

    expect($booking->fresh()->status)->toBe(Booking::STATUS_COMPLETED);

    expect(Payment::query()->count())->toBe(0);
});

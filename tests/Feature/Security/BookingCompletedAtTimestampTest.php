<?php

use App\Domain\Booking\BookingStateMachine;
use App\Events\BookingCompleted;
use App\Models\Booking;
use App\Models\BookingInspection;
use App\Models\CustomerLoyaltyReward;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

afterEach(function () {
    Carbon::setTestNow();
});

function medium6Booking(array $overrides = [], bool $withCheckoutInspection = false): Booking
{
    $booking = Booking::factory()->create(array_merge([
        'status' => Booking::STATUS_ACTIVE,
        'completed_at' => null,
    ], $overrides));

    if ($withCheckoutInspection) {
        BookingInspection::factory()->create([
            'booking_id' => $booking->id,
            'type' => 'checkout',
        ]);
    }

    return $booking;
}

test('successful rental completion sets completed status and immutable completed at timestamp', function () {
    Mail::fake();
    Carbon::setTestNow(Carbon::parse('2026-09-02 10:15:00'));
    $booking = medium6Booking(withCheckoutInspection: true);

    $completed = app(BookingService::class)->completeRental($booking);

    expect($completed->status)->toBe(Booking::STATUS_COMPLETED)
        ->and($completed->completed_at)->toBeInstanceOf(Carbon::class)
        ->and($completed->completed_at->toDateTimeString())->toBe('2026-09-02 10:15:00')
        ->and($booking->fresh()->completed_at->toDateTimeString())->toBe('2026-09-02 10:15:00');
});

test('confirmed and active bookings do not have completed at before completion', function () {
    $confirmed = medium6Booking([
        'status' => Booking::STATUS_CONFIRMED,
    ]);
    $active = medium6Booking();

    expect($confirmed->completed_at)->toBeNull()
        ->and($active->completed_at)->toBeNull();
});

test('failed rental completion leaves completed at null', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-02 11:00:00'));
    $booking = medium6Booking(withCheckoutInspection: false);

    expect(fn () => app(BookingService::class)->completeRental($booking))
        ->toThrow(Exception::class, 'Check-out inspection required before completing rental');

    expect($booking->fresh()->status)->toBe(Booking::STATUS_ACTIVE)
        ->and($booking->fresh()->completed_at)->toBeNull();
});

test('repeated completion attempt does not overwrite original completed at', function () {
    Mail::fake();
    Carbon::setTestNow(Carbon::parse('2026-09-02 12:00:00'));
    $booking = medium6Booking(withCheckoutInspection: true);

    app(BookingService::class)->completeRental($booking);
    $originalCompletedAt = $booking->fresh()->completed_at->copy();

    Carbon::setTestNow(Carbon::parse('2026-09-03 12:00:00'));

    expect(fn () => app(BookingService::class)->completeRental($booking->fresh()))
        ->toThrow(Exception::class, 'Only active rentals can be completed');

    expect($booking->fresh()->completed_at->toDateTimeString())
        ->toBe($originalCompletedAt->toDateTimeString());
});

test('booking completed event listeners see persisted completed at', function () {
    Mail::fake();
    Carbon::setTestNow(Carbon::parse('2026-09-02 13:00:00'));
    $booking = medium6Booking(withCheckoutInspection: true);
    $eventCompletedAt = null;
    $freshCompletedAt = null;

    Event::listen(BookingCompleted::class, function (BookingCompleted $event) use (&$eventCompletedAt, &$freshCompletedAt) {
        $eventCompletedAt = $event->booking->completed_at?->toDateTimeString();
        $freshCompletedAt = $event->booking->fresh()->completed_at?->toDateTimeString();
    });

    app(BookingService::class)->completeRental($booking);

    expect($eventCompletedAt)->toBe('2026-09-02 13:00:00')
        ->and($freshCompletedAt)->toBe('2026-09-02 13:00:00');
});

test('loyalty reward flow still works when completion sets completed at', function () {
    Mail::fake();
    $user = User::factory()->create();

    foreach (range(1, 9) as $index) {
        medium6Booking([
            'user_id' => $user->id,
            'status' => Booking::STATUS_COMPLETED,
            'completed_at' => Carbon::parse('2026-08-01 09:00:00')->addDays($index),
        ]);
    }

    Carbon::setTestNow(Carbon::parse('2026-09-02 14:00:00'));
    $booking = medium6Booking([
        'user_id' => $user->id,
    ], withCheckoutInspection: true);

    app(BookingService::class)->completeRental($booking);

    expect(CustomerLoyaltyReward::where('user_id', $user->id)->where('milestone', 10)->count())->toBe(1)
        ->and($booking->fresh()->completed_at->toDateTimeString())->toBe('2026-09-02 14:00:00');
});

test('cancellation does not set completed at', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-02 15:00:00'));
    $booking = medium6Booking([
        'status' => Booking::STATUS_PENDING,
    ]);

    app(BookingService::class)->cancelBooking($booking, 'Customer cancelled');

    expect($booking->fresh()->status)->toBe(Booking::STATUS_CANCELLED)
        ->and($booking->fresh()->completed_at)->toBeNull();
});

test('domain state machine completed transition sets completed at once', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-02 16:00:00'));
    $booking = medium6Booking();

    app(BookingStateMachine::class)->transition($booking, Booking::STATUS_COMPLETED, [
        'source_event_id' => 'evt_medium6_complete',
    ]);

    expect($booking->fresh()->status)->toBe(Booking::STATUS_COMPLETED)
        ->and($booking->fresh()->completed_at->toDateTimeString())->toBe('2026-09-02 16:00:00');
});

test('canonical admin complete action uses service completion timestamp path', function () {
    Mail::fake();
    Carbon::setTestNow(Carbon::parse('2026-09-02 17:00:00'));
    $admin = User::factory()->create(['role' => 'admin']);
    $booking = medium6Booking(withCheckoutInspection: true);

    $this->actingAs($admin)
        ->from(route('admin.bookings.show', $booking))
        ->post(route('admin.bookings.complete', $booking))
        ->assertRedirect(route('admin.bookings.show', $booking))
        ->assertSessionHasNoErrors();

    expect($booking->fresh()->status)->toBe(Booking::STATUS_COMPLETED)
        ->and($booking->fresh()->completed_at->toDateTimeString())->toBe('2026-09-02 17:00:00');
});

<?php

use App\Models\Booking;
use App\Models\BookingInspection;
use App\Models\Car;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\Payment;
use App\Models\User;
use App\Services\BookingTimelineService;
use App\ViewModels\BookingTimelineViewModel;
use App\ViewModels\TimelineEventViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin timeline builds successfully', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'status' => 'confirmed',
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    expect($timeline)->toBeInstanceOf(BookingTimelineViewModel::class);
    expect($timeline->events)->toBeArray();
    expect($timeline->events)->not->toBeEmpty();
});

test('admin timeline includes booking created event', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    $createdEvent = collect($timeline->events)->first(fn ($e) => $e->title === 'Booking Created');
    expect($createdEvent)->not->toBeNull();
    expect($createdEvent->completed)->toBeTrue();
});

test('admin timeline includes payment event when payment exists', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
    ]);
    $invoice = Invoice::factory()->create(['booking_id' => $booking->id]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'type' => 'payment',
        'status' => 'completed',
        'method' => 'stripe',
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    $paymentEvent = collect($timeline->events)->first(fn ($e) => $e->title === 'Payment Completed');
    expect($paymentEvent)->not->toBeNull();
    expect($paymentEvent->description)->toContain('Stripe');
});

test('admin timeline does not include confirmed event (no accurate timestamp)', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'status' => 'confirmed',
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    // Confirmed event should NOT appear because we don't have a real confirmed_at timestamp
    $confirmedEvent = collect($timeline->events)->first(fn ($e) => $e->title === 'Confirmed');
    expect($confirmedEvent)->toBeNull();
});

test('admin timeline includes rental started for active booking', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'status' => 'active',
        'started_at' => now()->subHours(1),
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    $startedEvent = collect($timeline->events)->first(fn ($e) => $e->title === 'Rental Started');
    expect($startedEvent)->not->toBeNull();
});

test('admin timeline does not include rental started without started_at', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'status' => 'active',
        // No started_at - should not show event
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    $startedEvent = collect($timeline->events)->first(fn ($e) => $e->title === 'Rental Started');
    expect($startedEvent)->toBeNull();
});

test('admin timeline includes rental completed for completed booking', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'status' => 'completed',
        'completed_at' => now()->subHours(1),
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    $completedEvent = collect($timeline->events)->first(fn ($e) => $e->title === 'Rental Completed');
    expect($completedEvent)->not->toBeNull();
});

test('admin timeline does not include rental completed without completed_at', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'status' => 'completed',
        // No completed_at - should not show event
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    $completedEvent = collect($timeline->events)->first(fn ($e) => $e->title === 'Rental Completed');
    expect($completedEvent)->toBeNull();
});

test('admin timeline does not include cancelled event (no accurate timestamp)', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'status' => 'cancelled',
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    // Cancelled event should NOT appear because we don't have a real cancelled_at timestamp
    $cancelledEvent = collect($timeline->events)->first(fn ($e) => $e->title === 'Cancelled');
    expect($cancelledEvent)->toBeNull();
});

test('admin timeline includes refund event for refunded booking', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'status' => 'cancelled',
    ]);
    $invoice = Invoice::factory()->create(['booking_id' => $booking->id]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'type' => 'payment',
        'status' => 'completed',
        'amount' => 1000,
    ]);
    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'type' => 'refund',
        'status' => 'completed',
        'amount' => 1000,
        'paid_at' => now()->subHours(1),
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    $refundEvent = collect($timeline->events)->first(fn ($e) => str_contains($e->title, 'Refund'));
    expect($refundEvent)->not->toBeNull();
    // Verify it uses the refund payment timestamp, not booking.updated_at
    expect($refundEvent->date)->toEqual(now()->subHours(1)->startOfSecond());
});

test('admin timeline includes security deposit held when intent exists', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'security_deposit_intent_id' => 'pi_test_123',
        'security_deposit_capturable_amount' => 5000,
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    $heldEvent = collect($timeline->events)->first(fn ($e) => $e->title === 'Security Deposit Held');
    expect($heldEvent)->not->toBeNull();
});

test('admin timeline includes security deposit captured when captured', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'security_deposit_captured_at' => now()->subHours(1),
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    $capturedEvent = collect($timeline->events)->first(fn ($e) => $e->title === 'Security Deposit Captured');
    expect($capturedEvent)->not->toBeNull();
});

test('admin timeline includes security deposit refunded when refunded', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'security_deposit_refunded_at' => now()->subHours(1),
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    $refundedEvent = collect($timeline->events)->first(fn ($e) => $e->title === 'Security Deposit Refunded');
    expect($refundedEvent)->not->toBeNull();
});

test('admin timeline includes check-in inspection when exists', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
    ]);
    BookingInspection::factory()->create([
        'booking_id' => $booking->id,
        'type' => 'checkin',
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    $checkinEvent = collect($timeline->events)->first(fn ($e) => $e->title === 'Check-in Inspection');
    expect($checkinEvent)->not->toBeNull();
});

test('admin timeline includes check-out inspection when exists', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
    ]);
    BookingInspection::factory()->create([
        'booking_id' => $booking->id,
        'type' => 'checkout',
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    $checkoutEvent = collect($timeline->events)->first(fn ($e) => $e->title === 'Check-out Inspection');
    expect($checkoutEvent)->not->toBeNull();
});

test('admin timeline events are chronologically ordered', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'status' => 'completed',
        'started_at' => now()->subHours(3),
        'completed_at' => now()->subHours(1),
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    $dates = collect($timeline->events)->map(fn ($e) => $e->date->timestamp);
    $sortedDates = $dates->sort()->values();

    expect($dates->values())->toEqual($sortedDates->values());
});

test('admin timeline does not break when optional events missing', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'status' => 'pending',
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    expect($timeline->events)->not->toBeEmpty();
    expect($timeline->events)->toHaveCount(1); // Only Booking Created
});

test('customer timeline remains unchanged', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'status' => 'confirmed',
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildTimeline($booking);

    expect($timeline)->toBeInstanceOf(BookingTimelineViewModel::class);
    expect($timeline->events)->not->toBeEmpty();

    // Customer timeline should not have admin-specific events
    $adminOnlyEvents = ['Rental Started', 'Rental Completed', 'Security Deposit Held',
        'Security Deposit Captured', 'Security Deposit Refunded',
        'Check-in Inspection', 'Check-out Inspection'];

    foreach ($adminOnlyEvents as $eventTitle) {
        expect(collect($timeline->events)->first(fn ($e) => $e->title === $eventTitle))->toBeNull();
    }

    // Customer timeline should also not show events without accurate timestamps
    expect(collect($timeline->events)->first(fn ($e) => $e->title === 'Confirmed'))->toBeNull();
});

test('admin timeline uses timeline event view models', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    foreach ($timeline->events as $event) {
        expect($event)->toBeInstanceOf(TimelineEventViewModel::class);
    }
});

test('admin timeline has deterministic ordering for same timestamps', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $sameTime = now()->subHours(1);
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'security_deposit_intent_id' => 'pi_test_123',
        'security_deposit_capturable_amount' => 5000,
        'created_at' => $sameTime,
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    // Build timeline twice and verify same order
    $timeline2 = $service->buildAdminTimeline($booking);

    $titles1 = collect($timeline->events)->map(fn ($e) => $e->title)->values();
    $titles2 = collect($timeline2->events)->map(fn ($e) => $e->title)->values();

    expect($titles1)->toEqual($titles2);
});

test('regression: admin timeline does not use updated_at as event timestamp', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'status' => 'cancelled',
        'updated_at' => now()->subMinutes(5), // Recent update
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    // Verify no event uses updated_at as its timestamp
    foreach ($timeline->events as $event) {
        expect($event->date->timestamp)->not->toBe($booking->updated_at->timestamp);
    }
});

test('regression: admin timeline does not use start_date as rental started timestamp', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'status' => 'active',
        'start_date' => now()->subHours(5), // Scheduled date
        'started_at' => null, // No actual start timestamp
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    // Rental Started should not appear because started_at is null
    $startedEvent = collect($timeline->events)->first(fn ($e) => $e->title === 'Rental Started');
    expect($startedEvent)->toBeNull();
});

test('regression: admin timeline does not use end_date as rental completed timestamp', function () {
    $user = User::factory()->create();
    $location = Location::factory()->create();
    $car = Car::factory()->create(['location_id' => $location->id]);

    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'car_id' => $car->id,
        'status' => 'completed',
        'end_date' => now()->subHours(5), // Scheduled date
        'completed_at' => null, // No actual completion timestamp
    ]);

    $service = app(BookingTimelineService::class);
    $timeline = $service->buildAdminTimeline($booking);

    // Rental Completed should not appear because completed_at is null
    $completedEvent = collect($timeline->events)->first(fn ($e) => $e->title === 'Rental Completed');
    expect($completedEvent)->toBeNull();
});

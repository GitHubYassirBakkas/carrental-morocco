<?php

use App\Models\Booking;
use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\Review;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\AdminAttentionService;

function adminAttentionAdmin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function adminAttentionCustomer(): User
{
    return User::factory()->create(['role' => 'user']);
}

function adminAttentionProfile(User $user, string $status): CustomerProfile
{
    $profile = CustomerProfile::create(['user_id' => $user->id]);

    $profile->forceFill([
        'driver_verification_status' => $status,
        'driver_verification_submitted_at' => $status === CustomerProfile::STATUS_PENDING ? now() : null,
    ])->save();

    return $profile->fresh();
}

function adminAttentionTicket(string $status = 'open'): Ticket
{
    return Ticket::create([
        'user_id' => adminAttentionCustomer()->id,
        'ticket_number' => 'TKT-'.str_pad((string) (Ticket::count() + 1), 4, '0', STR_PAD_LEFT),
        'subject' => 'Support request',
        'category' => 'booking',
        'status' => $status,
        'priority' => 'medium',
    ]);
}

function adminAttentionTicketMessage(Ticket $ticket, array $attributes = []): TicketMessage
{
    return TicketMessage::create(array_merge([
        'ticket_id' => $ticket->id,
        'user_id' => $ticket->user_id,
        'message' => 'Customer needs help with a booking.',
        'is_admin' => false,
        'is_read' => false,
    ], $attributes));
}

function adminAttentionReview(array $attributes = []): Review
{
    $booking = Booking::factory()->create(['status' => Booking::STATUS_COMPLETED]);

    return Review::create(array_merge([
        'user_id' => $booking->user_id,
        'car_id' => $booking->car_id,
        'booking_id' => $booking->id,
        'rating' => 5,
        'comment' => 'Great rental.',
        'is_approved' => false,
    ], $attributes));
}

test('zero pending bookings do not render a booking sidebar badge', function () {
    Booking::factory()->create(['status' => Booking::STATUS_CONFIRMED]);

    $this->actingAs(adminAttentionAdmin())
        ->get(route('admin.bookings.index'))
        ->assertOk()
        ->assertDontSee('data-admin-attention-key="bookings"', false);
});

test('pending booking count renders correctly in the sidebar', function () {
    Booking::factory()->count(2)->create(['status' => Booking::STATUS_PENDING]);
    Booking::factory()->create(['status' => Booking::STATUS_COMPLETED]);

    $this->actingAs(adminAttentionAdmin())
        ->get(route('admin.bookings.index'))
        ->assertOk()
        ->assertSee('data-admin-attention-key="bookings"', false)
        ->assertSee('Bookings needs attention: 2', false)
        ->assertSee('>2</span>', false);
});

test('support unread customer ticket count renders correctly in the sidebar', function () {
    $actionable = adminAttentionTicket('open');
    adminAttentionTicketMessage($actionable);
    adminAttentionTicketMessage($actionable);

    $resolved = adminAttentionTicket('resolved');
    adminAttentionTicketMessage($resolved);

    $adminMessage = adminAttentionTicket('open');
    adminAttentionTicketMessage($adminMessage, [
        'user_id' => adminAttentionAdmin()->id,
        'is_admin' => true,
    ]);

    $this->actingAs(adminAttentionAdmin())
        ->get(route('admin.support.index'))
        ->assertOk()
        ->assertSee('data-admin-attention-key="support"', false)
        ->assertSee('Support needs attention: 2', false);
});

test('pending driver verification count renders correctly in the sidebar', function () {
    adminAttentionProfile(adminAttentionCustomer(), CustomerProfile::STATUS_PENDING);
    adminAttentionProfile(adminAttentionCustomer(), CustomerProfile::STATUS_VERIFIED);
    adminAttentionProfile(adminAttentionCustomer(), CustomerProfile::STATUS_INCOMPLETE);

    $this->actingAs(adminAttentionAdmin())
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('data-admin-attention-key="users"', false)
        ->assertSee('Users needs attention: 1', false);
});

test('pending and partial invoice count renders correctly in the sidebar', function () {
    Invoice::factory()->create(['status' => Invoice::STATUS_PENDING]);
    Invoice::factory()->create(['status' => Invoice::STATUS_PARTIAL]);
    Invoice::factory()->create([
        'booking_id' => Booking::factory()->create(['status' => Booking::STATUS_COMPLETED])->id,
        'status' => Invoice::STATUS_PAID,
    ]);

    $this->actingAs(adminAttentionAdmin())
        ->get(route('admin.invoices.index'))
        ->assertOk()
        ->assertSee('data-admin-attention-key="invoices"', false)
        ->assertSee('Invoices needs attention: 2', false);
});

test('pending review count renders correctly in the sidebar', function () {
    adminAttentionReview();
    adminAttentionReview(['is_approved' => true, 'approved_at' => now()]);

    $this->actingAs(adminAttentionAdmin())
        ->get(route('admin.reviews.index'))
        ->assertOk()
        ->assertSee('data-admin-attention-key="reviews"', false)
        ->assertSee('Reviews needs attention: 1', false);
});

test('completed and resolved records are excluded from attention counts', function () {
    Booking::factory()->create(['status' => Booking::STATUS_COMPLETED]);
    Invoice::factory()->create([
        'booking_id' => Booking::factory()->create(['status' => Booking::STATUS_COMPLETED])->id,
        'status' => Invoice::STATUS_PAID,
    ]);
    adminAttentionProfile(adminAttentionCustomer(), CustomerProfile::STATUS_VERIFIED);
    adminAttentionReview(['is_approved' => true, 'approved_at' => now()]);

    $resolved = adminAttentionTicket('resolved');
    adminAttentionTicketMessage($resolved);

    expect(app(AdminAttentionService::class)->counts())->toBe([
        'bookings' => 0,
        'support' => 0,
        'invoices' => 0,
        'users' => 0,
        'reviews' => 0,
    ]);
});

test('sidebar attention badge displays 99 plus for counts at or above one hundred', function () {
    Booking::factory()->count(100)->create(['status' => Booking::STATUS_PENDING]);

    $this->actingAs(adminAttentionAdmin())
        ->get(route('admin.bookings.index'))
        ->assertOk()
        ->assertSee('Bookings needs attention: 100', false)
        ->assertSee('>99+</span>', false);
});

test('non admin customer views do not receive admin attention badge data', function () {
    Booking::factory()->create(['status' => Booking::STATUS_PENDING]);

    $this->actingAs(adminAttentionCustomer())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('data-admin-attention-key=', false)
        ->assertDontSee('Bookings needs attention', false);
});

test('booking status badge uses expected colors and translated labels', function (
    string $status,
    string $expectedClass,
    string $expectedLabel
) {
    $this->blade('<x-admin.booking-status-badge :status="$status" />', [
        'status' => $status,
    ])
        ->assertSee('data-booking-status="'.$status.'"', false)
        ->assertSee($expectedClass, false)
        ->assertSee($expectedLabel);
})->with([
    'pending is amber' => [Booking::STATUS_PENDING, 'bg-amber-500/15', 'Pending'],
    'confirmed is blue' => [Booking::STATUS_CONFIRMED, 'bg-blue-500/15', 'Confirmed'],
    'active is green' => [Booking::STATUS_ACTIVE, 'bg-emerald-500/15', 'Active'],
    'completed is purple' => [Booking::STATUS_COMPLETED, 'bg-purple-500/15', 'Completed'],
    'cancelled is red' => [Booking::STATUS_CANCELLED, 'bg-red-500/15', 'Cancelled'],
]);

test('booking status badge escapes unknown presentation labels', function () {
    $this->blade('<x-admin.booking-status-badge :status="$status" />', [
        'status' => '<script>alert(1)</script>',
    ])
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('sidebar renders without coupon or refund fake badges', function () {
    Booking::factory()->create(['status' => Booking::STATUS_PENDING]);

    $response = $this->actingAs(adminAttentionAdmin())
        ->get(route('admin.dashboard'))
        ->assertOk();

    $response->assertSee('Coupons', false)
        ->assertSee('Refunds', false)
        ->assertDontSee('data-admin-attention-key="coupons"', false)
        ->assertDontSee('data-admin-attention-key="refunds"', false);
});

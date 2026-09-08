<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDamage;
use App\Models\Car;
use App\Models\EmailLog;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Ticket;
use App\Models\User;

class AdminDashboardController extends Controller
{
    private const DASHBOARD_WIDGET_LIMIT = 5;

    public function index()
    {
        // 🚗 Cars
        $totalCars = Car::count();
        $availableCars = Car::where('is_available', 1)->count();
        $unavailableCars = Car::where('is_available', 0)->count();

        // 📋 Bookings
        $totalBookings = Booking::count();
        $pendingBookings = Booking::where('status', Booking::STATUS_PENDING)->count();
        $confirmedBookings = Booking::where('status', Booking::STATUS_CONFIRMED)->count();
        $activeRentals = Booking::where('status', Booking::STATUS_ACTIVE)->count();
        $completedRentals = Booking::where('status', Booking::STATUS_COMPLETED)->count();

        // 👥 Users
        $totalUsers = User::where('role', 'user')->count();

        // 💰 Revenue
        $totalRevenue = Payment::where('status', Payment::STATUS_COMPLETED)
            ->where('type', Payment::TYPE_PAYMENT)
            ->sum('amount');

        $monthlyRevenue = Payment::where('status', Payment::STATUS_COMPLETED)
            ->where('type', Payment::TYPE_PAYMENT)
            ->whereHas('invoice.booking', function ($q) {
                $q->whereMonth('end_date', now()->month)
                    ->whereYear('end_date', now()->year);
            })
            ->sum('amount');

        $weeklyRevenue = Payment::where('status', Payment::STATUS_COMPLETED)
            ->where('type', Payment::TYPE_PAYMENT)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('amount');

        $todayRevenue = Payment::where('status', Payment::STATUS_COMPLETED)
            ->where('type', Payment::TYPE_PAYMENT)
            ->whereDate('created_at', today())
            ->sum('amount');

        // 📧 Email Statistics
        $totalEmails = EmailLog::count();
        $failedEmails = EmailLog::where('status', 'failed')->count();
        $todayEmails = EmailLog::whereDate('created_at', today())->count();

        $totalDamages = BookingDamage::count();
        $unresolvedDamages = 0;
        $damagesThisMonth = 0;

        $totalReviews = Review::count();
        $pendingReviews = 0;
        $averageRating = Review::avg('rating');

        // 📅 Today's Activity
        $todayBookings = Booking::whereDate('created_at', today())->count();
        $todayCheckIns = Booking::whereDate('start_date', today())->count();
        $todayCheckOuts = Booking::whereDate('end_date', today())->count();

        // 📊 Performance Metrics
        $conversionRate = $totalUsers > 0
            ? round(($totalBookings / $totalUsers) * 100, 1)
            : 0;

        $averageBookingValue = $totalBookings > 0
            ? round($totalRevenue / $totalBookings, 2)
            : 0;

        $repeatCustomers = User::has('bookings', '>=', 2)->count();

        // 🚨 Dashboard Alerts
        $lateBookingsCount = Booking::where('status', Booking::STATUS_ACTIVE)
            ->where('end_date', '<', now())
            ->count();

        $pendingOldBookingsCount = Booking::where('status', Booking::STATUS_PENDING)
            ->where('created_at', '<=', now()->subHours(24))
            ->count();

        $endingTodayBookings = Booking::where('status', Booking::STATUS_ACTIVE)
            ->whereDate('end_date', now()->toDateString())
            ->count();

        $startingTodayBookings = Booking::where('status', Booking::STATUS_CONFIRMED)
            ->whereDate('start_date', now()->toDateString())
            ->count();

        $pendingConfirmations = Booking::where('status', Booking::STATUS_PENDING)->count();
        $securityDepositsWaitingRelease = Booking::where('status', Booking::STATUS_COMPLETED)
            ->whereNotNull('security_deposit_intent_id')
            ->where('security_deposit_status', '!=', 'released')
            ->count();

        // 📋 Pending Actions
        $bookingsToConfirm = Booking::where('status', Booking::STATUS_PENDING)->count();
        $rentalsToStart = Booking::where('status', Booking::STATUS_CONFIRMED)
            ->whereDate('start_date', '<=', now())
            ->count();
        $rentalsToComplete = Booking::where('status', Booking::STATUS_ACTIVE)
            ->whereDate('end_date', '<=', now())
            ->count();
        $depositsToRelease = Booking::where('status', Booking::STATUS_COMPLETED)
            ->whereNotNull('security_deposit_intent_id')
            ->where('security_deposit_status', '!=', 'released')
            ->count();
        $checkinsToPerform = Booking::where('status', Booking::STATUS_CONFIRMED)
            ->whereDate('start_date', '<=', now())
            ->count();
        $checkoutsToPerform = Booking::where('status', Booking::STATUS_ACTIVE)
            ->whereDate('end_date', '<=', now())
            ->count();

        // 📊 Charts - Revenue (Last 12 months)
        $monthlyChart = collect();
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyChart->put($month->format('M Y'), Payment::where('status', Payment::STATUS_COMPLETED)
                ->where('type', Payment::TYPE_PAYMENT)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount'));
        }

        // 📊 Charts - Booking Status
        $bookingStatusChart = Booking::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        // Ensure all statuses are represented
        $allStatuses = ['pending', 'confirmed', 'active', 'completed', 'cancelled'];
        foreach ($allStatuses as $status) {
            if (! $bookingStatusChart->has($status)) {
                $bookingStatusChart->put($status, 0);
            }
        }

        // 🏆 Top Performers
        $topRentedCars = Booking::selectRaw('car_id, COUNT(*) as total_rentals')
            ->groupBy('car_id')
            ->orderByDesc('total_rentals')
            ->with('car')
            ->take(5)
            ->get();

        $lowAvailabilityCars = Car::withCount('bookings')
            ->where('is_available', 0)
            ->orderByDesc('bookings_count')
            ->take(5)
            ->get();

        $lowAvailabilityChart = $lowAvailabilityCars->mapWithKeys(function ($car) {
            return [$car->full_name => $car->bookings_count];
        });

        $bookingLifecycle = [
            'pending' => Booking::where('status', Booking::STATUS_PENDING)->count(),
            'confirmed' => Booking::where('status', Booking::STATUS_CONFIRMED)->count(),
            'active' => Booking::where('status', Booking::STATUS_ACTIVE)->count(),
            'ending_today' => Booking::whereDate('end_date', now())->count(),
            'completed' => Booking::where('status', Booking::STATUS_COMPLETED)->count(),
        ];

        // Top customers by bookings
        $topCustomers = User::whereHas('bookings', function ($q) {
            $q->whereIn('status', [Booking::STATUS_COMPLETED, Booking::STATUS_CONFIRMED]);
        })
            ->withCount([
                'bookings' => function ($q) {
                    $q->whereIn('status', [Booking::STATUS_COMPLETED, Booking::STATUS_CONFIRMED]);
                },
            ])
            ->orderByDesc('bookings_count')
            ->limit(10)
            ->get();

        // 📋 Recent Activity
        $recentActivity = Booking::with(['car', 'user'])
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get();

        // 📅 Upcoming Pickups (Today + Tomorrow)
        $upcomingPickupsToday = Booking::where('status', Booking::STATUS_CONFIRMED)
            ->whereDate('start_date', today())
            ->with(['user', 'car'])
            ->count();
        $upcomingPickupsTomorrow = Booking::where('status', Booking::STATUS_CONFIRMED)
            ->whereDate('start_date', now()->addDay())
            ->with(['user', 'car'])
            ->count();

        // 📅 Upcoming Returns (Today + Tomorrow)
        $upcomingReturnsToday = Booking::where('status', Booking::STATUS_ACTIVE)
            ->whereDate('end_date', today())
            ->with(['user', 'car'])
            ->count();
        $upcomingReturnsTomorrow = Booking::where('status', Booking::STATUS_ACTIVE)
            ->whereDate('end_date', now()->addDay())
            ->with(['user', 'car'])
            ->count();

        // 📋 Latest Bookings (Latest 5)
        $latestBookings = Booking::with(['user', 'car'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // 🔔 Admin Notifications (based on booking events)
        $adminNotifications = collect();

        // New bookings (pending)
        $newBookings = Booking::where('status', Booking::STATUS_PENDING)
            ->where('created_at', '>=', now()->subHours(24))
            ->with(['user', 'car'])
            ->latest()
            ->limit(self::DASHBOARD_WIDGET_LIMIT)
            ->get();
        foreach ($newBookings as $booking) {
            $adminNotifications->push([
                'type' => 'new_booking',
                'priority' => 'info',
                'title' => 'New Booking Received',
                'message' => "{$booking->user->name} booked {$booking->car->full_name}",
                'time' => $booking->created_at->diffForHumans(),
                'sort_at' => $booking->created_at,
                'link' => route('admin.bookings.show', $booking->id),
            ]);
        }

        // Cancelled bookings
        $cancelledBookings = Booking::where('status', Booking::STATUS_CANCELLED)
            ->where('updated_at', '>=', now()->subHours(24))
            ->with(['user', 'car'])
            ->orderByDesc('updated_at')
            ->limit(self::DASHBOARD_WIDGET_LIMIT)
            ->get();
        foreach ($cancelledBookings as $booking) {
            $adminNotifications->push([
                'type' => 'booking_cancelled',
                'priority' => 'warning',
                'title' => 'Booking Cancelled',
                'message' => "{$booking->user->name} cancelled booking for {$booking->car->full_name}",
                'time' => $booking->updated_at->diffForHumans(),
                'sort_at' => $booking->updated_at,
                'link' => route('admin.bookings.show', $booking->id),
            ]);
        }

        // New reviews
        $newReviews = Review::where('created_at', '>=', now()->subHours(24))
            ->with(['user', 'booking.car'])
            ->latest()
            ->limit(self::DASHBOARD_WIDGET_LIMIT)
            ->get();
        foreach ($newReviews as $review) {
            $adminNotifications->push([
                'type' => 'new_review',
                'priority' => 'info',
                'title' => 'New Review Submitted',
                'message' => "{$review->user->name} left a {$review->rating}-star review",
                'time' => $review->created_at->diffForHumans(),
                'sort_at' => $review->created_at,
                'link' => route('admin.reviews.index'),
            ]);
        }

        // Overdue rentals
        $lateBookings = Booking::where('status', Booking::STATUS_ACTIVE)
            ->where('end_date', '<', now())
            ->with(['user', 'car'])
            ->orderBy('end_date')
            ->limit(self::DASHBOARD_WIDGET_LIMIT)
            ->get();
        foreach ($lateBookings as $booking) {
            $daysOverdue = $booking->end_date->diffInDays(now());
            $adminNotifications->push([
                'type' => 'overdue_rental',
                'priority' => 'critical',
                'title' => 'Rental Overdue',
                'message' => "Booking #{$booking->id} is overdue by {$daysOverdue} day(s).",
                'time' => $booking->end_date->diffForHumans(),
                'sort_at' => $booking->end_date,
                'link' => route('admin.bookings.show', $booking->id),
            ]);
        }

        // Failed emails (new lightweight query)
        $failedEmails = EmailLog::where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->latest()
            ->limit(self::DASHBOARD_WIDGET_LIMIT)
            ->get();
        foreach ($failedEmails as $email) {
            $adminNotifications->push([
                'type' => 'failed_email',
                'priority' => 'critical',
                'title' => 'Email Delivery Failed',
                'message' => "Email delivery failed for {$email->to}.",
                'time' => $email->created_at->diffForHumans(),
                'sort_at' => $email->created_at,
                'link' => route('admin.email-logs.show', $email->id),
            ]);
        }

        // New damage reports (new lightweight query)
        $newDamages = BookingDamage::where('created_at', '>=', now()->subHours(24))
            ->with('booking')
            ->latest()
            ->limit(self::DASHBOARD_WIDGET_LIMIT)
            ->get();
        foreach ($newDamages as $damage) {
            $adminNotifications->push([
                'type' => 'new_damage_report',
                'priority' => 'critical',
                'title' => 'Damage Report',
                'message' => "Damage reported for Booking #{$damage->booking_id}.",
                'time' => $damage->created_at->diffForHumans(),
                'sort_at' => $damage->created_at,
                'link' => route('admin.bookings.show', $damage->booking_id),
            ]);
        }

        // New support tickets (new lightweight query)
        $newTickets = Ticket::where('created_at', '>=', now()->subHours(24))
            ->with('user')
            ->latest()
            ->limit(self::DASHBOARD_WIDGET_LIMIT)
            ->get();
        foreach ($newTickets as $ticket) {
            $adminNotifications->push([
                'type' => 'new_support_ticket',
                'priority' => 'warning',
                'title' => 'New Support Ticket',
                'message' => "New support ticket #{$ticket->ticket_number} received.",
                'time' => $ticket->created_at->diffForHumans(),
                'sort_at' => $ticket->created_at,
                'link' => route('admin.support.show', $ticket->id),
            ]);
        }

        // Sort by time and take latest 5
        $adminNotifications = $adminNotifications->sortByDesc('sort_at')->take(self::DASHBOARD_WIDGET_LIMIT);

        return view('admin.dashboard', compact(
            // Core Stats
            'totalCars',
            'availableCars',
            'totalBookings',
            'pendingBookings',
            'totalUsers',
            'totalRevenue',
            'monthlyRevenue',

            // New Stats
            'totalReviews',
            'averageRating',

            // Today's Activity
            'todayBookings',
            'todayRevenue',
            'todayCheckIns',
            'todayCheckOuts',

            // Performance
            'conversionRate',
            'averageBookingValue',
            'repeatCustomers',

            // Alerts
            'lateBookingsCount',
            'pendingOldBookingsCount',
            'endingTodayBookings',

            // Charts
            'monthlyChart',
            'bookingStatusChart',
            'lowAvailabilityChart',
            'bookingLifecycle',

            // Lists
            'topRentedCars',
            'lowAvailabilityCars',
            'topCustomers',

            // New Dashboard Data
            'unavailableCars',
            'confirmedBookings',
            'activeRentals',
            'completedRentals',
            'weeklyRevenue',
            'startingTodayBookings',
            'pendingConfirmations',
            'securityDepositsWaitingRelease',
            'bookingsToConfirm',
            'rentalsToStart',
            'rentalsToComplete',
            'depositsToRelease',
            'checkinsToPerform',
            'checkoutsToPerform',
            'recentActivity',
            'adminNotifications',
            'upcomingPickupsToday',
            'upcomingPickupsTomorrow',
            'upcomingReturnsToday',
            'upcomingReturnsTomorrow',
            'latestBookings',

        ));
    }
}

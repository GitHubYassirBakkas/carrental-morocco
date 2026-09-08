<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use App\Models\Payment;
use App\Models\EmailLog;
use App\Models\BookingDamage;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // 🔄 Auto-cancel overdue bookings
        app(\App\Services\BookingService::class)->cancelOverdueBookings();
        
        // 🚗 Cars
        $totalCars = Car::count();
        $availableCars = Car::where('is_available', 1)->count();

        // 📋 Bookings
        $totalBookings = Booking::count();
        $pendingBookings = Booking::where('status', 'pending')->count();

        // 👥 Users
        $totalUsers = User::where('role', 'user')->count();

        // 💰 Revenue
        $totalRevenue = Payment::where('status', 'completed')
            ->where('type', 'payment')
            ->sum('amount');

        $monthlyRevenue = Payment::where('status', 'completed')
            ->where('type', 'payment')
            ->whereHas('invoice.booking', function ($q) {
                $q->whereMonth('end_date', now()->month)
                  ->whereYear('end_date', now()->year);
            })
            ->sum('amount');

        // 📧 Email Statistics
        $totalEmails = EmailLog::count();
        $failedEmails = EmailLog::where('status', 'failed')->count();
        $todayEmails = EmailLog::whereDate('created_at', today())->count();

       // 🔧 Damage Statistics (commented out - column doesn't exist yet)
        $totalDamages = \App\Models\BookingDamage::count();
        $unresolvedDamages = 0; // BookingDamage::where('is_resolved', false)->count();
        $damagesThisMonth = 0; // BookingDamage::whereMonth('reported_at', now()->month)->count();

      $totalReviews = Review::count();
      $pendingReviews = 0;
      $averageRating = Review::avg('rating');

        // 📅 Today's Activity
        $todayBookings = Booking::whereDate('created_at', today())->count();
        $todayRevenue = Payment::where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');
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

        // 🚨 Alerts
        $lateBookings = Booking::where('status', 'active')
            ->where('end_date', '<', now())
            ->get();

        $pendingOldBookings = Booking::where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(24))
            ->get();

        $endingTodayBookings = Booking::where('status', 'active')
            ->whereDate('end_date', now()->toDateString())
            ->get();

        // 📊 Charts
        $monthlyChart = Payment::where('status', 'completed')
            ->where('type', 'payment')
            ->whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $bookingStatusChart = Booking::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

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
            'pending' => Booking::where('status','pending')->count(),
            'confirmed' => Booking::where('status','confirmed')->count(),
            'active' => Booking::where('status','active')->count(),
            'ending_today' => Booking::whereDate('end_date', now())->count(),
            'completed' => Booking::where('status','completed')->count(),
        ];

        // Top customers by bookings
        $topCustomers = \App\Models\User::withCount([
            'bookings' => function($q) {
                $q->whereIn('status', ['completed', 'confirmed']);
            }
        ])
        ->having('bookings_count', '>', 0)
        ->orderByDesc('bookings_count')
        ->limit(10)
        ->get();


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
            'totalEmails',
            'failedEmails',
            'todayEmails',
            'totalDamages',
            'unresolvedDamages',
            'damagesThisMonth',
            'totalReviews',
            'pendingReviews',
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
            'lateBookings',
            'pendingOldBookings',
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

        ));
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Statistics
        $totalBookings = $user->bookings()->count();
        $activeRentals = $user->bookings()
            ->whereIn('status', ['confirmed', 'active'])
            ->count();
        $wishlistCars = $user->wishlists()->count();
        $completedRentals = $user->bookings()
            ->where('status', 'completed')
            ->count();

        // Total amount spent (using shared method from User model)
        $totalSpent = $user->total_spent;

        // Recent bookings with eager loading
        $recentBookings = $user->bookings()
            ->with(['car', 'review'])
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'user',
            'totalBookings',
            'activeRentals',
            'wishlistCars',
            'totalSpent',
            'completedRentals',
            'recentBookings'
        ));
    }
}

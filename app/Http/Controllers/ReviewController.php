<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Show review form
     */
    public function create(Booking $booking)
    {
        // Security checks
        abort_if($booking->user_id !== auth()->id(), 403);
        abort_if(!$booking->isCompleted(), 403, 'You can only review completed bookings.');
        abort_if($booking->review, 403, 'You have already reviewed this booking.');

        return view('reviews.create', compact('booking'));
    }

    /**
     * Store review
     */
    public function store(Request $request, Booking $booking)
    {
        // Security checks
        abort_if($booking->user_id !== auth()->id(), 403);
        abort_if(!$booking->isCompleted(), 403);
        abort_if($booking->review, 403);

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:10|max:1000',
        ]);

        Review::create([
            'user_id' => auth()->id(),
            'car_id' => $booking->car_id,
            'booking_id' => $booking->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'],
            'is_approved' => false, // Pending admin approval
        ]);

        return redirect()
            ->route('my_booking.index')
            ->with('success', 'Thank you! Your review has been submitted and is pending approval.');
           
    }
}

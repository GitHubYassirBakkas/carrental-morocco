<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Booking;
use App\Models\Insurance;
use App\Models\Payment;
use App\Models\Location;
use App\Models\Invoice;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * STEP 1️⃣ - Save booking data to SESSION (Preview)
     */
    public function preview(Request $request, Car $car)
    {
        $data = $request->validate([
            'pickup_location_id' => 'required|exists:locations,id',
            'dropoff_location_id' => 'required|exists:locations,id',
            'pickup_date' => 'required|date|after_or_equal:today',
            'pickup_time' => 'required',
            'return_date' => 'required|date|after:pickup_date',
            'return_time' => 'required',
        ]);

        $start = Carbon::parse($data['pickup_date'] . ' ' . $data['pickup_time']);
        $end = Carbon::parse($data['return_date'] . ' ' . $data['return_time']);

        $days = max(1, $start->diffInDays($end));

        // 🔥 PRO Overlap Check
        if (!$car->isAvailableForDates($start, $end)) {
            return back()->withErrors([
                'dates' => '❌ This car is already booked for these dates. Please choose different dates.'
            ])->withInput();
        }

        $insurance = session()->has('insurance_id')
            ? Insurance::find(session('insurance_id'))
            : null;

        $carTotal = $car->price_per_day * $days;
        $insuranceTotal = $insurance ? $insurance->daily_rate : 0;

        // 📍 Calculate dropoff fee if different location
        $dropoffFee = 0;
        if ($data['pickup_location_id'] != $data['dropoff_location_id']) {
            $dropoffFee = 200; // 200 MAD for different dropoff location
        }

        $grandTotal = $carTotal + $insuranceTotal + $dropoffFee;

        session()->put('booking_preview', [
            'car_id' => $car->id,
            'pickup_location_id' => $data['pickup_location_id'],
            'dropoff_location_id' => $data['dropoff_location_id'],
            'pickup_date' => $data['pickup_date'],
            'return_date' => $data['return_date'],
            'pickup_time' => $data['pickup_time'],
            'return_time' => $data['return_time'],
            'start_date' => $start->toDateTimeString(),
            'end_date' => $end->toDateTimeString(),
            'days' => $days,
            'car_price' => $car->price_per_day,
            'insurance_id' => $insurance?->id,
            'insurance_price' => $insurance?->daily_rate ?? 0,
            'car_total' => $carTotal,
            'insurance_total' => $insuranceTotal,
            'dropoff_fee' => $dropoffFee,
            'grand_total' => $grandTotal,
        ]);

        if (!auth()->user()->hasVerifiedEmail()) {
            session(['url.intended' => route('bookings.preview.show')]);
            return redirect()->route('verification.notice');
        }

        return redirect()->route('bookings.preview.show');
    }

    /**
     * STEP 2️⃣ - Show booking preview page
     */
    public function showPreview()
    {
        abort_if(!session()->has('booking_preview'), 404);

        $preview = session('booking_preview');

        // ✅ Verify required keys exist
        foreach (['grand_total', 'days', 'car_price', 'pickup_location_id', 'dropoff_location_id'] as $key) {
            abort_if(!isset($preview[$key]), 404);
        }

        $car = Car::findOrFail($preview['car_id']);
        $insurance = $preview['insurance_id']
            ? Insurance::find($preview['insurance_id'])
            : null;

        // 📍 Load locations
        $pickupLocation = Location::findOrFail($preview['pickup_location_id']);
        $dropoffLocation = Location::findOrFail($preview['dropoff_location_id']);

        return view('bookings.preview', compact(
            'preview',
            'car',
            'insurance',
            'pickupLocation',
            'dropoffLocation'
        ));
    }

    /**
     * STEP 3️⃣ - Confirm & store booking in DB
     */
   public function store(Request $request)
{
    if (!session()->has('booking_preview')) {
        return redirect()->route('cars.index')->with('error', 'No booking found.');
    }

    $preview = session('booking_preview');
    $car = Car::findOrFail($preview['car_id']);

    // ✅ Double availability check
    if (!$car->isAvailableForDates($preview['start_date'], $preview['end_date'])) {
        return redirect()
            ->route('cars.details', $car)
            ->withErrors(['dates' => 'Car is no longer available.']);
    }

    // Get final total (with coupon if applied)
    $finalTotal = session('final_total', $preview['grand_total']);
    $couponDiscount = session('coupon_discount', 0);
    $appliedCoupon = session('applied_coupon');

    // ✅ CREATE BOOKING WITH ALL REQUIRED FIELDS
    $booking = Booking::create([
        'user_id' => auth()->id(),
        'car_id' => $car->id,
        'insurance_id' => $preview['insurance_id'],
        'pickup_location_id' => $preview['pickup_location_id'],
        'dropoff_location_id' => $preview['dropoff_location_id'],
        'start_date' => $preview['start_date'],
        'end_date' => $preview['end_date'],
        'total_days' => $preview['days'],
        'daily_rate' => $preview['car_price'],
        'price_per_day' => $preview['car_price'],
        'insurance_daily_rate' => $preview['insurance_price'] ?? 0,
        'total_amount' => $finalTotal,
        'status' => 'pending',
        'payment_method' => null,
        'deposit_paid' => false,
        'deposit_due_at' => now()->addHours(24),
    ]);

    // ✅ CREATE INVOICE WITH DISCOUNT FIELDS
    $invoice = Invoice::create([
        'booking_id' => $booking->id,
        'user_id' => auth()->id(),
        'subtotal' => $preview['grand_total'], // Before discount
        'discount_amount' => $couponDiscount, // Discount amount
        'total_amount' => $finalTotal, // After discount
        'status' => 'pending',
    ]);

    // ✅ APPLY COUPON IF USED
    if ($appliedCoupon && $couponDiscount > 0) {
        $coupon = Coupon::find($appliedCoupon['id']);

        if ($coupon) {
            // Record usage
            $coupon->apply(
                auth()->id(),
                $booking->id,
                $preview['grand_total'] // Original amount
            );
        }
    }

    // Clear session
    session()->forget([
        'booking_preview',
        'insurance_id',
        'insurance_name',
        'insurance_price',
        'applied_coupon',
        'coupon_discount',
        'final_total'
    ]);

    return redirect()
        ->route('payments.show', $booking)
        ->with('success', 'Booking created. Please complete the payment.');
}
    /**
     * STEP 4️⃣ - Booking success page
     */
    public function success(Booking $booking)
    {
        // Security: Only owner can view
        abort_if($booking->user_id !== auth()->id(), 403);

        return view('bookings.success', compact('booking'));
    }

    /**
     * My Bookings Page
     */
    public function myBookings()
    {
        $user = auth()->user();

        $upcoming = $user->bookings()
            ->where('start_date', '>', now())
            ->with(['car', 'review']) // ✅ Load car and review relationships
            ->orderBy('start_date')
            ->get();

        $past = $user->bookings()
            ->where('end_date', '<', now())
            ->with(['car', 'review']) // ✅ Load car and review relationships
            ->orderByDesc('end_date')
            ->get();

        // ✅ Total spent (only completed/confirmed bookings)
        $totalSpent = $user->bookings()
            ->whereIn('status', ['completed', 'confirmed'])
            ->sum('total_amount');

        return view('my_booking.index', compact(
            'upcoming',
            'past',
            'totalSpent'
        ));
    }

    /**
     * Check car availability (AJAX)
     */
    public function checkAvailability(Request $request, Car $car)
    {
        $request->validate([
            'pickup_date' => 'required|date',
            'return_date' => 'required|date|after:pickup_date',
        ]);

        $pickup = Carbon::parse($request->pickup_date);
        $return = Carbon::parse($request->return_date);

        $isAvailable = $car->isAvailableForDates($pickup, $return);

        return response()->json([
            'available' => $isAvailable,
            'message' => $isAvailable 
                ? 'Car is available for selected dates!' 
                : 'Car is already booked for these dates.'
        ]);
    }
}
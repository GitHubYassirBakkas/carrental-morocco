<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Booking;
use App\Models\Insurance;
use App\Models\Location;
use App\Models\Coupon;
use App\Services\InvoiceService;
use App\Services\Pricing\BookingPricingService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        private BookingPricingService $pricingService,
        private readonly InvoiceService $invoices
    )
    {
    }

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

        // 📍 Calculate dropoff fee if different location
        $dropoffFee = $this->pricingService->calculateDropoffFee(
            $data['pickup_location_id'],
            $data['dropoff_location_id']
        );

        $pricingBreakdown = $this->pricingService->breakdownForPreview(
            (float) $car->price_per_day,
            $days,
            $insurance?->fixed_price,
            ['dropoff_fee' => $dropoffFee]
        );

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
            'insurance_price' => $insurance?->fixed_price ?? 0,
            'car_total' => $pricingBreakdown['rental_amount'],
            'insurance_total' => $pricingBreakdown['insurance_amount'],
            'dropoff_fee' => $dropoffFee,
            'rental_amount' => $pricingBreakdown['rental_amount'],
            'insurance_amount' => $pricingBreakdown['insurance_amount'],
            'protection_plan_amount' => $pricingBreakdown['protection_plan_amount'],
            'extras_amount' => $pricingBreakdown['extras_amount'],
            'pricing_breakdown' => $pricingBreakdown,
            'grand_total' => $pricingBreakdown['total_amount'],
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

    $couponDiscount = session('coupon_discount', 0);
    $appliedCoupon = session('applied_coupon');
    $pricingBreakdown = $this->pricingService->breakdown(
        (float) ($preview['rental_amount'] ?? $preview['car_total'] ?? 0),
        (float) ($preview['insurance_amount'] ?? $preview['insurance_total'] ?? 0),
        (float) ($preview['extras_amount'] ?? $preview['dropoff_fee'] ?? 0),
        (float) $couponDiscount
    );

    // Get final total (with coupon if applied). Keep session value for full compatibility.
    $finalTotal = session('final_total', $pricingBreakdown['total_amount']);

    try {
        $booking = DB::transaction(function () use ($car, $preview, $pricingBreakdown, $finalTotal, $couponDiscount, $appliedCoupon) {
            $car = Car::whereKey($car->id)->lockForUpdate()->firstOrFail();

            if (!$car->isAvailableForDates($preview['start_date'], $preview['end_date'])) {
                throw ValidationException::withMessages([
                    'dates' => 'Car is no longer available.',
                ]);
            }

    // ✅ CREATE BOOKING WITH ALL REQUIRED FIELDS
    $booking = Booking::create([
        'user_id' => auth()->id(),
        'car_id' => $car->id,
        'insurance_id' => $preview['insurance_id'],
        'pickup_location_id' => $preview['pickup_location_id'],
        'dropoff_location_id' => $preview['dropoff_location_id'],
        'start_date' => $preview['start_date'],
        'end_date' => $preview['end_date'],
        'rental_price_per_day' => $preview['car_price'],
        'insurance_fixed_price' => $preview['insurance_price'] ?? 0,
        'total_amount' => $finalTotal,
        'status' => Booking::STATUS_PENDING,
        'payment_method' => null,
        'advance_payment_amount' => $this->pricingService->calculateAdvancePayment((float) $finalTotal, true),
        'advance_payment_status' => Booking::ADVANCE_PAYMENT_STATUS_PENDING,
        'advance_payment_due_at' => now()->addHours(setting(
            'advance_payment_deadline_hours',
            config('rental.advance_payment_deadline_hours', 24)
        )),
        'security_deposit_amount' => $car->security_deposit_amount ?? 0,
        'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_PENDING,
        'discount_amount' => $couponDiscount,
    ]);

    // ✅ CREATE INVOICE WITH DISCOUNT FIELDS
    $this->invoices->createForCustomerBooking(
        $booking,
        auth()->id(),
        $pricingBreakdown['subtotal_amount'], // Before discount
        $couponDiscount, // Discount amount
        $finalTotal // After discount
    );

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

            return $booking;
        });
    } catch (ValidationException $e) {
        return redirect()
            ->route('cars.details', $car)
            ->withErrors($e->errors());
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

    public function show(Booking $booking)
    {
        abort_if($booking->user_id !== auth()->id(), 403);

        $booking->load([
            'car',
            'insurance',
            'pickupLocation',
            'dropoffLocation',
            'invoice.payments',
        ]);

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
            ->whereIn('status', [Booking::STATUS_COMPLETED, Booking::STATUS_CONFIRMED])
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

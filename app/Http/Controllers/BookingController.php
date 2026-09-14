<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Coupon;
use App\Models\Insurance;
use App\Models\Location;
use App\Models\User;
use App\Services\BookingService;
use App\Services\BookingTimelineService;
use App\Services\InvoiceService;
use App\Services\NotificationService;
use App\Services\Pricing\BookingPricingService;
use App\Services\RefundPolicyService;
use App\Services\RefundService;
use App\Services\RentalBusinessRules;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        private BookingPricingService $pricingService,
        private readonly InvoiceService $invoices,
        private NotificationService $notificationService,
        private readonly BookingService $bookingService,
        private readonly RefundService $refundService,
        private readonly RefundPolicyService $refundPolicyService,
        private readonly BookingTimelineService $bookingTimelineService,
        private readonly RentalBusinessRules $rentalRules
    ) {}

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

        $start = Carbon::parse($data['pickup_date'].' '.$data['pickup_time']);
        $end = Carbon::parse($data['return_date'].' '.$data['return_time']);

        $days = max(1, $start->diffInDays($end));

        // Enforce booking settings
        $minDays = $this->rentalRules->bookingMinDays();
        $maxDays = $this->rentalRules->bookingMaxDays();
        $maxAdvanceDays = $this->rentalRules->maxAdvanceBookingDays();

        if ($days < $minDays) {
            return back()->withErrors([
                'dates' => __('messages.minimum_rental_duration_days', ['days' => $minDays]),
            ])->withInput();
        }

        if ($days > $maxDays) {
            return back()->withErrors([
                'dates' => __('messages.maximum_rental_duration_days', ['days' => $maxDays]),
            ])->withInput();
        }

        if ($this->rentalRules->exceedsMaxAdvanceDate($start)) {
            return back()->withErrors([
                'dates' => __('messages.max_advance_booking_days_error', ['days' => $maxAdvanceDays]),
            ])->withInput();
        }

        if (! $this->rentalRules->driverMeetsMinimumAge($request->user(), $car)) {
            return back()->withErrors([
                'driver_age' => 'Driver must be at least '.$this->rentalRules->effectiveMinimumDriverAge($car).' years old for this vehicle.',
            ])->withInput();
        }

        // 🔥 PRO Overlap Check
        if (! $car->isAvailableForDates($start, $end)) {
            return back()->withErrors([
                'dates' => __('messages.car_already_booked_dates'),
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

        if (! auth()->user()->hasVerifiedEmail()) {
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
        abort_if(! session()->has('booking_preview'), 404);

        $preview = session('booking_preview');

        // ✅ Verify required keys exist
        foreach (['grand_total', 'days', 'car_price', 'pickup_location_id', 'dropoff_location_id'] as $key) {
            abort_if(! isset($preview[$key]), 404);
        }

        $car = Car::findOrFail($preview['car_id']);

        if (! $this->rentalRules->driverMeetsMinimumAge(request()->user(), $car)) {
            session()->forget('booking_preview');

            return redirect()
                ->route('cars.details', $car)
                ->withErrors([
                    'driver_age' => 'Driver must be at least '.$this->rentalRules->effectiveMinimumDriverAge($car).' years old for this vehicle.',
                ]);
        }
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

    public function driverVerificationRequired(Request $request)
    {
        $user = $request->user()->loadMissing('customerProfile');
        $profile = $user->customerProfile;
        $status = $profile?->driver_verification_status ?? 'missing';

        if ($profile?->isDriverVerified()) {
            return redirect()->route('cars.index');
        }

        return view('bookings.driver-verification-required', compact('profile', 'status'));
    }

    /**
     * STEP 3️⃣ - Confirm & store booking in DB
     */
    public function store(Request $request)
    {
        if (! session()->has('booking_preview')) {
            return redirect()->route('cars.index')->with('error', __('messages.no_booking_found'));
        }

        $preview = session('booking_preview');
        $car = Car::findOrFail($preview['car_id']);

        // ✅ Double availability check
        if (! $car->isAvailableForDates($preview['start_date'], $preview['end_date'])) {
            return redirect()
                ->route('cars.details', $car)
                ->withErrors(['dates' => __('messages.car_no_longer_available')]);
        }

        // Re-enforce booking settings (defense in depth)
        $start = Carbon::parse($preview['start_date']);
        $end = Carbon::parse($preview['end_date']);
        $days = max(1, $start->diffInDays($end));

        $minDays = $this->rentalRules->bookingMinDays();
        $maxDays = $this->rentalRules->bookingMaxDays();
        $maxAdvanceDays = $this->rentalRules->maxAdvanceBookingDays();

        if ($days < $minDays) {
            return redirect()
                ->route('cars.details', $car)
                ->withErrors(['dates' => __('messages.minimum_rental_duration_days', ['days' => $minDays])]);
        }

        if ($days > $maxDays) {
            return redirect()
                ->route('cars.details', $car)
                ->withErrors(['dates' => __('messages.maximum_rental_duration_days', ['days' => $maxDays])]);
        }

        if ($this->rentalRules->exceedsMaxAdvanceDate($start)) {
            return redirect()
                ->route('cars.details', $car)
                ->withErrors(['dates' => __('messages.max_advance_booking_days_error', ['days' => $maxAdvanceDays])]);
        }

        if (! $this->rentalRules->driverMeetsMinimumAge($request->user(), $car)) {
            return redirect()
                ->route('cars.details', $car)
                ->withErrors([
                    'driver_age' => 'Driver must be at least '.$this->rentalRules->effectiveMinimumDriverAge($car).' years old for this vehicle.',
                ]);
        }

        $appliedCoupon = session('applied_coupon');
        $pricingBreakdown = $this->pricingService->breakdown(
            (float) ($preview['rental_amount'] ?? $preview['car_total'] ?? 0),
            (float) ($preview['insurance_amount'] ?? $preview['insurance_total'] ?? 0),
            (float) ($preview['extras_amount'] ?? $preview['dropoff_fee'] ?? 0),
            0
        );
        $baseAmount = (float) $pricingBreakdown['subtotal_amount'];
        $userId = auth()->id();

        try {
            $booking = DB::transaction(function () use ($car, $preview, $pricingBreakdown, $baseAmount, $appliedCoupon, $userId) {
                $car = Car::whereKey($car->id)->lockForUpdate()->firstOrFail();

                if (! $car->isAvailableForDates($preview['start_date'], $preview['end_date'])) {
                    throw ValidationException::withMessages([
                        'dates' => __('messages.car_no_longer_available'),
                    ]);
                }

                // ✅ CREATE BOOKING WITH ALL REQUIRED FIELDS
                $coupon = null;
                $couponDiscount = 0.0;
                $finalPricingBreakdown = $pricingBreakdown;
                $finalTotal = (float) $pricingBreakdown['total_amount'];

                if ($appliedCoupon) {
                    $couponId = $appliedCoupon['id'] ?? null;
                    $couponCode = isset($appliedCoupon['code']) ? strtoupper((string) $appliedCoupon['code']) : null;

                    $coupon = $couponId
                        ? Coupon::whereKey($couponId)->lockForUpdate()->first()
                        : null;

                    if (! $coupon || ! $couponCode || strtoupper((string) $coupon->code) !== $couponCode) {
                        throw ValidationException::withMessages([
                            'coupon_code' => 'This coupon is no longer available. Please apply it again.',
                        ]);
                    }

                    $validation = $coupon->canBeUsed($userId, $baseAmount, $car->type);

                    if (! $validation['valid']) {
                        throw ValidationException::withMessages([
                            'coupon_code' => $validation['message'],
                        ]);
                    }

                    $couponDiscount = $coupon->calculateDiscount($baseAmount);
                    $finalPricingBreakdown = $this->pricingService->breakdown(
                        (float) ($preview['rental_amount'] ?? $preview['car_total'] ?? 0),
                        (float) ($preview['insurance_amount'] ?? $preview['insurance_total'] ?? 0),
                        (float) ($preview['extras_amount'] ?? $preview['dropoff_fee'] ?? 0),
                        (float) $couponDiscount
                    );
                    $finalTotal = (float) $finalPricingBreakdown['total_amount'];
                }

                $booking = Booking::create([
                    'user_id' => $userId,
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
                    'security_deposit_amount' => $car->security_deposit_amount ?? 0,
                    'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_PENDING,
                    'discount_amount' => $couponDiscount,
                ]);

                // ✅ CREATE INVOICE WITH DISCOUNT FIELDS
                $this->invoices->createForCustomerBooking(
                    $booking,
                    $userId,
                    $finalPricingBreakdown['subtotal_amount'], // Before discount
                    $couponDiscount, // Discount amount
                    $finalTotal, // After discount and tax
                    $finalPricingBreakdown['tax_amount']
                );

                // ✅ APPLY COUPON IF USED
                if ($coupon && $couponDiscount > 0) {
                    $couponResult = $coupon->apply(
                        $userId,
                        $booking->id,
                        $baseAmount,
                        $car->type
                    );

                    if (! $couponResult['valid']) {
                        throw ValidationException::withMessages([
                            'coupon_code' => $couponResult['message'],
                        ]);
                    }
                }

                // ✅ CREATE NOTIFICATION
                $this->notificationService->create(
                    $userId,
                    'booking_confirmed',
                    __('messages.notification_booking_confirmed'),
                    __('messages.notification_booking_confirmed_message', ['reference' => "#{$booking->id}"]),
                    [
                        'booking_id' => $booking->id,
                        'booking_reference' => "#{$booking->id}",
                        'car_id' => $car->id,
                        'car_name' => $car->name,
                        'pickup_date' => $preview['start_date'],
                        'return_date' => $preview['end_date'],
                    ]
                );

                return $booking;
            });
        } catch (ValidationException $e) {
            if (array_key_exists('coupon_code', $e->errors())) {
                session()->forget([
                    'applied_coupon',
                    'coupon_discount',
                    'final_total',
                ]);

                return redirect()
                    ->route('bookings.preview.show')
                    ->withErrors($e->errors());
            }

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
            'final_total',
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

        $booking->load([
            'car',
            'insurance',
            'pickupLocation',
            'dropoffLocation',
            'invoice.payments',
        ]);

        // Prepare booking with refund status
        $bookingData = $this->refundService->prepareBookingsForCustomer(collect([$booking]))->first();

        // Build timeline
        $timeline = $this->bookingTimelineService->buildTimeline($booking);

        return view('bookings.success', ['booking' => $bookingData, 'timeline' => $timeline]);
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

        // Prepare booking with refund status
        $bookingData = $this->refundService->prepareBookingsForCustomer(collect([$booking]))->first();

        // Build timeline
        $timeline = $this->bookingTimelineService->buildTimeline($booking);

        return view('bookings.success', ['booking' => $bookingData, 'timeline' => $timeline]);
    }

    /**
     * My Bookings Page
     */
    public function myBookings()
    {
        $user = auth()->user();

        $upcoming = $user->bookings()
            ->where('start_date', '>', now())
            ->activeOrReserved()
            ->with(['car', 'review', 'pickupLocation', 'dropoffLocation', 'invoice.payments'])
            ->orderBy('start_date')
            ->get();

        $past = $user->bookings()
            ->where(function ($query) {
                $query->where('end_date', '<', now())
                    ->orWhere('status', Booking::STATUS_CANCELLED);
            })
            ->with(['car', 'review', 'pickupLocation', 'dropoffLocation', 'invoice.payments'])
            ->orderByDesc('end_date')
            ->get();

        // Prepare bookings with refund status
        $upcoming = $this->refundService->prepareBookingsForCustomer($upcoming);
        $past = $this->refundService->prepareBookingsForCustomer($past);

        // ✅ Total spent (using shared method from User model)
        $totalSpent = $user->total_spent;

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
                ? __('messages.car_available_selected_dates')
                : __('messages.car_already_booked_selected_dates'),
        ]);
    }

    /**
     * Cancel booking (customer)
     */
    public function cancel(Booking $booking)
    {
        // Security: Only owner can cancel
        abort_if($booking->user_id !== auth()->id(), 403);

        // Check cancellation eligibility using RefundPolicyService
        if (! $this->refundPolicyService->canCustomerCancel($booking)) {
            return back()->withErrors([
                'cancellation' => __('messages.booking_no_longer_cancellable'),
            ]);
        }

        try {
            $this->bookingService->cancelBooking($booking, 'Cancelled by customer');

            // Create admin notification
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $this->notificationService->create(
                    $admin->id,
                    'customer_cancelled_booking',
                    __('messages.notification_customer_cancelled_booking'),
                    __('messages.notification_customer_cancelled_booking_message', [
                        'reference' => "#{$booking->id}",
                        'customer' => $booking->user->name,
                    ]),
                    [
                        'booking_id' => $booking->id,
                        'booking_reference' => "#{$booking->id}",
                        'customer_id' => $booking->user_id,
                        'customer_name' => $booking->user->name,
                        'cancelled_at' => now(),
                    ]
                );
            }

            return redirect()->route('my_booking.index')
                ->with('success', __('messages.booking_cancelled_successfully'));
        } catch (\Exception $e) {
            Log::error('Customer booking cancellation failed.', [
                'exception' => $e,
                'booking_id' => $booking->id,
                'user_id' => auth()->id(),
            ]);

            return back()->withErrors([
                'cancellation' => __('messages.booking_cancel_failed_try_support'),
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Coupon;
use App\Services\Pricing\BookingPricingService;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(private readonly BookingPricingService $pricingService) {}

    /**
     * Apply coupon to current booking
     */
    public function apply(Request $request)
    {
        // Check if removing coupon
        if ($request->action === 'remove') {
            session()->forget(['applied_coupon', 'coupon_discount', 'final_total', 'final_pricing_breakdown']);

            return back()->with('success', 'Coupon removed successfully.');
        }

        // Validate
        $request->validate([
            'coupon_code' => 'required|string|max:50',
        ]);

        $code = strtoupper($request->coupon_code);
        $preview = session('booking_preview');

        if (! $preview) {
            return back()->withErrors(['coupon_code' => __('messages.no_booking_found_start_new')]);
        }

        $car = Car::find($preview['car_id'] ?? null);
        if (! $car) {
            return back()->withErrors(['coupon_code' => 'Selected vehicle is no longer available. Please start a new booking.']);
        }

        // Find coupon
        $coupon = Coupon::where('code', $code)
            ->active()
            ->first();

        if (! $coupon) {
            return back()->withErrors(['coupon_code' => 'Invalid coupon code.']);
        }

        $baseAmount = (float) ($preview['pricing_breakdown']['subtotal_amount'] ?? $preview['grand_total']);

        // Validate coupon
        $validation = $coupon->canBeUsed(
            auth()->id(),
            $baseAmount,
            $car->type
        );

        if (! $validation['valid']) {
            return back()->withErrors(['coupon_code' => $validation['message']]);
        }

        // Calculate discount
        $discountAmount = $coupon->calculateDiscount($baseAmount);
        $finalPricingBreakdown = $this->pricingService->breakdown(
            (float) ($preview['rental_amount'] ?? $preview['car_total'] ?? 0),
            (float) ($preview['insurance_amount'] ?? $preview['insurance_total'] ?? 0),
            (float) ($preview['extras_amount'] ?? $preview['dropoff_fee'] ?? 0),
            $discountAmount
        );

        // Store in session
        session([
            'applied_coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
            ],
            'coupon_discount' => $discountAmount,
            'final_total' => $finalPricingBreakdown['total_amount'],
            'final_pricing_breakdown' => $finalPricingBreakdown,
        ]);

        return back()->with('success', "Coupon '{$code}' applied! You saved ".number_format($discountAmount, 0).' MAD!');
    }
}

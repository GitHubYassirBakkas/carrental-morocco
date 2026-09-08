<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Apply coupon to current booking
     */
    public function apply(Request $request)
    {
        // Check if removing coupon
        if ($request->action === 'remove') {
            session()->forget(['applied_coupon', 'coupon_discount', 'final_total']);

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

        // Validate coupon
        $validation = $coupon->canBeUsed(
            auth()->id(),
            $preview['grand_total'],
            $car->type
        );

        if (! $validation['valid']) {
            return back()->withErrors(['coupon_code' => $validation['message']]);
        }

        // Calculate discount
        $discountAmount = $coupon->calculateDiscount($preview['grand_total']);
        $finalTotal = $preview['grand_total'] - $discountAmount;

        // Store in session
        session([
            'applied_coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
            ],
            'coupon_discount' => $discountAmount,
            'final_total' => $finalTotal,
        ]);

        return back()->with('success', "Coupon '{$code}' applied! You saved ".number_format($discountAmount, 0).' MAD!');
    }
}

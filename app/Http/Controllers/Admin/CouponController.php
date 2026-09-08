<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Display all coupons
     */
    public function index(Request $request)
    {
        $query = Coupon::query()->with('user')->latest();

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'expired') {
                $query->where('valid_until', '<', now());
            } elseif ($request->status === 'valid') {
                $query->valid();
            }
        }

        // Search
        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        $coupons = $query->paginate(15);

        // Stats
        $stats = [
            'total' => Coupon::count(),
            'active' => Coupon::where('is_active', true)->count(),
            'expired' => Coupon::where('valid_until', '<', now())->count(),
            'total_used' => Coupon::sum('used_count'),
            'total_discount_given' => CouponUsage::sum('discount_amount'),
        ];

        return view('admin.coupons.index', compact('coupons', 'stats'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $categories = [
            'welcome' => 'Welcome Offer',
            'loyalty' => 'Loyalty Reward',
            'seasonal' => 'Seasonal Promotion',
            'referral' => 'Referral Program',
            'retention' => 'Win-back Offer',
            'corporate' => 'Corporate Account',
            'apology' => 'Service Recovery',
        ];

        $carTypes = ['sedan', 'suv', 'luxury', 'van', 'sports'];

        return view('admin.coupons.create', compact('categories', 'carTypes'));
    }

    /**
     * Store new coupon
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:coupons,code|uppercase',
            'category' => 'required|in:welcome,loyalty,seasonal,referral,retention,corporate,apology',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_booking_amount' => 'nullable|numeric|min:0',
            'min_bookings' => 'nullable|integer|min:1',
            'min_total_spent' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'required|integer|min:1',
            'allowed_car_types' => 'nullable|array',
            'allowed_car_types.*' => 'string',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        // Additional validation for percentage
        if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'Percentage cannot exceed 100%'])->withInput();
        }

        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->has('is_active');

        Coupon::create($data);

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon created successfully!');
    }

    /**
     * Show coupon details
     */
    public function show(Coupon $coupon)
    {
        $coupon->load(['usages.user', 'usages.booking']);
        
        $stats = [
            'total_uses' => $coupon->used_count,
            'unique_users' => $coupon->usages()->distinct('user_id')->count(),
            'total_discount' => $coupon->usages()->sum('discount_amount'),
            'avg_discount' => $coupon->usages()->avg('discount_amount'),
        ];

        return view('admin.coupons.show', compact('coupon', 'stats'));
    }

    /**
     * Show edit form
     */
    public function edit(Coupon $coupon)
    {
        $categories = [
            'welcome' => 'Welcome Offer',
            'loyalty' => 'Loyalty Reward',
            'seasonal' => 'Seasonal Promotion',
            'referral' => 'Referral Program',
            'retention' => 'Win-back Offer',
            'corporate' => 'Corporate Account',
            'apology' => 'Service Recovery',
        ];

        $carTypes = ['sedan', 'suv', 'luxury', 'van', 'sports'];

        return view('admin.coupons.edit', compact('coupon', 'categories', 'carTypes'));
    }

    /**
     * Update coupon
     */
    public function update(Request $request, Coupon $coupon)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|uppercase|unique:coupons,code,' . $coupon->id,
            'category' => 'required|in:welcome,loyalty,seasonal,referral,retention,corporate,apology',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'min_booking_amount' => 'nullable|numeric|min:0',
            'min_bookings' => 'nullable|integer|min:1',
            'min_total_spent' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'required|integer|min:1',
            'allowed_car_types' => 'nullable|array',
            'allowed_car_types.*' => 'string',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        // Additional validation for percentage
        if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'Percentage cannot exceed 100%'])->withInput();
        }

        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->has('is_active');

        $coupon->update($data);

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon updated successfully!');
    }

    /**
     * Delete coupon
     */
    public function destroy(Coupon $coupon)
    {
        // Check if coupon has been used
        if ($coupon->used_count > 0) {
            return back()->withErrors(['error' => 'Cannot delete coupon that has been used. Deactivate it instead.']);
        }

        $coupon->delete();

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon deleted successfully!');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(Coupon $coupon)
    {
        $coupon->update([
            'is_active' => !$coupon->is_active
        ]);

        $status = $coupon->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Coupon {$status} successfully!");
    }

    /**
     * Generate unique coupon code
     */
    public function generateCode(Request $request)
    {
        $prefix = $request->input('prefix', 'PROMO');
        $length = $request->input('length', 8);
        
        do {
            $code = strtoupper($prefix . str_pad(rand(0, 99999999), $length - strlen($prefix), '0', STR_PAD_LEFT));
        } while (Coupon::where('code', $code)->exists());

        return response()->json(['code' => $code]);
    }
}
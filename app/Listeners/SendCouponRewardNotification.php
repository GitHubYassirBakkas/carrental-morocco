<?php

namespace App\Listeners;

use App\Events\BookingCompleted;
use App\Models\Coupon;
use App\Mail\CouponRewardMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendCouponRewardNotification
{
    public function handle(BookingCompleted $event): void
    {
        Log::info('🎯 EVENT FIRED: BookingCompleted');
        
        $booking = $event->booking;
        $user = $booking->user;

        Log::info('👤 User ID: ' . $user->id . ' | Name: ' . $user->name);

        // Count completed bookings
        $completedBookings = $user->bookings()
            ->whereIn('status', ['completed', 'confirmed'])
            ->count();

        Log::info('📊 Total completed bookings: ' . $completedBookings);

        // Check if user reached milestone (2 for testing)
        if ($completedBookings == 10) {
            Log::info('🎉 MILESTONE REACHED! Generating coupon...');
            
            $couponCode = 'USER' . $user->id . 'GOLD50';

            // Check if already exists
            if (Coupon::where('code', $couponCode)->exists()) {
                Log::warning('⚠️ Coupon already exists: ' . $couponCode);
                return;
            }

            // Create coupon
            $coupon = Coupon::create([
                'code' => $couponCode,
                'user_id' => $user->id,
                'category' => 'loyalty',
                'discount_type' => 'percentage',
                'discount_value' => 50,
                'valid_from' => now(),
                'valid_until' => now()->addDays(30),
                'max_uses' => 1,
                'max_uses_per_user' => 1,
                'is_active' => true,
                'description' => 'Congratulations on completing 2 bookings!',
            ]);

            Log::info('✅ Coupon created successfully: ' . $couponCode);

            // Send email
            try {
                Mail::to($user->email)->send(new CouponRewardMail($user, $coupon, 10));
                Log::info('📧 Email sent to: ' . $user->email);
            } catch (\Exception $e) {
                Log::error('❌ Email sending failed: ' . $e->getMessage());
            }
        } else {
            Log::info('ℹ️ Milestone not reached yet: ' . $completedBookings . '/2');
        }
    }
}
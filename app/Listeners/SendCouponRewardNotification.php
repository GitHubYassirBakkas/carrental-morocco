<?php

namespace App\Listeners;

use App\Events\BookingCompleted;
use App\Mail\CouponRewardMail;
use App\Models\Booking;
use App\Models\Coupon;
use Illuminate\Support\Facades\Mail;

class SendCouponRewardNotification
{
    public function handle(BookingCompleted $event): void
    {
        $booking = $event->booking;
        $user = $booking->user;

        $completedBookings = $user->bookings()
            ->whereIn('status', [Booking::STATUS_COMPLETED, Booking::STATUS_CONFIRMED])
            ->count();

        if ($completedBookings !== 10) {
            return;
        }

        $couponCode = 'USER' . $user->id . 'GOLD50';

        if (Coupon::where('code', $couponCode)->exists()) {
            return;
        }

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
            'description' => 'Congratulations on completing 10 bookings!',
        ]);

        try {
            Mail::to($user->email)->send(new CouponRewardMail($user, $coupon, 10));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

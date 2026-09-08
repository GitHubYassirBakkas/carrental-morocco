<?php

namespace App\Listeners;

use App\Events\BookingCompleted;
use App\Mail\CouponRewardMail;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\CustomerLoyaltyReward;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendCouponRewardNotification
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(BookingCompleted $event): void
    {
        try {
            $reward = $this->createReward($event->booking);
        } catch (QueryException $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                Log::info('Loyalty reward already exists for milestone.', [
                    'booking_id' => $event->booking->id,
                    'exception' => $e::class,
                ]);

                return;
            }

            Log::error('Loyalty reward persistence failed.', [
                'booking_id' => $event->booking->id,
                'exception' => $e::class,
            ]);

            return;
        } catch (Throwable $e) {
            Log::error('Loyalty reward handling failed.', [
                'booking_id' => $event->booking->id,
                'exception' => $e::class,
            ]);

            return;
        }

        if (! $reward) {
            return;
        }

        $this->sendCommunication($reward);
    }

    private function createReward(Booking $eventBooking): ?CustomerLoyaltyReward
    {
        return DB::transaction(function () use ($eventBooking) {
            $booking = Booking::with('user')
                ->whereKey($eventBooking->id)
                ->lockForUpdate()
                ->first();

            if (! $booking || $booking->status !== Booking::STATUS_COMPLETED) {
                return null;
            }

            $user = User::whereKey($booking->user_id)
                ->lockForUpdate()
                ->first();

            if (! $user || (bool) $user->is_banned) {
                return null;
            }

            if ((int) $booking->user_id !== (int) $user->id) {
                return null;
            }

            $milestone = $this->completedOrdinalFor($user, $booking);

            if ($milestone < 10 || $milestone % 10 !== 0) {
                return null;
            }

            if (CustomerLoyaltyReward::where('user_id', $user->id)
                ->where('milestone', $milestone)
                ->exists()) {
                return null;
            }

            $coupon = Coupon::create([
                'code' => $this->generateCouponCode($milestone),
                'user_id' => $user->id,
                'category' => 'loyalty',
                'discount_type' => 'percentage',
                'discount_value' => 50,
                'valid_from' => now(),
                'valid_until' => now()->addDays(60),
                'max_uses' => 1,
                'max_uses_per_user' => 1,
                'is_active' => true,
                'description' => "Loyalty reward for completing {$milestone} rentals.",
            ]);

            return CustomerLoyaltyReward::create([
                'user_id' => $user->id,
                'milestone' => $milestone,
                'coupon_id' => $coupon->id,
                'qualifying_booking_id' => $booking->id,
                'awarded_at' => now(),
            ])->load(['user', 'coupon']);
        }, 3);
    }

    private function completedOrdinalFor(User $user, Booking $booking): int
    {
        $completedAt = ($booking->completed_at ?? $booking->updated_at ?? $booking->created_at)->toDateTimeString();

        return $user->bookings()
            ->where('status', Booking::STATUS_COMPLETED)
            ->where(function ($query) use ($completedAt, $booking) {
                $completionExpression = 'COALESCE(completed_at, updated_at, created_at)';

                $query->whereRaw("{$completionExpression} < ?", [$completedAt])
                    ->orWhere(function ($query) use ($completionExpression, $completedAt, $booking) {
                        $query->whereRaw("{$completionExpression} = ?", [$completedAt])
                            ->where('id', '<=', $booking->id);
                    });
            })
            ->count();
    }

    private function sendCommunication(CustomerLoyaltyReward $reward): void
    {
        $user = $reward->user;
        $coupon = $reward->coupon;

        if (! $user || ! $coupon) {
            return;
        }

        try {
            $this->notificationService->create(
                $user->id,
                'loyalty_reward_unlocked',
                'Loyalty reward unlocked',
                "You've completed {$reward->milestone} rentals and unlocked 50% off your next eligible booking. Coupon: {$coupon->code}. Valid until {$coupon->valid_until->format('d M Y')}.",
                [
                    'coupon_id' => $coupon->id,
                    'coupon_code' => $coupon->code,
                    'milestone' => $reward->milestone,
                    'expires_at' => $coupon->valid_until->toDateString(),
                    'cta_url' => route('cars.index'),
                ]
            );
        } catch (Throwable $e) {
            Log::warning('Loyalty reward notification failed.', [
                'customer_loyalty_reward_id' => $reward->id,
                'user_id' => $user->id,
                'coupon_id' => $coupon->id,
                'exception' => $e::class,
            ]);
        }

        try {
            Mail::to($user->email)->send(new CouponRewardMail($user, $coupon, $reward->milestone));
        } catch (Throwable $e) {
            Log::warning('Loyalty reward email failed.', [
                'customer_loyalty_reward_id' => $reward->id,
                'user_id' => $user->id,
                'coupon_id' => $coupon->id,
                'exception' => $e::class,
            ]);
        }
    }

    private function generateCouponCode(int $milestone): string
    {
        do {
            $code = 'LOYALTY-'.$milestone.'-'.Str::upper(Str::random(8));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }

    private function isUniqueConstraintViolation(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (string) ($e->errorInfo[1] ?? '');
        $message = strtolower($e->getMessage());

        return $driverCode === '1062'
            || $sqlState === '23505'
            || str_contains($message, 'unique constraint failed')
            || str_contains($message, 'duplicate entry');
    }
}

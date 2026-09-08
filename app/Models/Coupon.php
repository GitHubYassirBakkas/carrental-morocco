<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Coupon extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Coupon $coupon): void {
            if ($coupon->usages()->exists()) {
                throw new \RuntimeException('Coupons with usage history cannot be deleted. Deactivate the coupon instead.');
            }
        });
    }

    protected $fillable = [
        'code',
        'user_id',
        'category',
        'discount_type',
        'discount_value',
        'min_booking_amount',
        'min_bookings',
        'min_total_spent',
        'max_uses',
        'used_count',
        'max_uses_per_user',
        'allowed_car_types',
        'valid_from',
        'valid_until',
        'is_active',
        'description',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_booking_amount' => 'decimal:2',
        'min_total_spent' => 'decimal:2',
        'allowed_car_types' => 'array',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeValid(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where('valid_from', '<=', $now)
            ->where('valid_until', '>=', $now);
    }

    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereNull('user_id')
                ->orWhere('user_id', $userId);
        });
    }

    // ==========================================
    // VALIDATION METHODS
    // ==========================================

    /**
     * Check if coupon can be used
     */
    public function canBeUsed(?int $userId = null, ?float $bookingAmount = null, ?string $carType = null): array
    {
        // Not active
        if (! $this->is_active) {
            return ['valid' => false, 'message' => 'This coupon is not active.'];
        }

        // Date validation
        $now = now();
        if ($this->valid_from && $this->valid_from->isFuture()) {
            return ['valid' => false, 'message' => 'This coupon is not yet valid.'];
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return ['valid' => false, 'message' => 'This coupon has expired.'];
        }

        // Max uses reached
        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return ['valid' => false, 'message' => 'This coupon has reached its maximum usage limit.'];
        }

        // User-specific coupon
        if ($this->user_id && $userId && $this->user_id != $userId) {
            return ['valid' => false, 'message' => 'This coupon is not available for your account.'];
        }

        // User usage limit
        if ($userId) {
            $userUsageCount = $this->usages()->where('user_id', $userId)->count();
            if ($userUsageCount >= $this->max_uses_per_user) {
                return ['valid' => false, 'message' => 'You have already used this coupon the maximum number of times.'];
            }
        }

        // Minimum booking amount
        if ($this->min_booking_amount && $bookingAmount && $bookingAmount < $this->min_booking_amount) {
            return [
                'valid' => false,
                'message' => 'Minimum booking amount of '.number_format($this->min_booking_amount, 2).' MAD required.',
            ];
        }

        $allowedCarTypes = $this->normalizedAllowedCarTypes();
        if ($allowedCarTypes !== []) {
            $normalizedCarType = $this->normalizeCarType($carType);

            if ($normalizedCarType === null || ! in_array($normalizedCarType, $allowedCarTypes, true)) {
                return [
                    'valid' => false,
                    'message' => 'This coupon is not valid for the selected vehicle type.',
                ];
            }
        }

        // Check user qualifications (bookings count, total spent)
        if ($userId) {
            $user = User::find($userId);

            if ($this->min_bookings) {
                $userBookingsCount = $user->bookings()->whereIn('status', [Booking::STATUS_COMPLETED, Booking::STATUS_CONFIRMED])->count();
                if ($userBookingsCount < $this->min_bookings) {
                    return [
                        'valid' => false,
                        'message' => 'You need at least '.$this->min_bookings.' completed bookings to use this coupon.',
                    ];
                }
            }

            if ($this->min_total_spent) {
                $userTotalSpent = $user->bookings()->whereIn('status', [Booking::STATUS_COMPLETED, Booking::STATUS_CONFIRMED])->sum('total_amount');
                if ($userTotalSpent < $this->min_total_spent) {
                    return [
                        'valid' => false,
                        'message' => 'You need to have spent at least '.number_format($this->min_total_spent, 2).' MAD to use this coupon.',
                    ];
                }
            }
        }

        return ['valid' => true, 'message' => 'Coupon is valid!'];
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->discount_type === 'percentage') {
            return ($amount * $this->discount_value) / 100;
        }

        // Fixed amount
        return min($this->discount_value, $amount); // Don't exceed total amount
    }

    /**
     * Apply coupon to booking
     */
    public function apply(int $userId, int $bookingId, float $originalAmount, ?string $carType = null): array
    {
        return DB::transaction(function () use ($userId, $bookingId, $originalAmount, $carType) {
            $coupon = static::whereKey($this->id)
                ->lockForUpdate()
                ->first();

            if (! $coupon) {
                return [
                    'valid' => false,
                    'message' => 'This coupon is no longer available.',
                ];
            }

            return $coupon->applyAfterFinalValidation($userId, $bookingId, $originalAmount, $carType);
        }, 3);
    }

    private function applyAfterFinalValidation(int $userId, int $bookingId, float $originalAmount, ?string $carType = null): array
    {
        $validation = $this->canBeUsed($userId, $originalAmount, $carType);

        if (! $validation['valid']) {
            return $validation;
        }

        $alreadyUsedForBooking = CouponUsage::where('coupon_id', $this->id)
            ->where('booking_id', $bookingId)
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyUsedForBooking) {
            return [
                'valid' => false,
                'message' => 'This coupon has already been used for this booking by this user.',
            ];
        }

        $discountAmount = $this->calculateDiscount($originalAmount);
        $finalAmount = $originalAmount - $discountAmount;

        // Record usage
        CouponUsage::create([
            'coupon_id' => $this->id,
            'user_id' => $userId,
            'booking_id' => $bookingId,
            'discount_amount' => $discountAmount,
            'original_amount' => $originalAmount,
            'final_amount' => $finalAmount,
        ]);

        // Increment usage count
        $this->increment('used_count');

        return [
            'valid' => true,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
        ];
    }

    private function normalizedAllowedCarTypes(): array
    {
        return collect($this->allowed_car_types ?? [])
            ->map(fn ($type) => $this->normalizeCarType($type))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeCarType(mixed $type): ?string
    {
        $normalized = strtolower(trim((string) $type));

        return $normalized === '' ? null : $normalized;
    }

    // ==========================================
    // ATTRIBUTES
    // ==========================================

    public function getDiscountDisplayAttribute(): string
    {
        if ($this->discount_type === 'percentage') {
            return $this->discount_value.'%';
        }

        return number_format($this->discount_value, 0).' MAD';
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'welcome' => 'Welcome Offer',
            'loyalty' => 'Loyalty Reward',
            'seasonal' => 'Seasonal Promotion',
            'referral' => 'Referral Program',
            'retention' => 'Win-back Offer',
            'corporate' => 'Corporate Account',
            'apology' => 'Service Recovery',
            default => 'General'
        };
    }
}

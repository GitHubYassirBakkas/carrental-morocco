<?php

namespace App\Models;

use App\Models\Concerns\ProtectsHistoricalRecords;
use App\Services\Pricing\BookingPricingService;
use App\Services\SecurityDepositService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, ProtectsHistoricalRecords, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_CANCELED_ALIAS = 'canceled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_ACTIVE,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    public const ACTIVE_OR_RESERVED_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_ACTIVE,
    ];

    public const CANCELLABLE_STATUSES = self::ACTIVE_OR_RESERVED_STATUSES;

    public const ADVANCE_PAYMENT_STATUS_PENDING = 'pending';

    public const ADVANCE_PAYMENT_STATUS_PAID = 'paid';

    public const SECURITY_DEPOSIT_STATUS_PENDING = 'pending';

    public const SECURITY_DEPOSIT_STATUS_HELD = 'held';

    public const SECURITY_DEPOSIT_STATUS_CAPTURED = 'captured';

    public const SECURITY_DEPOSIT_STATUS_REFUND_PENDING = 'refund_pending';

    public const SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    public const SECURITY_DEPOSIT_STATUS_REFUNDED = 'refunded';

    public const SECURITY_DEPOSIT_STATUS_RELEASED = 'released';

    public const SECURITY_DEPOSIT_FINAL_STATUSES = [
        self::SECURITY_DEPOSIT_STATUS_CAPTURED,
        self::SECURITY_DEPOSIT_STATUS_REFUND_PENDING,
        self::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED,
        self::SECURITY_DEPOSIT_STATUS_REFUNDED,
    ];

    public const SECURITY_DEPOSIT_ACTIONABLE_STATUSES = [
        self::SECURITY_DEPOSIT_STATUS_HELD,
    ];

    public const SECURITY_DEPOSIT_REFUNDED_STATUSES = [
        self::SECURITY_DEPOSIT_STATUS_REFUNDED,
        self::SECURITY_DEPOSIT_STATUS_RELEASED,
    ];

    public const SECURITY_DEPOSIT_HELD_OR_FINAL_UNRELEASED_STATUSES = [
        self::SECURITY_DEPOSIT_STATUS_HELD,
        self::SECURITY_DEPOSIT_STATUS_CAPTURED,
        self::SECURITY_DEPOSIT_STATUS_REFUND_PENDING,
        self::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED,
    ];

    protected $fillable = [
        'user_id',
        'car_id',
        'insurance_id',
        'pickup_location_id',
        'dropoff_location_id',
        'start_date',
        'end_date',
        'rental_price_per_day',
        'insurance_fixed_price',
        'total_amount',
        'status',
        'special_requests',
        'pickup_actual',
        'return_actual',
        'initial_mileage',
        'return_mileage',
        'notes',
        'invoiced_at',
        'driver_name',
        'driver_license_number',
        'additional_driver_name',
        'additional_driver_license',
        'advance_payment_amount',
        'security_deposit_amount',
        'security_deposit_capturable_amount',
        'security_deposit_intent_id',
        'rental_payment_intent_id',
        'security_deposit_status',
        'security_deposit_released_at',
        'security_deposit_charged_amount',
        'security_deposit_penalty_amount',
        'security_deposit_refunded_amount',
        'security_deposit_refund_id',
        'security_deposit_captured_by',
        'security_deposit_captured_at',
        'security_deposit_refunded_by',
        'security_deposit_refunded_at',
        'security_deposit_penalty_reason',
        'security_deposit_refund_error_message',
        'security_deposit_processed_by',
        'advance_payment_status',
        'advance_payment_paid_at',
        'advance_payment_due_at',
        'pickup_instructions',
        'return_instructions',
        'coupon_id',
        'discount_amount',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'completed_at' => 'datetime',
        'started_at' => 'datetime',
        'pickup_actual' => 'datetime',
        'return_actual' => 'datetime',
        'rental_price_per_day' => 'decimal:2',
        'insurance_fixed_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'advance_payment_amount' => 'decimal:2',
        'security_deposit_amount' => 'decimal:2',
        'security_deposit_capturable_amount' => 'decimal:2',
        'security_deposit_charged_amount' => 'decimal:2',
        'security_deposit_penalty_amount' => 'decimal:2',
        'security_deposit_refunded_amount' => 'decimal:2',
        'security_deposit_released_at' => 'datetime',
        'security_deposit_captured_at' => 'datetime',
        'security_deposit_refunded_at' => 'datetime',
        'advance_payment_paid_at' => 'datetime',
        'advance_payment_due_at' => 'datetime',
        'discount_amount' => 'decimal:2',
    ];

    protected $appends = [
        'timeline_status',
        'is_late',
        'late_minutes',
        'late_fee',
        'progress_percent',
        'time_label',
        'total_days',
    ];

    protected static function historicalRecordDeleteMessage(): string
    {
        return 'Bookings are historical business records and cannot be permanently deleted. Archive them with soft delete instead.';
    }

    /* ================= SETTINGS ================= */
    /* ================= RELATIONS ================= */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function securityDepositCapturedBy()
    {
        return $this->belongsTo(User::class, 'security_deposit_captured_by');
    }

    public function securityDepositRefundedBy()
    {
        return $this->belongsTo(User::class, 'security_deposit_refunded_by');
    }

    public function securityDepositProcessedBy()
    {
        return $this->belongsTo(User::class, 'security_deposit_processed_by');
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function insurance()
    {
        return $this->belongsTo(Insurance::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function pickupLocation()
    {
        return $this->belongsTo(Location::class, 'pickup_location_id');
    }

    public function dropoffLocation()
    {
        return $this->belongsTo(Location::class, 'dropoff_location_id');
    }

    /* ================= HELPERS ================= */

    public function getTotalDaysAttribute(): int
    {
        if (! $this->start_date || ! $this->end_date) {
            return 1;
        }

        return max(1, $this->start_date->diffInDays($this->end_date));
    }

    public function hasStatus(string $status): bool
    {
        return $this->status === $status;
    }

    public function isPending()
    {
        return $this->hasStatus(self::STATUS_PENDING);
    }

    public function isConfirmed()
    {
        return $this->hasStatus(self::STATUS_CONFIRMED);
    }

    public function isActive()
    {
        return $this->hasStatus(self::STATUS_ACTIVE);
    }

    public function isCompleted()
    {
        return $this->hasStatus(self::STATUS_COMPLETED);
    }

    public function isCancelled()
    {
        return $this->hasStatus(self::STATUS_CANCELLED);
    }

    public function hasAdvancePaymentStatus(string $status): bool
    {
        return $this->advance_payment_status === $status;
    }

    public function isAdvancePaymentPaid(): bool
    {
        return $this->hasAdvancePaymentStatus(self::ADVANCE_PAYMENT_STATUS_PAID);
    }

    public function hasSecurityDepositStatus(string $status): bool
    {
        return $this->security_deposit_status === $status;
    }

    /* ================= TIMELINE ================= */

    public function getTimelineStatusAttribute()
    {
        $now = now();

        if ($this->isCancelled()) {
            return self::STATUS_CANCELLED;
        }
        if ($this->isCompleted()) {
            return self::STATUS_COMPLETED;
        }

        if ($this->start_date && $now->lt($this->start_date)) {
            return 'upcoming';
        }

        if ($this->start_date && $this->end_date && $now->between($this->start_date, $this->end_date)) {
            return 'ongoing';
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return 'late';
        }

        return 'unknown';
    }

    /* ================= LATE LOGIC ================= */

    public function getIsLateAttribute()
    {
        if (! $this->end_date) {
            return false;
        }

        $return = $this->return_actual ?? now();

        return $return->gt($this->end_date);
    }

    public function getLateMinutesAttribute()
    {
        if (! $this->is_late || ! $this->end_date) {
            return 0;
        }

        $return = $this->return_actual ?? now();

        return $this->end_date->diffInMinutes($return);
    }

    public function getLateFeeAttribute()
    {
        if (! $this->is_late) {
            return 0;
        }

        $graceMinutes = (int) config('rental.late_grace_minutes', 60);

        if ($this->late_minutes <= $graceMinutes) {
            return 0;
        }

        $chargeableMinutes = $this->late_minutes - $graceMinutes;
        $hours = ceil($chargeableMinutes / 60);

        return $hours * (float) config('rental.late_fee_per_hour', 50);
    }

    /* ================= PROGRESS ================= */

    public function getProgressPercentAttribute()
    {
        if (! $this->start_date || ! $this->end_date) {
            return 0;
        }

        $total = $this->start_date->diffInSeconds($this->end_date);
        if ($total <= 0) {
            return 100;
        }

        $passed = $this->start_date->diffInSeconds(now(), false);

        if ($passed <= 0) {
            return 0;
        }
        if ($passed >= $total) {
            return 100;
        }

        return round(($passed / $total) * 100);
    }

    public function getTimeLabelAttribute()
    {
        if (! $this->start_date || ! $this->end_date) {
            return '-';
        }

        if ($this->timeline_status === 'upcoming') {
            return 'Starts in '.now()->diffForHumans($this->start_date, true);
        }

        if ($this->timeline_status === 'ongoing') {
            return 'Ends in '.now()->diffForHumans($this->end_date, true);
        }

        if ($this->timeline_status === 'late') {
            return 'Late by '.now()->diffForHumans($this->end_date, true);
        }

        if ($this->timeline_status === 'completed') {
            return 'Completed';
        }

        return '-';
    }

    public function inspections()
    {
        return $this->hasMany(BookingInspection::class);
    }

    public function checkinInspection()
    {
        return $this->hasOne(BookingInspection::class)
            ->where('type', 'checkin');
    }

    public function checkoutInspection()
    {
        return $this->hasOne(BookingInspection::class)
            ->where('type', 'checkout');
    }

    public function damages()
    {
        return $this->hasMany(BookingDamage::class);
    }

    public function checkinDamages()
    {
        return $this->damages()->where('stage', 'checkin');
    }

    public function checkoutDamages()
    {
        return $this->damages()->where('stage', 'checkout');
    }

    public function getTotalCheckoutDamageAttribute()
    {
        return $this->checkoutDamages()
            ->where('is_chargeable', true)
            ->sum('estimated_cost');
    }

    public function getMileageDifferenceAttribute()
    {
        if (! $this->checkinInspection || ! $this->checkoutInspection) {
            return 0;
        }

        return $this->checkoutInspection->mileage - $this->checkinInspection->mileage;
    }

    public function getFuelDifferenceAttribute()
    {
        return max(
            0,
            ($this->fuel_at_pickup_percent ?? 0)
            - ($this->fuel_at_return_percent ?? 0)
        );
    }

    public function getFinalTotalAttribute()
    {
        return $this->total_amount
            + ($this->fuel_charge ?? 0)
            + ($this->late_fee ?? 0)
            + $this->damages()->sum('estimated_cost');
    }

    public function calculateFuelCharge()
    {
        if (
            $this->fuel_at_pickup_percent === null ||
            $this->fuel_at_return_percent === null
        ) {
            return 0;
        }

        $missingPercent = $this->fuel_at_pickup_percent - $this->fuel_at_return_percent;

        if ($missingPercent <= 0) {
            return 0;
        }

        $tankCapacity = $this->car->fuel_tank_capacity;
        $pricePerLiter = $this->car->fuel_price_per_liter;

        $missingLiters = $tankCapacity * ($missingPercent / 100);

        return round($missingLiters * $pricePerLiter, 2);
    }

    public function calculateLate()
    {
        if ($this->isCompleted()) {
            return [
                'minutes' => $this->late_minutes,
                'fee' => $this->late_fee,
            ];
        }

        if (now()->lte($this->end_date)) {
            return [
                'minutes' => 0,
                'fee' => 0,
            ];
        }

        $minutes = now()->diffInMinutes($this->end_date);

        return [
            'minutes' => $minutes,
            'fee' => $minutes * 5,
        ];
    }

    public function calculateFuel()
    {
        return [
            'used' => $this->fuel_used,
            'charge' => $this->fuel_charge,
        ];
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function getPricingBreakdownAttribute(): array
    {
        return app(BookingPricingService::class)
            ->breakdownForBooking($this);
    }

    public function getInvoiceLineItemsAttribute(): array
    {
        return app(BookingPricingService::class)
            ->lineItems($this->pricing_breakdown);
    }

    public function canComplete()
    {
        return $this->isActive();
    }

    public function isAdvancePaymentOverdue(): bool
    {
        if (! $this->advance_payment_due_at) {
            return false;
        }

        if ($this->isAdvancePaymentPaid()) {
            return false;
        }

        return now()->greaterThan($this->advance_payment_due_at);
    }

    public function isSecurityDepositSafeToRelease(): bool
    {
        return $this->isSecurityDepositActionable()
            && ! $this->isCompleted();
    }

    public function getSecurityDepositEffectiveState(): string
    {
        return app(SecurityDepositService::class)
            ->getSecurityDepositEffectiveState($this);
    }

    public function isSecurityDepositActionable(): bool
    {
        return app(SecurityDepositService::class)
            ->isSecurityDepositActionable($this);
    }

    public function isSecurityDepositRefundable(): bool
    {
        return app(SecurityDepositService::class)
            ->isSecurityDepositRefundable($this);
    }

    public function couponUsage()
    {
        return $this->hasOne(CouponUsage::class);
    }

    /**
     * Scope for bookings waiting on advance payment.
     */
    public function scopePendingAdvancePayment($query)
    {
        return $query->where('advance_payment_status', '!=', self::ADVANCE_PAYMENT_STATUS_PAID)
            ->where('status', self::STATUS_PENDING);
    }

    public function scopeActiveOrReserved(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_OR_RESERVED_STATUSES);
    }

    public function scopeOverlapping(Builder $query, $startDate, $endDate): Builder
    {
        return $query->where('start_date', '<', $endDate)
            ->where('end_date', '>', $startDate);
    }
}

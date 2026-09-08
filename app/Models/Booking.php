<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Location;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'car_id',
        'insurance_id',
        'pickup_location_id',
        'dropoff_location_id',
        'start_date',
        'end_date',
        'daily_rate',
        'insurance_daily_rate',
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
        'deposit_amount',
        'deposit_payment_intent_id',
        'deposit_status',
        'deposit_charged_amount',
        'deposit_paid',
        'deposit_paid_at',      // ← Add this
        'deposit_due_at',       // ← Add this
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
        'daily_rate' => 'decimal:2',
        'insurance_daily_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'deposit_charged_amount' => 'decimal:2',
        'deposit_paid' => 'boolean',
        'deposit_paid_at' => 'datetime',    // ← Add this
        'deposit_due_at' => 'datetime',     // ← Add this
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

    /* ================= SETTINGS ================= */
    const GRACE_MINUTES = 60;     // 1 hours free
    const HOURLY_LATE_FEE = 50;    // MAD per hour

    /* ================= RELATIONS ================= */

    public function user()
    {
        return $this->belongsTo(User::class);
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
        if (!$this->start_date || !$this->end_date) {
            return 1;
        }

        return max(1, $this->start_date->diffInDays($this->end_date));
    }

    public function isPending()   { return $this->status === 'pending'; }
    public function isConfirmed() { return $this->status === 'confirmed'; }
    public function isActive()    { return $this->status === 'active'; }
    public function isCompleted() { return $this->status === 'completed'; }
    public function isCancelled() { return $this->status === 'cancelled'; }

    /* ================= TIMELINE ================= */

    public function getTimelineStatusAttribute()
    {
        $now = now();

        if ($this->status === 'cancelled') return 'cancelled';
        if ($this->status === 'completed') return 'completed';

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
        if (!$this->end_date) return false;

        $return = $this->return_actual ?? now();

        return $return->gt($this->end_date);
    }

    public function getLateMinutesAttribute()
    {
        if (!$this->is_late || !$this->end_date) return 0;

        $return = $this->return_actual ?? now();

        return $this->end_date->diffInMinutes($return);
    }

    public function getLateFeeAttribute()
    {
        if (!$this->is_late) return 0;

        if ($this->late_minutes <= self::GRACE_MINUTES) {
            return 0;
        }

        $chargeableMinutes = $this->late_minutes - self::GRACE_MINUTES;
        $hours = ceil($chargeableMinutes / 60);

        return $hours * self::HOURLY_LATE_FEE;
    }

    /* ================= PROGRESS ================= */

    public function getProgressPercentAttribute()
    {
        if (!$this->start_date || !$this->end_date) return 0;

        $total = $this->start_date->diffInSeconds($this->end_date);
        if ($total <= 0) return 100;

        $passed = $this->start_date->diffInSeconds(now(), false);

        if ($passed <= 0) return 0;
        if ($passed >= $total) return 100;

        return round(($passed / $total) * 100);
    }

    public function getTimeLabelAttribute()
    {
        if (!$this->start_date || !$this->end_date) {
            return '-';
        }

        if ($this->timeline_status === 'upcoming') {
            return 'Starts in ' . now()->diffForHumans($this->start_date, true);
        }

        if ($this->timeline_status === 'ongoing') {
            return 'Ends in ' . now()->diffForHumans($this->end_date, true);
        }

        if ($this->timeline_status === 'late') {
            return 'Late by ' . now()->diffForHumans($this->end_date, true);
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
    return $this->damages()->where('stage','checkin');
}

public function checkoutDamages()
{
    return $this->damages()->where('stage','checkout');
}

public function getTotalCheckoutDamageAttribute()
{
    return $this->checkoutDamages()
                ->where('is_chargeable', true)
                ->sum('estimated_cost');
}

public function getMileageDifferenceAttribute()
{
    if (!$this->checkinInspection || !$this->checkoutInspection) {
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

    $missingPercent =
        $this->fuel_at_pickup_percent - $this->fuel_at_return_percent;

    if ($missingPercent <= 0) {
        return 0;
    }

    $tankCapacity = $this->car->fuel_tank_capacity; // ex: 50L
    $pricePerLiter = $this->car->fuel_price_per_liter; // ex: 13 MAD

    $missingLiters = $tankCapacity * ($missingPercent / 100);

    return round($missingLiters * $pricePerLiter, 2);
}





public function calculateLate()
{
    if ($this->status === 'completed') {
        return [
            'minutes' => $this->late_minutes,
            'fee' => $this->late_fee
        ];
    }

    if (now()->lte($this->end_date)) {
        return [
            'minutes' => 0,
            'fee' => 0
        ];
    }

    $minutes = now()->diffInMinutes($this->end_date);

    return [
        'minutes' => $minutes,
        'fee' => $minutes * 5
    ];
}

public function calculateFuel()
{
    return [
        'used' => $this->fuel_used,
        'charge' => $this->fuel_charge
    ];
}

public function invoice()
{
    return $this->hasOne(Invoice::class);
}


public function canComplete()
{
    // Define the logic for when a booking can be completed
    // For example, check if the booking is in 'active' status
    return $this->status === 'active';
    
    // Or you might want to check multiple conditions:
    // return $this->status === 'active' && $this->payment_status === 'paid';
}

public function isDepositOverdue(): bool
{
    // ila ma kaynach deposit_due_at → false
    if (!$this->deposit_due_at) return false;

    // ila tpaid deja → false
    if ($this->deposit_paid) return false;

    // ila deadline fat w ma tpaidch → true
    return now()->greaterThan($this->deposit_due_at);
}

public function couponUsage()
{
    return $this->hasOne(CouponUsage::class);
}


/**
 * Scope for pending deposit bookings
 */
public function scopePendingDeposit($query)
{
    return $query->where('deposit_paid', false)
                 ->where('status', 'pending');
}
    }

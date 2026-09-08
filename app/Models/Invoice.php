<?php

namespace App\Models;

use App\Models\Concerns\ProtectsHistoricalRecords;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use ProtectsHistoricalRecords;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PARTIAL,
        self::STATUS_PAID,
        self::STATUS_REFUNDED,
        self::STATUS_CANCELLED,
    ];

    protected static function historicalRecordDeleteMessage(): string
    {
        return 'Invoices are financial history and cannot be deleted.';
    }

    protected $fillable = [
        'booking_id',
        'user_id',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'issued_at',
        'due_date',      
        'discount_amount',

    ];

    protected $casts = [
        'subtotal'      => 'decimal:2',
        'tax_amount'    => 'decimal:2',
        'total_amount'  => 'decimal:2',
        'issued_at'     => 'datetime',
        'due_date'      => 'datetime',
        'discount_amount' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // مجموع الدفوعات المكتملة
    public function getPaidAmountAttribute()
    {
        $payments = $this->payments()
            ->where('type', Payment::TYPE_PAYMENT)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        $refunds = $this->payments()
            ->where('type', Payment::TYPE_REFUND)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        return app(\App\Services\Pricing\BookingPricingService::class)
            ->calculatePaidAmount((float) $payments, (float) $refunds);
    }

    // الرصيد المتبقي
    public function getBalanceAttribute()
    {
        return app(\App\Services\Pricing\BookingPricingService::class)
            ->calculateBalance((float) $this->total_amount, (float) $this->paid_amount);
    }

    public function getPricingBreakdownAttribute(): array
    {
        return app(\App\Services\Pricing\BookingPricingService::class)
            ->breakdownForInvoice($this);
    }

    public function getLineItemsAttribute(): array
    {
        return app(\App\Services\Pricing\BookingPricingService::class)
            ->lineItems($this->pricing_breakdown);
    }
}

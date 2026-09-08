<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'booking_id',
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
        return $this->payments()
            ->where('status', 'completed')
            ->sum('amount');
    }

    // الرصيد المتبقي
    public function getBalanceAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }
}

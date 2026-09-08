<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerLoyaltyReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'milestone',
        'coupon_id',
        'qualifying_booking_id',
        'awarded_at',
    ];

    protected $casts = [
        'milestone' => 'integer',
        'awarded_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function qualifyingBooking()
    {
        return $this->belongsTo(Booking::class, 'qualifying_booking_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingDamage extends Model
{
    protected $fillable = [
        'booking_id',
        'stage',
        'part',
        'type',
        'description',
        'photos',
        'estimated_cost',
        'is_chargeable',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'is_chargeable' => 'boolean',
        'photos' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
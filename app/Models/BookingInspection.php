<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'fuel_level',
        'mileage',
        'has_damage',
        'damage_notes',
        'created_by',
        'booking_inspection_id',
        'path',
        'type',
        'notes',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function photos()
    {
        return $this->hasMany(
            BookingPhoto::class,
            'booking_inspection_id'
        );
    }
}


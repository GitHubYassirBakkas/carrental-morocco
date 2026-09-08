<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'car_id',
        'booking_id',
        'rating',
        'comment',
        'is_approved',
        'approved_at',
        'response',
        'response_date'
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'response_date' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function scopeForCar($query, $carId)
    {
        return $query->where('car_id', $carId);
    }

    public function getAverageRatingAttribute()
    {
        return $this->where('car_id', $this->car_id)
            ->approved()
            ->avg('rating');
    }
}

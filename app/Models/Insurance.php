<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Insurance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'description',
        'fixed_price',
        'max_coverage',
        'deductible',
        'excess_fee',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'fixed_price' => 'decimal:2',
        'max_coverage' => 'decimal:2',
        'deductible' => 'decimal:2',
        'excess_fee' => 'decimal:2',
        'is_active' => 'boolean',
        'features' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * Cars that offer this insurance
     */
    public function cars()
    {
        return $this->belongsToMany(Car::class, 'car_insurance')
            ->withPivot('is_default')
            ->withTimestamps();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Scope: Active insurances only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordered by sort_order and fixed_price
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('fixed_price');
    }

    /**
     * Get insurance badge color
     */
    public function getBadgeColorAttribute()
    {
        return match($this->type) {
            'basic' => 'blue',
            'standard' => 'yellow',
            'premium' => 'green',
            default => 'gray',
        };
    }

    /**
     * Get insurance display name with icon
     */
    public function getDisplayNameAttribute()
    {
        $icons = [
            'basic' => '🛡️',
            'standard' => '⭐',
            'premium' => '👑',
        ];
        
        return ($icons[$this->type] ?? '📋') . ' ' . $this->name;
    }

    public function getInsuranceFeeAttribute()
    {
        return $this->fixed_price; // One-time protection fee.
    }
}

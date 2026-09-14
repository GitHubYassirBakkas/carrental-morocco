<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'city',
        'country',
        'postal_code',
        'phone',
        'email',
        'opening_time',
        'closing_time',
        'notes',
        'is_active',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * ✅ السيارات المتوفرة في هذا الموقع
     */
    public function cars()
    {
        return $this->hasMany(Car::class);
    }

    /**
     * ✅ الحجوزات التي تبدأ من هذا الموقع (Pick-up)
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'pickup_location_id');
    }

    /**
     * ✅ الحجوزات التي تنتهي في هذا الموقع (Drop-off)
     */
    public function dropoffBookings()
    {
        return $this->hasMany(Booking::class, 'dropoff_location_id');
    }

    // ==========================================
    // ATTRIBUTES (Accessors)
    // ==========================================

    /**
     * ✅ العنوان الكامل (مع الرمز البريدي)
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * ✅ التحقق من وجود إحداثيات GPS
     */
    public function getHasCoordinatesAttribute(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * ✅ رابط Google Maps مدمج (iframe)
     * ⚠️ استبدل YOUR_API_KEY بمفتاح Google Maps الخاص بك
     */
    public function getMapEmbedUrlAttribute(): ?string
    {
        if (! $this->has_coordinates) {
            return null;
        }

        // استخدم مفتاح API الخاص بك هنا
        $apiKey = config('services.google_maps.api_key');

        return "https://www.google.com/maps/embed/v1/place?key={$apiKey}&q={$this->latitude},{$this->longitude}&zoom=15";
    }

    /**
     * ✅ رابط Google Maps المباشر (يفتح في علامة تبويب جديدة)
     */
    public function getMapLinkAttribute(): ?string
    {
        if (! $this->has_coordinates) {
            return null;
        }

        return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    /**
     * ✅ أوقات العمل بصيغة قابلة للقراءة
     */
    public function getWorkingHoursAttribute(): ?string
    {
        if (empty($this->opening_time) || empty($this->closing_time)) {
            return 'غير محدد';
        }

        return "{$this->opening_time} - {$this->closing_time}";
    }

    /**
     * ✅ التحقق من أن الموقع مفتوح الآن
     */
    public function getIsOpenNowAttribute(): bool
    {
        if (empty($this->opening_time) || empty($this->closing_time)) {
            return false;
        }

        $now = now()->format('H:i:s');

        return $now >= $this->opening_time && $now <= $this->closing_time;
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * ✅ المواقع النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * ✅ المواقع حسب المدينة
     */
    public function scopeInCity($query, string $city)
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    /**
     * ✅ المواقع التي لديها إحداثيات GPS
     */
    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand',
        'model',
        'year',
        'type',
        'transmission',
        'fuel_type',
        'seats',
        'doors',
        'luggage',
        'mileage',
        'price_per_day',
        'image',
        'gallery',
        'description',
        'features',
        'is_available',
        'location_id',

        // 📋 Rental Conditions
        'minimum_age',
        'fuel_policy',
        'cancellation_policy',
        'security_deposit_amount',
        'required_documents',
    ];

    protected $casts = [
        'features' => 'array', // ✅ IMPORTANT: Convert JSON to array
        'gallery' => 'array',
        'required_documents' => 'array',
        'is_available' => 'boolean',
        'price_per_day' => 'decimal:2',
        'security_deposit_amount' => 'decimal:2',
        'year' => 'integer',
        'seats' => 'integer',
        'doors' => 'integer',
        'luggage' => 'integer',
        'mileage' => 'integer',
        'minimum_age' => 'integer',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Car $car): void {
            if ($car->hasBusinessHistory()) {
                throw new \RuntimeException('Cars with booking or review history cannot be deleted. Mark the car unavailable instead.');
            }
        });
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'wishlists');
    }

    public function hasBusinessHistory(): bool
    {
        return $this->bookings()->withTrashed()->exists()
            || $this->reviews()->exists();
    }

    /**
     * Insurance options available for this car
     */
    public function insurances()
    {
        return $this->belongsToMany(Insurance::class, 'car_insurance')
            ->withPivot('is_default')
            ->withTimestamps()
            ->orderBy('sort_order');
    }

    /**
     * Get available insurances or default all if none assigned
     */
    public function getAvailableInsurancesAttribute()
    {
        // If car has specific insurances assigned, use those
        if ($this->insurances()->count() > 0) {
            return $this->insurances;
        }

        // Otherwise, return all active insurances
        return Insurance::active()->ordered()->get();
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    /**
     * ✅ Full car name (e.g., "2024 BMW X5")
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->year} {$this->brand} {$this->model}";
    }

    /**
     * ✅ Check if car is available for given date range
     */
    public function isAvailableForDates($startDate, $endDate): bool
    {
        return ! $this->bookings()
            ->activeOrReserved()
            ->overlapping($startDate, $endDate)
            ->exists();
    }

    /**
     * ✅ Get all images (main + gallery)
     */
    /**
     * Resolve a car image whether it came from an admin upload or the seeded
     * public image set.
     */
    public function resolveImageUrl(?string $image = null): string
    {
        $image = $image ?: $this->image;
        $fallback = asset('images/cars/Route.jpg');

        if (! $image) {
            return $fallback;
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        $filename = basename($image);
        $storagePath = 'cars/'.$filename;

        if (Storage::disk('public')->exists($storagePath)) {
            return Storage::url($storagePath);
        }

        $publicPath = public_path('images/cars/'.$filename);

        if (is_file($publicPath)) {
            return asset('images/cars/'.$filename);
        }

        foreach (glob(public_path('images/cars/*')) ?: [] as $candidate) {
            if (strtolower(basename($candidate)) === strtolower($filename)) {
                return asset('images/cars/'.basename($candidate));
            }
        }

        return $fallback;
    }

    public function getImageUrlAttribute(): string
    {
        return $this->resolveImageUrl();
    }

    public function getAllImagesAttribute(): array
    {
        $images = [];

        if ($this->image) {
            $images[] = $this->image_url;
        }

        if (is_array($this->gallery)) {
            foreach ($this->gallery as $img) {
                $images[] = $this->resolveImageUrl($img);
            }
        }

        return array_values(array_unique($images)) ?: [$this->image_url];
    }

    /**
     * ✅ Get average rating
     */
    public function getAverageRatingAttribute(): float
    {
        return (float) $this->reviews()->avg('rating') ?? 0;
    }

    /**
     * ✅ Format price with currency
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price_per_day, 2).' MAD';
    }
}

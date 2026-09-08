<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'country',
        'postal_code',
        'profile_photo_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Scope: Only regular users.
     */
    public function scopeUsers($query)
    {
        return $query->where('role', 'user');
    }

    public function scopeCustomers($query)
    {
        return $this->scopeUsers($query);
    }

    /**
     * Scope: Only admins
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope: Banned users
     */
    public function scopeBanned($query)
    {
        return $query->where('is_banned', true);
    }

    /**
     * Scope: Active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_banned', false);
    }

    /**
     * User's reviews
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function ticketMessages()
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function couponUsages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function loyaltyRewards()
    {
        return $this->hasMany(CustomerLoyaltyReward::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function favoriteCars()
    {
        return $this->belongsToMany(Car::class, 'wishlists');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function getTotalSpentAttribute(): float
    {
        return $this->bookings()
            ->whereIn('status', [\App\Models\Booking::STATUS_COMPLETED, \App\Models\Booking::STATUS_CONFIRMED])
            ->sum('total_amount');
    }

    public function hasBusinessHistory(): bool
    {
        return $this->bookings()->withTrashed()->exists()
            || $this->invoices()->exists()
            || $this->payments()->exists()
            || $this->reviews()->exists()
            || $this->tickets()->exists()
            || $this->ticketMessages()->exists()
            || $this->couponUsages()->exists()
            || $this->loyaltyRewards()->exists();
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
            if ($user->hasBusinessHistory()) {
                throw new \RuntimeException('Users with booking, payment, review, coupon, or support history cannot be deleted. Ban the user instead.');
            }

            if (app()->environment(['production', 'staging'])) {
                abort(403, '🚨 Deleting users is blocked in Safe Mode');
            }
        });
    }
}

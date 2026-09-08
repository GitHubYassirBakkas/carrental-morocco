<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
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
     'role',
     'is_banned',
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

public function hasBusinessHistory(): bool
{
    return $this->bookings()->withTrashed()->exists()
        || $this->invoices()->exists()
        || $this->payments()->exists()
        || $this->reviews()->exists()
        || $this->tickets()->exists()
        || $this->ticketMessages()->exists()
        || $this->couponUsages()->exists();
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

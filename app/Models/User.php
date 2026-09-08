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
 * Scope: Only customers
 */
public function scopeCustomers($query)
{
    return $query->where('role', 'customer');
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
}

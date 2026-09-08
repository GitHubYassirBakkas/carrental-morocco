<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VerificationToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'type',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->token = $model->token ?? Str::random(64);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
            ->whereNull('used_at');
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    public function isUsed()
    {
        return $this->used_at !== null;
    }

    public function markAsUsed()
    {
        $this->used_at = now();
        $this->save();
    }

    public static function createForUser($user, $type, $expiresInHours = 24)
    {
        // Invalidate any existing tokens of the same type for this user
        static::where('user_id', $user->id)
            ->where('type', $type)
            ->update(['used_at' => now()]);

        return static::create([
            'user_id' => $user->id,
            'type' => $type,
            'expires_at' => now()->addHours($expiresInHours),
        ]);
    }
}

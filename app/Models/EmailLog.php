<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'to',
        'subject',
        'content',
        'template',
        'variables',
        'status',
        'error_message',
        'sent_at',
        'opened_at',
        'clicked_at',
        'message_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get body content (alias for content)
     */
    public function getBodyAttribute()
    {
        return $this->content;
    }

    /**
     * Get sent_at timestamp (use created_at)
     */
    public function getSentAtAttribute()
    {
        return $this->status === 'sent' ? $this->created_at : null;
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function markAsOpened()
    {
        $this->update([
            'opened_at' => now(),
            'status' => 'opened',
        ]);
    }

    public function markAsClicked()
    {
        $this->update([
            'clicked_at' => now(),
            'status' => 'clicked',
        ]);
    }
}

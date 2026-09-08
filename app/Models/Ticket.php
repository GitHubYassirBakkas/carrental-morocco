<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'user_id', 'ticket_number', 'subject', 
        'category', 'status', 'priority'
    ];

    // العلاقات
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at', 'asc');
    }

    public function latestMessage()
    {
        return $this->hasOne(TicketMessage::class)->latestOfMany();
    }

    // Generate ticket number تلقائي
    public static function generateTicketNumber(): string
    {
        $last = self::latest()->first();
        $number = $last ? (int) substr($last->ticket_number, 4) + 1 : 1;
        return 'TKT-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    // Status colors للـ UI
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open'        => 'blue',
            'in_progress' => 'yellow',
            'resolved'    => 'green',
            'closed'      => 'gray',
            default       => 'gray',
        };
    }

    // Priority colors
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'low'    => 'green',
            'medium' => 'yellow',
            'high'   => 'red',
            default  => 'gray',
        };
    }

    // Unread messages count للـ admin
    public function unreadCount(): int
    {
        return $this->messages()->where('is_read', false)->where('is_admin', false)->count();
    }
}
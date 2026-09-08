<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    protected $fillable = [
        'ticket_id', 'user_id', 'message', 'is_admin', 'is_read'
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'is_read'  => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
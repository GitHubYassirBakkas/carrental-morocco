<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboxEvent extends Model
{
    protected $fillable = [
        'event_type',
        'payload',
        'trace_id',
        'source_event_id',
        'dispatched',
        'attempts',
        'dispatched_at',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'dispatched' => 'boolean',
        'dispatched_at' => 'datetime',
    ];
}

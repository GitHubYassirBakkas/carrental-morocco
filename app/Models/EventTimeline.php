<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventTimeline extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'trace_id',
        'event_name',
        'source_event_id',
        'aggregate_type',
        'aggregate_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}

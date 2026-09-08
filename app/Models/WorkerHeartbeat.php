<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkerHeartbeat extends Model
{
    protected $fillable = [
        'worker_name',
        'queue',
        'current_job',
        'current_event_id',
        'heartbeat_at',
        'context',
    ];

    protected $casts = [
        'heartbeat_at' => 'datetime',
        'context' => 'array',
    ];
}

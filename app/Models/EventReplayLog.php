<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventReplayLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'event_replay_log';

    protected $fillable = [
        'event_source',
        'event_id',
        'trace_id',
        'status',
        'error_message',
    ];
}

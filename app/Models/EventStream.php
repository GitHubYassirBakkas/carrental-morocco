<?php

namespace App\Models;

use App\Models\Concerns\ProtectsHistoricalRecords;
use Illuminate\Database\Eloquent\Model;

class EventStream extends Model
{
    use ProtectsHistoricalRecords;

    public const UPDATED_AT = null;

    protected $table = 'event_stream';

    protected static function historicalRecordDeleteMessage(): string
    {
        return 'Event stream audit records cannot be deleted.';
    }

    protected $fillable = [
        'event_key',
        'event_name',
        'booking_id',
        'version',
        'trace_id',
        'correlation_id',
        'source_event_id',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}

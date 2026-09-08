<?php

namespace App\Models;

use App\Models\Concerns\ProtectsHistoricalRecords;
use Illuminate\Database\Eloquent\Model;

class BookingStateTransition extends Model
{
    use ProtectsHistoricalRecords;

    public const UPDATED_AT = null;

    protected static function historicalRecordDeleteMessage(): string
    {
        return 'Booking state transition audit records cannot be deleted.';
    }

    protected $fillable = [
        'booking_id',
        'from_status',
        'to_status',
        'source',
        'source_event_id',
        'trace_id',
        'accepted',
        'reason',
    ];

    protected $casts = [
        'accepted' => 'boolean',
    ];
}

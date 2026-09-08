<?php

namespace App\Models;

use App\Models\Concerns\ProtectsHistoricalRecords;
use Illuminate\Database\Eloquent\Model;

class BookingSaga extends Model
{
    use ProtectsHistoricalRecords;

    protected static function historicalRecordDeleteMessage(): string
    {
        return 'Booking saga records cannot be deleted.';
    }

    protected $fillable = [
        'saga_id',
        'booking_id',
        'current_step',
        'status',
        'compensation_status',
        'trace_id',
        'payload',
        'compensation_payload',
        'retry_count',
        'failure_root_cause',
        'completed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'compensation_payload' => 'array',
        'completed_at' => 'datetime',
    ];
}

<?php

namespace App\Models;

use App\Models\Concerns\ProtectsHistoricalRecords;
use Illuminate\Database\Eloquent\Model;

class FailedWebhookEvent extends Model
{
    use ProtectsHistoricalRecords;

    public const UPDATED_AT = null;

    protected static function historicalRecordDeleteMessage(): string
    {
        return 'Failed webhook audit records cannot be deleted.';
    }

    protected $fillable = [
        'event_id',
        'event_type',
        'booking_id',
        'payment_intent_id',
        'payload',
        'error_message',
        'stack_trace',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'failed_at' => 'datetime',
    ];
}

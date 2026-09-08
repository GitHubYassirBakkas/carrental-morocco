<?php

namespace App\Models;

use App\Models\Concerns\ProtectsHistoricalRecords;
use Illuminate\Database\Eloquent\Model;

class PaymentEventAudit extends Model
{
    use ProtectsHistoricalRecords;

    public const UPDATED_AT = null;

    protected static function historicalRecordDeleteMessage(): string
    {
        return 'Payment event audit records cannot be deleted.';
    }

    protected $fillable = [
        'booking_id',
        'event_id',
        'event_type',
        'payment_intent_id',
        'payload',
        'processed_at',
        'outcome',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}

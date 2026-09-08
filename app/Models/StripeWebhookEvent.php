<?php

namespace App\Models;

use App\Models\Concerns\ProtectsHistoricalRecords;
use Illuminate\Database\Eloquent\Model;

class StripeWebhookEvent extends Model
{
    use ProtectsHistoricalRecords;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    protected static function historicalRecordDeleteMessage(): string
    {
        return 'Stripe webhook event records cannot be deleted.';
    }

    protected $fillable = [
        'event_id',
        'type',
        'payload',
        'status',
        'attempts',
        'error_message',
        'processed_at',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}

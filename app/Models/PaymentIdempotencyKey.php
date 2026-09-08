<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentIdempotencyKey extends Model
{
    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'idempotency_key',
        'event_id',
        'payment_intent_id',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}

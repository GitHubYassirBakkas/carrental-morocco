<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalIdempotencyRecord extends Model
{
    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'scope',
        'idempotency_key',
        'status',
        'owner_token',
        'expires_at',
        'context',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'context' => 'array',
    ];
}

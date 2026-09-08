<?php

namespace App\Models;

use App\Models\Concerns\ProtectsHistoricalRecords;
use Illuminate\Database\Eloquent\Model;

class SagaExecutionLog extends Model
{
    use ProtectsHistoricalRecords;

    public const UPDATED_AT = null;

    protected static function historicalRecordDeleteMessage(): string
    {
        return 'Saga execution audit records cannot be deleted.';
    }

    protected $fillable = [
        'saga_id',
        'booking_id',
        'trace_id',
        'step',
        'status',
        'context',
        'failure_root_cause',
    ];

    protected $casts = [
        'context' => 'array',
    ];
}

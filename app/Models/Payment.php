<?php
namespace App\Models;

use App\Models\Concerns\ProtectsHistoricalRecords;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory, ProtectsHistoricalRecords;

public const STATUS_PENDING = 'pending';
public const STATUS_COMPLETED = 'completed';
public const STATUS_FAILED = 'failed';

public const STATUSES = [
    self::STATUS_PENDING,
    self::STATUS_COMPLETED,
    self::STATUS_FAILED,
];

public const TYPE_PAYMENT = 'payment';
public const TYPE_REFUND = 'refund';

protected static function historicalRecordDeleteMessage(): string
{
    return 'Payments and refunds are financial history and cannot be deleted.';
}

 protected $fillable = [
    'invoice_id',
    'user_id',
    'amount',
    'method',
    'type',
    'status',
    'transaction_id',
    'paid_at',
    'notes'
];

protected $casts = [
    'amount' => 'decimal:2',
    'paid_at' => 'datetime'
];

public function invoice()
{
    return $this->belongsTo(Invoice::class);
}

public function user()
{
    return $this->belongsTo(User::class);
}

}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

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

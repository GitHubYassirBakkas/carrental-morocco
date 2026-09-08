<?php

namespace App\Models;

use App\Models\Concerns\ProtectsHistoricalRecords;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingPhoto extends Model
{
    use HasFactory, ProtectsHistoricalRecords;

    protected static function historicalRecordDeleteMessage(): string
    {
        return 'Booking photos are historical inspection records and cannot be deleted.';
    }

    protected $fillable = [
        'booking_inspection_id',
        'path',
        'position', // front, back, left, right, interior...
    ];

    /* ========= RELATION ========= */

    public function inspection()
    {
        return $this->belongsTo(BookingInspection::class,  'booking_inspection_id');
    }
}

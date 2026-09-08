<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingPhoto extends Model
{
    use HasFactory;

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

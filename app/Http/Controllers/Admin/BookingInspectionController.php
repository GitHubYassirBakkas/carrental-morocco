<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingInspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BookingInspectionController extends Controller
{
   public function store(Request $request, Booking $booking)
{
    $request->validate([
        'type' => 'required|in:checkin,checkout',
        'mileage' => 'required|integer|min:0',
        'fuel_level' => 'required|integer|min:0|max:100',
        'damage_notes' => 'nullable|string',
    ]);

    if ($booking->inspections()->where('type', $request->type)->exists()) {
        return back()->with('error', 'Inspection already exists.');
    }

    $inspection = BookingInspection::create([
        'booking_id' => $booking->id,
        'type' => $request->type,
        'mileage' => $request->mileage,
        'fuel_level' => $request->fuel_level,
        'has_damage' => !empty($request->damage_notes),
        'damage_notes' => $request->damage_notes,
        'created_by' => auth()->id(),
    ]);

    /*
    |--------------------------------------------------
    | CHECK-IN LOGIC
    |--------------------------------------------------
    */
    if ($request->type === 'checkin') {

        $booking->update([
            'fuel_at_pickup_percent' => $request->fuel_level,
        ]);
    }

    /*
    |--------------------------------------------------
    | CHECK-OUT LOGIC (STRONG VERSION)
    |--------------------------------------------------
    */
    if ($request->type === 'checkout') {

        $pickupFuel = $booking->fuel_at_pickup_percent ?? 0;
        $returnFuel = $request->fuel_level;

        $fuelUsed = max(0, $pickupFuel - $returnFuel);

        // 💰 Fuel price per 1%
        $pricePerPercent = 5; // تقدر تخليه ف config

        $fuelCharge = $fuelUsed * $pricePerPercent;

        // ⏱ Late calculation
        $lateMinutes = max(
            0,
            now()->diffInMinutes($booking->end_date, false)
        );

        $lateMinutes = $lateMinutes < 0 ? abs($lateMinutes) : 0;

        $lateFee = 0;

        if ($lateMinutes > 0) {
            $lateFee = ceil($lateMinutes / 60) * $booking->daily_rate;
        }

        $booking->update([
            'fuel_at_return_percent' => $returnFuel,
            'fuel_used' => $fuelUsed,
            'fuel_charge' => $fuelCharge,
            'late_minutes' => $lateMinutes,
            'late_fee' => $lateFee,
        ]);
    }

    return back()->with('success', ucfirst($request->type).' inspection saved successfully.');
}


/**
     * Show inspection details
     */
    public function show(BookingInspection $inspection)
    {
        return view('admin.inspections.show', compact('inspection'));
    }

    public function uploadPhotos(Request $request, BookingInspection $inspection)
    {
        $request->validate([
            'photos.*' => 'required|image|max:10240',
            'type' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        foreach ($request->file('photos') as $photo) {

            $path = $photo->store('inspections', 'public');

            $inspection->photos()->create([
                'path'  => $path,
                'type'  => $request->type,
                'notes' => $request->notes,
            ]);
        }

        return back()->with('success', 'Photos uploaded successfully');
    }
}

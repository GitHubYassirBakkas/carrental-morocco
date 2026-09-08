<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingInspection;
use App\Services\Pricing\BookingPricingService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BookingInspectionController extends Controller
{
    public function __construct(private readonly BookingPricingService $pricingService) {}

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

        BookingInspection::create([
            'booking_id' => $booking->id,
            'type' => $request->type,
            'mileage' => $request->mileage,
            'fuel_level' => $request->fuel_level,
            'has_damage' => ! empty($request->damage_notes),
            'damage_notes' => $request->damage_notes,
            'created_by' => auth()->id(),
        ]);

        if ($request->type === 'checkin') {
            $booking->update([
                'fuel_at_pickup_percent' => $request->fuel_level,
            ]);
        }

        if ($request->type === 'checkout') {
            $returnFuel = $request->fuel_level;
            $charges = $this->pricingService->calculateCheckoutCharges($booking, $returnFuel);

            $booking->update([
                'fuel_at_return_percent' => $returnFuel,
                'fuel_used' => $charges['fuel_used'],
                'fuel_charge' => $charges['fuel_charge'],
                'late_minutes' => $charges['late_minutes'],
                'late_fee' => $charges['late_fee'],
            ]);
        }

        return back()->with('success', ucfirst($request->type).' inspection saved successfully.');
    }

    public function show(BookingInspection $inspection)
    {
        return view('admin.inspections.show', compact('inspection'));
    }

    public function uploadPhotos(Request $request, BookingInspection $inspection)
    {
        $request->validate([
            'photos' => 'required|array|max:10',
            'photos.*' => 'required|file|image|mimes:jpeg,jpg,png,webp|mimetypes:image/jpeg,image/png,image/webp|max:5120',
            'type' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        foreach ($request->file('photos') as $photo) {
            $path = $this->storeInspectionPhoto($photo);

            $inspection->photos()->create([
                'path' => $path,
                'type' => $request->type,
                'notes' => $request->notes,
            ]);
        }

        return back()->with('success', 'Photos uploaded successfully');
    }

    private function storeInspectionPhoto(UploadedFile $photo): string
    {
        $extension = strtolower($photo->extension() ?: 'jpg');
        $filename = Str::uuid()->toString().'.'.$extension;
        $path = Storage::disk('local')->putFileAs('booking-evidence/inspections', $photo, $filename);

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Inspection photo could not be stored.');
        }

        return $path;
    }
}

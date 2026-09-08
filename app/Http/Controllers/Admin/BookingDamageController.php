<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Pricing\BookingPricingService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BookingDamageController extends Controller
{
    public function __construct(private readonly BookingPricingService $pricingService) {}

    public function store(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'stage' => 'required|in:checkin,checkout',
            'part' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'required|string',
            'estimated_cost' => 'required|numeric|min:0',
        ]);

        if ($request->hasFile('photos')) {
            $request->validate([
                'photos' => 'array|max:10',
                'photos.*' => 'file|image|mimes:jpeg,jpg,png,webp|mimetypes:image/jpeg,image/png,image/webp|max:5120',
            ]);
        }

        $data['is_chargeable'] = $request->boolean('is_chargeable');
        $photoPaths = [];

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                try {
                    $photoPaths[] = $this->storeDamagePhoto($photo);
                } catch (\Throwable $e) {
                    Log::error('Damage photo upload failed', [
                        'booking_id' => $booking->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        $data['photos'] = count($photoPaths) > 0 ? $photoPaths : null;

        $booking->damages()->create($data);

        if ($data['stage'] === 'checkout' && $data['is_chargeable'] && $booking->invoice) {
            $this->updateInvoiceWithDamages($booking);
        }

        $message = ucfirst($data['stage']).' damage recorded successfully';
        if ($data['photos']) {
            $message .= ' with '.count($data['photos']).' photo(s)';
        }

        return redirect()->back()->with('success', $message);
    }

    private function updateInvoiceWithDamages(Booking $booking): void
    {
        $invoice = $booking->invoice;
        $invoiceTotals = $this->pricingService->calculateInvoiceTotalsForBooking($booking);

        $invoice->update([
            'subtotal' => $invoiceTotals['subtotal_amount'],
            'tax_amount' => $invoiceTotals['tax_amount'],
            'total_amount' => $invoiceTotals['total_amount'],
        ]);
    }

    private function storeDamagePhoto(UploadedFile $photo): string
    {
        $extension = strtolower($photo->extension() ?: 'jpg');
        $filename = Str::uuid()->toString().'.'.$extension;
        $path = Storage::disk('local')->putFileAs('booking-evidence/damages', $photo, $filename);

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Damage photo could not be stored.');
        }

        return $path;
    }
}

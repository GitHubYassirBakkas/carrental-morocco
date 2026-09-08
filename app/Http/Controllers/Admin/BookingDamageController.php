<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Pricing\BookingPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingDamageController extends Controller
{
    public function __construct(private readonly BookingPricingService $pricingService)
    {
    }

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
                'photos' => 'array',
                'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            ]);
        }

        $data['is_chargeable'] = $request->boolean('is_chargeable');
        $photoPaths = [];

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                try {
                    $filename = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                    $photoPaths[] = $photo->storeAs('damages', $filename, 'public');
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

        $message = ucfirst($data['stage']) . ' damage recorded successfully';
        if ($data['photos']) {
            $message .= ' with ' . count($data['photos']) . ' photo(s)';
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
}

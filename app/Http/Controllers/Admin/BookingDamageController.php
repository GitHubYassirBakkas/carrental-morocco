<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDamage;
use Illuminate\Http\Request;

class BookingDamageController extends Controller
{
    /**
     * Store new damage record
     */
   
  public function store(Request $request, Booking $booking)
{

  
    // Basic validation (without photos)
    $data = $request->validate([
        'stage' => 'required|in:checkin,checkout',
        'part' => 'required|string|max:255',
        'type' => 'required|string|max:255',
        'description' => 'required|string',
        'estimated_cost' => 'required|numeric|min:0',
    ]);

    // Separate photo validation
    if ($request->hasFile('photos')) {
        $request->validate([
            'photos' => 'array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);
    }

    // Handle checkbox
    $data['is_chargeable'] = $request->has('is_chargeable') && $request->is_chargeable == '1';

    // Handle photo uploads
    $photoPaths = [];
    
    if ($request->hasFile('photos')) {
        foreach ($request->file('photos') as $photo) {
            try {
                $filename = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                $path = $photo->storeAs('damages', $filename, 'public');
                $photoPaths[] = $path;
                
                \Log::info('📸 Photo uploaded', ['path' => $path]);
                
            } catch (\Exception $e) {
                \Log::error('❌ Photo upload failed', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    $data['photos'] = count($photoPaths) > 0 ? $photoPaths : null;

    // Create damage
    $damage = $booking->damages()->create($data);

    // Update invoice
    if ($data['stage'] === 'checkout' && $data['is_chargeable'] && $booking->invoice) {
        $this->updateInvoiceWithDamages($booking);
    }

    $message = ucfirst($data['stage']) . ' damage recorded successfully';
    if ($data['photos']) {
        $message .= ' with ' . count($data['photos']) . ' photo(s)';
    }

    return redirect()->back()->with('success', $message);
}
    /**
     * Update invoice total when damages added
     */
    private function updateInvoiceWithDamages(Booking $booking)
    {
        $invoice = $booking->invoice;
        
        // 1️⃣ Get total chargeable damages
        $damagesCost = $booking->checkoutDamages()
            ->where('is_chargeable', true)
            ->sum('estimated_cost');
        
        // 2️⃣ Get settings
        $taxPercentage = setting('tax_percentage', 0);  // Default 0%
        $rentalAmount = $booking->total_amount;
        
        // 3️⃣ Calculate new totals
        $subtotalBeforeTax = $rentalAmount + $damagesCost;
        
        // 4️⃣ Calculate tax (if enabled)
        if ($taxPercentage > 0) {
            $taxMultiplier = 1 + ($taxPercentage / 100);
            $totalAmount = $subtotalBeforeTax * $taxMultiplier;
            $subtotal = $totalAmount / $taxMultiplier;
            $taxAmount = $totalAmount - $subtotal;
        } else {
            // No tax
            $totalAmount = $subtotalBeforeTax;
            $subtotal = $subtotalBeforeTax;
            $taxAmount = 0;
        }
        
        // 5️⃣ Update invoice in database
        $invoice->update([
            'subtotal' => round($subtotal, 2),
            'tax_amount' => round($taxAmount, 2),
            'total_amount' => round($totalAmount, 2),
        ]);
        
        // 6️⃣ Log for debugging
        \Log::info('Invoice updated with damages', [
            'invoice_id' => $invoice->id,
            'rental_amount' => $rentalAmount,
            'damages_cost' => $damagesCost,
            'tax_percentage' => $taxPercentage,
            'old_total' => $invoice->getOriginal('total_amount'),
            'new_total' => $totalAmount,
        ]);
    }
}
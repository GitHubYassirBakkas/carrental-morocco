<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Pricing\BookingPricingService;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    public function __construct(private readonly BookingPricingService $pricingService)
    {
    }

    public function createForConfirmedBooking(Booking $booking): Invoice
    {
        $invoiceTotals = $this->pricingService->calculateInvoiceTotalsForBooking($booking);

        return Invoice::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'subtotal' => $invoiceTotals['subtotal_amount'],
            'tax_amount' => $invoiceTotals['tax_amount'],
            'total_amount' => $invoiceTotals['total_amount'],
            'status' => Invoice::STATUS_PENDING,
            'issued_at' => now(),
            'due_date' => $booking->start_date ?? now()->addDays(7),
        ]);
    }

    public function firstOrCreateForPaymentPage(Booking $booking): Invoice
    {
        return $booking->invoice ?? Invoice::create([
            'booking_id' => $booking->id,
            'subtotal' => $booking->total_amount,
            'tax_amount' => 0,
            'total_amount' => $booking->total_amount,
            'status' => Invoice::STATUS_PENDING,
        ]);
    }

    public function firstOrCreateForPaymentProcessing(Booking $booking): Invoice
    {
        return $booking->invoice ?? Invoice::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'subtotal' => $booking->total_amount,
            'tax_amount' => 0,
            'total_amount' => $booking->total_amount,
            'status' => Invoice::STATUS_PENDING,
            'issued_at' => now(),
        ]);
    }

    public function createForCustomerBooking(
        Booking $booking,
        ?int $userId,
        float $subtotal,
        float $discountAmount,
        float $totalAmount
    ): Invoice {
        return Invoice::create([
            'booking_id' => $booking->id,
            'user_id' => $userId,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'status' => Invoice::STATUS_PENDING,
        ]);
    }

    public function syncPaymentStatus(Invoice $invoice): void
    {
        $invoice->refresh();

        if ($invoice->status === Invoice::STATUS_CANCELLED) {
            return;
        }

        $paidAmount = (float) $invoice->paid_amount;
        $totalAmount = (float) $invoice->total_amount;
        $newStatus = $this->calculateInvoiceStatus($paidAmount, $totalAmount);

        if ($invoice->status !== $newStatus) {
            $oldStatus = $invoice->status;
            $invoice->update(['status' => $newStatus]);

            Log::info('Invoice status updated', [
                'invoice_id' => $invoice->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'paid_amount' => $paidAmount,
                'total_amount' => $totalAmount,
            ]);
        }
    }

    public function syncRefundStatus(Invoice $invoice): void
    {
        $paidAmount = $this->calculatePaidAmount($invoice);

        $invoice->status = $paidAmount <= 0
            ? Invoice::STATUS_REFUNDED
            : $this->calculateInvoiceStatus((float) $paidAmount, (float) $invoice->total_amount);

        $invoice->save();
    }

    public function calculatePaidAmount(Invoice $invoice): float
    {
        $payments = $invoice->payments()
            ->where('type', Payment::TYPE_PAYMENT)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        $refunds = $invoice->payments()
            ->where('type', Payment::TYPE_REFUND)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        return $this->pricingService->calculatePaidAmount((float) $payments, (float) $refunds);
    }

    public function calculateInvoiceStatus(float $paidAmount, float $totalAmount): string
    {
        return $this->pricingService->calculateInvoiceStatus($paidAmount, $totalAmount);
    }
}

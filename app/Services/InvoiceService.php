<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Pricing\BookingPricingService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    public function __construct(private readonly BookingPricingService $pricingService) {}

    public function createForConfirmedBooking(Booking $booking): Invoice
    {
        return $this->firstOrCreateForBooking($booking, function () use ($booking): array {
            $invoiceTotals = $this->pricingService->calculateInvoiceTotalsForBooking($booking);

            return [
                'user_id' => $booking->user_id,
                'subtotal' => $invoiceTotals['subtotal_amount'],
                'tax_amount' => $invoiceTotals['tax_amount'],
                'total_amount' => $invoiceTotals['total_amount'],
                'status' => Invoice::STATUS_PENDING,
                'issued_at' => now(),
                'due_date' => $booking->start_date ?? now()->addDays(7),
            ];
        });
    }

    public function firstOrCreateForPaymentPage(Booking $booking): Invoice
    {
        return $this->firstOrCreateForBooking($booking, fn (): array => $this->fallbackInvoiceAttributes($booking));
    }

    public function firstOrCreateForPaymentProcessing(Booking $booking): Invoice
    {
        return $this->firstOrCreateForBooking(
            $booking,
            fn (): array => array_merge($this->fallbackInvoiceAttributes($booking), [
                'issued_at' => now(),
            ])
        );
    }

    public function createForCustomerBooking(
        Booking $booking,
        ?int $userId,
        float $subtotal,
        float $discountAmount,
        float $totalAmount,
        float $taxAmount = 0
    ): Invoice {
        return $this->firstOrCreateForBooking($booking, fn (): array => [
            'user_id' => $booking->user_id,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
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

    /**
     * Create the booking invoice once and safely reuse it if a concurrent request won the race.
     */
    private function firstOrCreateForBooking(Booking $booking, callable $attributesResolver): Invoice
    {
        $existingInvoice = Invoice::where('booking_id', $booking->id)->first();

        if ($existingInvoice) {
            return $existingInvoice;
        }

        try {
            return $this->createInvoiceRecord(array_merge(
                $attributesResolver(),
                ['booking_id' => $booking->id],
            ));
        } catch (QueryException $exception) {
            if (! $this->isDuplicateBookingInvoiceConstraint($exception)) {
                throw $exception;
            }

            $invoice = Invoice::where('booking_id', $booking->id)->first();

            if (! $invoice) {
                throw $exception;
            }

            return $invoice;
        }
    }

    private function isDuplicateBookingInvoiceConstraint(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (string) ($exception->errorInfo[1] ?? '');
        $message = strtolower($exception->getMessage());

        if ($driverCode === '1062' && str_contains($message, 'invoices_booking_id_unique')) {
            return true;
        }

        if ($sqlState === '23505' && str_contains($message, 'invoices_booking_id_unique')) {
            return true;
        }

        return in_array($driverCode, ['19', '2067'], true)
            && str_contains($message, 'unique constraint failed')
            && str_contains($message, 'invoices.booking_id');
    }

    protected function createInvoiceRecord(array $attributes): Invoice
    {
        return Invoice::create($attributes);
    }

    private function fallbackInvoiceAttributes(Booking $booking): array
    {
        $breakdown = $this->pricingService->breakdownForBooking($booking);

        return [
            'user_id' => $booking->user_id,
            'subtotal' => $breakdown['subtotal_amount'],
            'discount_amount' => $breakdown['discount_amount'],
            'tax_amount' => $breakdown['tax_amount'],
            'total_amount' => $breakdown['total_amount'],
            'status' => Invoice::STATUS_PENDING,
        ];
    }
}

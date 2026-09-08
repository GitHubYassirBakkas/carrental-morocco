<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentService
{
    /**
     * Record a payment for an invoice
     *
     * @param Invoice $invoice
     * @param float $amount
     * @param string $method
     * @param string|null $notes
     * @return Payment
     * @throws Exception
     */
    public function recordPayment(
        Invoice $invoice,
        float $amount,
        string $method,
        int $userId,
        ?string $notes = null
    ): Payment {
        return DB::transaction(function () use ($invoice, $amount, $method, $userId, $notes) {
            // Lock invoice to prevent race conditions
            $invoice = Invoice::where('id', $invoice->id)
                ->lockForUpdate()
                ->first();

            // Validate amount
            if ($amount <= 0) {
                throw new Exception('Payment amount must be greater than zero');
            }

            $balance = $invoice->total_amount - $invoice->paid_amount;
            if ($amount > $balance) {
                throw new Exception("Payment amount ({$amount} MAD) exceeds balance ({$balance} MAD)");
            }

            // Create payment record
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'user_id' => $userId,
                'amount' => $amount,
                'method' => $method,
                'type' => 'payment',
                'status' => 'completed',
                'transaction_id' => 'PAY-' . strtoupper(uniqid()),
                'paid_at' => now(),
                'notes' => $notes
            ]);

            // Update invoice status
            $this->updateInvoiceStatus($invoice);
 // 🎯 FIX #2: Check deposit inside transaction
            $booking = $invoice->booking;
            if ($booking && $booking->status === 'pending') {
                $newPaidAmount = $paidAmount + $amount;
                $this->depositService->confirmBookingIfDepositReached(
                    $booking,
                    $newPaidAmount
                );
            }
            
            Log::info('Payment recorded', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'method' => $method
            ]);

            return $payment;
        });
    }

    /**
     * Automatically update invoice status based on paid amount
     *
     * @param Invoice $invoice
     * @return void
     */
    public function updateInvoiceStatus(Invoice $invoice): void
    {
        // Refresh to get latest payments
        $invoice->refresh();
        
        $paidAmount = $invoice->paid_amount;
        $totalAmount = $invoice->total_amount;

        $newStatus = $this->calculateInvoiceStatus($paidAmount, $totalAmount);

        if ($invoice->status !== $newStatus) {
            $invoice->update(['status' => $newStatus]);
            
            Log::info('Invoice status updated', [
                'invoice_id' => $invoice->id,
                'old_status' => $invoice->status,
                'new_status' => $newStatus,
                'paid_amount' => $paidAmount,
                'total_amount' => $totalAmount
            ]);
        }
    }

    /**
     * Calculate invoice status based on amounts
     *
     * @param float $paidAmount
     * @param float $totalAmount
     * @return string
     */
    public function calculateInvoiceStatus(float $paidAmount, float $totalAmount): string
    {
        if ($paidAmount >= $totalAmount) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return 'partial';
        }

        return 'unpaid';
    }

    /**
     * Generate unique transaction ID
     *
     * @param string $prefix
     * @return string
     */
    private function generateTransactionId(string $prefix): string
    {
        return $prefix . '-' . strtoupper(uniqid());
    }
}
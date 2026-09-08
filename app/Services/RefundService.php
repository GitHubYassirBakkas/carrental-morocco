<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Exception;

class RefundService
{
    public function processRefund(Invoice $invoice, float $amount, ?string $reason = null): Payment
    {
        if ($amount <= 0) {
            throw new Exception('Refund amount must be greater than zero');
        }

        $paidAmount = $this->calculatePaidAmount($invoice);

        if ($amount > $paidAmount) {
            throw new Exception('Refund exceeds refundable balance');
        }

        return DB::transaction(function () use ($invoice, $amount, $reason) {

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
                'amount' => $amount,
                'method' => 'bank_transfer',
                'type' => 'refund',
                'status' => 'completed',
                'transaction_id' => 'REF-' . strtoupper(uniqid()),
                'paid_at' => now(),
                'notes' => $reason ?? 'Refund processed'
            ]);

            $this->updateInvoiceStatus($invoice);

            return $payment;
        });
    }

    public function calculatePaidAmount(Invoice $invoice): float
    {
        $payments = $invoice->payments()
            ->where('type', 'payment')
            ->where('status', 'completed')
            ->sum('amount');

        $refunds = $invoice->payments()
            ->where('type', 'refund')
            ->where('status', 'completed')
            ->sum('amount');

        return $payments - $refunds;
    }

    private function updateInvoiceStatus(Invoice $invoice): void
    {
        $paidAmount = $this->calculatePaidAmount($invoice);

        if ($paidAmount <= 0) {
            $invoice->status = 'refunded';
        } elseif ($paidAmount < $invoice->total_amount) {
            $invoice->status = 'partial';
        } else {
            $invoice->status = 'paid';
        }

        $invoice->save();
    }
}
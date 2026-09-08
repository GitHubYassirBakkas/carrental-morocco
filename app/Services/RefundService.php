<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Exception;

class RefundService
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly PaymentService $paymentService
    )
    {
    }

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

            return $this->paymentService->recordRefund($invoice, $amount, $reason);
        });
    }

    public function calculatePaidAmount(Invoice $invoice): float
    {
        return $this->invoiceService->calculatePaidAmount($invoice);
    }
}

<?php

namespace App\Services;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use InvalidArgumentException;

class RefundReceiptService
{
    /**
     * Generate refund receipt PDF
     *
     * @param  Payment  $payment  The refund payment record
     * @return \Barryvdh\DomPDF\Dompdf
     */
    public function generateRefundReceipt(Payment $payment)
    {
        if ($payment->type !== Payment::TYPE_REFUND) {
            throw new InvalidArgumentException('Payment is not a refund.');
        }

        $invoice = $payment->invoice;
        $booking = $invoice->booking;

        // Calculate refund type
        $originalAmount = $invoice->payments()
            ->where('type', Payment::TYPE_PAYMENT)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        $refundType = $this->calculateRefundType($payment->amount, $originalAmount);

        return Pdf::loadView('admin.bookings.refund-receipt', compact(
            'payment',
            'invoice',
            'booking',
            'refundType',
            'originalAmount'
        ));
    }

    /**
     * Calculate refund type based on refund amount and original amount
     *
     * @return string 'full', 'partial', or 'none'
     */
    private function calculateRefundType(float $refundAmount, float $originalAmount): string
    {
        if ($refundAmount >= $originalAmount) {
            return 'full';
        } elseif ($refundAmount <= 0) {
            return 'none';
        }

        return 'partial';
    }

    /**
     * Generate refund receipt PDF and return it for download
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadRefundReceipt(Payment $payment)
    {
        $pdf = $this->generateRefundReceipt($payment);

        return $pdf->download('refund-receipt-'.$payment->transaction_id.'.pdf');
    }

    /**
     * Generate refund receipt PDF and return it as a file path
     * Useful for email attachments
     *
     * @return string The file path to the generated PDF
     *
     * @throws \RuntimeException If PDF file cannot be written
     */
    public function generateRefundReceiptForEmail(Payment $payment): string
    {
        $pdf = $this->generateRefundReceipt($payment);

        $booking = $payment->invoice->booking;
        $filename = 'refund-receipt-booking-'.$booking->id.'.pdf';
        $filePath = storage_path('app/temp/'.$filename);

        // Ensure directory exists
        if (! file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $result = file_put_contents($filePath, $pdf->output());

        if ($result === false) {
            throw new \RuntimeException('Unable to write temporary refund receipt PDF.');
        }

        return $filePath;
    }
}

<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class CashRefundProcessedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $pdfContents;

    public function __construct(
        public Payment $payment,
        public string $refundType,
        public float $originalAmount,
        public string $pdfPath
    ) {
        $pdfContents = file_get_contents($pdfPath);

        if ($pdfContents === false) {
            throw new RuntimeException('Unable to read refund receipt PDF for email attachment.');
        }

        $this->pdfContents = $pdfContents;
    }

    public function envelope(): Envelope
    {
        $booking = $this->payment->invoice->booking;

        return new Envelope(
            subject: '💰 Refund Processed - Booking #'.$booking->id,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.cash-refund-processed');
    }

    public function attachments(): array
    {
        $booking = $this->payment->invoice->booking;
        $filename = 'refund-receipt-booking-'.$booking->id.'.pdf';

        return [
            Attachment::fromData(fn () => $this->pdfContents, $filename)
                ->withMime('application/pdf'),
        ];
    }
}

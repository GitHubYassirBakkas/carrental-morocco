<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StripeRefundProcessedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public string $refundType,
        public float $originalAmount
    ) {}

    public function envelope(): Envelope
    {
        $booking = $this->payment->invoice->booking;

        return new Envelope(
            subject: '💰 Refund Processed - Booking #'.$booking->id,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.stripe-refund-processed');
    }

    public function attachments(): array
    {
        return [];
    }
}

<?php

namespace App\Mail;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CouponRewardMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public $coupon;

    public $milestone;

    public function __construct(User $user, Coupon $coupon, int $milestone = 10)
    {
        $this->user = $user;
        $this->coupon = $coupon;
        $this->milestone = $milestone;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You've completed {$this->milestone} rentals - enjoy {$this->coupon->discount_display} off your next booking.",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.coupon-reward',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

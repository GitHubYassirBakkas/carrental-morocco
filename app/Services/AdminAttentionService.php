<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\Review;
use App\Models\TicketMessage;

class AdminAttentionService
{
    public function counts(): array
    {
        return [
            'bookings' => $this->pendingBookingsCount(),
            'support' => $this->unreadCustomerSupportMessagesCount(),
            'invoices' => $this->invoiceAttentionCount(),
            'users' => $this->pendingDriverVerificationCount(),
            'reviews' => $this->pendingReviewCount(),
        ];
    }

    public function total(): int
    {
        return array_sum($this->counts());
    }

    public function pendingBookingsCount(): int
    {
        return Booking::where('status', Booking::STATUS_PENDING)->count();
    }

    public function unreadCustomerSupportMessagesCount(): int
    {
        return TicketMessage::where('is_admin', false)
            ->where('is_read', false)
            ->whereHas('ticket', function ($query): void {
                $query->whereIn('status', ['open', 'in_progress']);
            })
            ->count();
    }

    public function invoiceAttentionCount(): int
    {
        return Invoice::whereIn('status', [
            Invoice::STATUS_PENDING,
            Invoice::STATUS_PARTIAL,
        ])->count();
    }

    public function pendingDriverVerificationCount(): int
    {
        return CustomerProfile::where('driver_verification_status', CustomerProfile::STATUS_PENDING)->count();
    }

    public function pendingReviewCount(): int
    {
        return Review::pending()->count();
    }
}

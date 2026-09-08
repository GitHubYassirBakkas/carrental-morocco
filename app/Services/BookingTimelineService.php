<?php

namespace App\Services;

use App\Models\Booking;
use App\ViewModels\BookingTimelineViewModel;
use App\ViewModels\TimelineEventViewModel;

class BookingTimelineService
{
    public function __construct(
        private readonly RefundService $refundService
    ) {}

    /**
     * Build timeline for booking details (customer-facing)
     */
    public function buildTimeline(Booking $booking): BookingTimelineViewModel
    {
        // Ensure required relationships are loaded to avoid N+1 queries
        $booking->loadMissing(['invoice.payments']);

        $events = [];

        // Booking Created - always exists
        $events[] = new TimelineEventViewModel(
            title: 'Booking Created',
            description: 'Booking request submitted.',
            date: $booking->created_at,
            status: 'completed',
            icon: 'calendar',
            completed: true
        );

        // Payment Completed - if payment exists
        if ($booking->invoice && $booking->invoice->payments->isNotEmpty()) {
            $payment = $booking->invoice->payments
                ->first(fn ($p) => $p->type === 'payment' && $p->status === 'completed');

            if ($payment) {
                $paymentMethod = $this->formatPaymentMethod($payment->method);
                $paymentDate = $payment->paid_at ?? $payment->created_at;
                $events[] = new TimelineEventViewModel(
                    title: 'Payment Completed',
                    description: "Payment method: {$paymentMethod}",
                    date: $paymentDate,
                    status: 'completed',
                    icon: 'credit-card',
                    completed: true
                );
            }
        }

        // Confirmed - if booking is confirmed
        // Note: No dedicated confirmed_at field exists, so we omit this event
        // rather than using an inaccurate timestamp like updated_at
        if ($booking->status === 'confirmed' || $booking->status === 'active' || $booking->status === 'completed') {
            // Only show if we have a real confirmation timestamp
            // Since confirmed_at doesn't exist, we skip this event
            // to avoid showing inaccurate timing information
        }

        // Cancelled - only if cancelled
        // Note: No dedicated cancelled_at field exists, so we omit this event
        // rather than using an inaccurate timestamp like updated_at
        if ($booking->status === 'cancelled') {
            // Skip this event as we don't have a real cancellation timestamp
            // updated_at could be from any status change, not specifically cancellation
        }

        // Refunded - only if refund exists (uses existing RefundService)
        // Use actual refund payment timestamp, not booking.updated_at
        $refundStatus = $this->refundService->getRefundStatus($booking);
        if ($refundStatus === 'refunded' || $refundStatus === 'partial') {
            if ($booking->invoice && $booking->invoice->payments->isNotEmpty()) {
                $refundPayment = $booking->invoice->payments
                    ->first(fn ($p) => $p->type === 'refund' && $p->status === 'completed');

                if ($refundPayment) {
                    $refundDate = $refundPayment->paid_at ?? $refundPayment->created_at;
                    $events[] = new TimelineEventViewModel(
                        title: $refundStatus === 'refunded' ? 'Refunded' : 'Partial Refund',
                        description: $refundStatus === 'refunded'
                            ? 'Full refund processed.'
                            : 'Partial refund processed.',
                        date: $refundDate,
                        status: 'completed',
                        icon: 'arrow-circle-left',
                        completed: true
                    );
                }
            }
        }

        // Sort events chronologically by date
        // For events with the same timestamp, sort by title to ensure deterministic ordering
        usort($events, function ($a, $b) {
            if ($a->date->eq($b->date)) {
                return strcmp($a->title, $b->title);
            }

            return $a->date->lt($b->date) ? -1 : 1;
        });

        return new BookingTimelineViewModel($events);
    }

    /**
     * Build admin timeline with complete operational visibility
     */
    public function buildAdminTimeline(Booking $booking): BookingTimelineViewModel
    {
        // Ensure required relationships are loaded to avoid N+1 queries
        $booking->loadMissing([
            'invoice.payments',
            'checkinInspection',
            'checkoutInspection',
        ]);

        $events = [];

        // Booking Created - always exists
        $events[] = new TimelineEventViewModel(
            title: 'Booking Created',
            description: 'Booking request submitted.',
            date: $booking->created_at,
            status: 'completed',
            icon: 'calendar',
            completed: true
        );

        // Payment Completed - if payment exists
        if ($booking->invoice && $booking->invoice->payments->isNotEmpty()) {
            $payment = $booking->invoice->payments
                ->first(fn ($p) => $p->type === 'payment' && $p->status === 'completed');

            if ($payment) {
                $paymentMethod = $this->formatPaymentMethod($payment->method);
                $paymentDate = $payment->paid_at ?? $payment->created_at;
                $events[] = new TimelineEventViewModel(
                    title: 'Payment Completed',
                    description: "Payment method: {$paymentMethod}",
                    date: $paymentDate,
                    status: 'completed',
                    icon: 'credit-card',
                    completed: true
                );
            }
        }

        // Confirmed - if booking is confirmed
        // Note: No dedicated confirmed_at field exists, so we omit this event
        // rather than using an inaccurate timestamp like updated_at
        if ($booking->status === 'confirmed' || $booking->status === 'active' || $booking->status === 'completed') {
            // Only show if we have a real confirmation timestamp
            // Since confirmed_at doesn't exist, we skip this event
            // to avoid showing inaccurate timing information
        }

        // Rental Started - if booking is active or completed
        if ($booking->status === 'active' || $booking->status === 'completed') {
            // Only use started_at if it exists (actual start timestamp)
            // Do NOT use start_date (scheduled date) as it's not the actual event timestamp
            if ($booking->started_at) {
                $events[] = new TimelineEventViewModel(
                    title: 'Rental Started',
                    description: 'Rental period has begun.',
                    date: $booking->started_at,
                    status: 'completed',
                    icon: 'play-circle',
                    completed: true
                );
            }
        }

        // Rental Completed - if booking is completed
        if ($booking->status === 'completed') {
            // Only use completed_at if it exists (actual completion timestamp)
            // Do NOT use end_date (scheduled date) as it's not the actual event timestamp
            if ($booking->completed_at) {
                $events[] = new TimelineEventViewModel(
                    title: 'Rental Completed',
                    description: 'Rental period has ended.',
                    date: $booking->completed_at,
                    status: 'completed',
                    icon: 'stop-circle',
                    completed: true
                );
            }
        }

        // Cancelled - only if cancelled
        // Note: No dedicated cancelled_at field exists, so we omit this event
        // rather than using an inaccurate timestamp like updated_at
        if ($booking->status === 'cancelled') {
            // Skip this event as we don't have a real cancellation timestamp
            // updated_at could be from any status change, not specifically cancellation
        }

        // Refunded - only if refund exists (uses existing RefundService)
        // Use actual refund payment timestamp, not booking.updated_at
        $refundStatus = $this->refundService->getRefundStatus($booking);
        if ($refundStatus === 'refunded' || $refundStatus === 'partial') {
            if ($booking->invoice && $booking->invoice->payments->isNotEmpty()) {
                $refundPayment = $booking->invoice->payments
                    ->first(fn ($p) => $p->type === 'refund' && $p->status === 'completed');

                if ($refundPayment) {
                    $refundDate = $refundPayment->paid_at ?? $refundPayment->created_at;
                    $events[] = new TimelineEventViewModel(
                        title: $refundStatus === 'refunded' ? 'Refunded' : 'Partial Refund',
                        description: $refundStatus === 'refunded'
                            ? 'Full refund processed.'
                            : 'Partial refund processed.',
                        date: $refundDate,
                        status: 'completed',
                        icon: 'arrow-circle-left',
                        completed: true
                    );
                }
            }
        }

        // Security Deposit Held - if intent exists and capturable amount > 0
        if ($booking->security_deposit_intent_id && $booking->security_deposit_capturable_amount > 0) {
            $events[] = new TimelineEventViewModel(
                title: 'Security Deposit Held',
                description: 'Security deposit authorization held.',
                date: $booking->created_at,
                status: 'completed',
                icon: 'shield',
                completed: true
            );
        }

        // Security Deposit Captured - if captured
        if ($booking->security_deposit_captured_at) {
            $events[] = new TimelineEventViewModel(
                title: 'Security Deposit Captured',
                description: 'Security deposit charged.',
                date: $booking->security_deposit_captured_at,
                status: 'completed',
                icon: 'shield-check',
                completed: true
            );
        }

        // Security Deposit Refunded - if refunded
        if ($booking->security_deposit_refunded_at || $booking->security_deposit_released_at) {
            $refundDate = $booking->security_deposit_refunded_at ?? $booking->security_deposit_released_at;
            $events[] = new TimelineEventViewModel(
                title: 'Security Deposit Refunded',
                description: 'Security deposit released.',
                date: $refundDate,
                status: 'completed',
                icon: 'shield-check',
                completed: true
            );
        }

        // Check-in Inspection - if exists
        if ($booking->checkinInspection) {
            $events[] = new TimelineEventViewModel(
                title: 'Check-in Inspection',
                description: 'Vehicle inspection at pickup.',
                date: $booking->checkinInspection->created_at,
                status: 'completed',
                icon: 'clipboard-check',
                completed: true
            );
        }

        // Check-out Inspection - if exists
        if ($booking->checkoutInspection) {
            $events[] = new TimelineEventViewModel(
                title: 'Check-out Inspection',
                description: 'Vehicle inspection at return.',
                date: $booking->checkoutInspection->created_at,
                status: 'completed',
                icon: 'clipboard-check',
                completed: true
            );
        }

        // Sort events chronologically by date
        // For events with the same timestamp, sort by title to ensure deterministic ordering
        usort($events, function ($a, $b) {
            if ($a->date->eq($b->date)) {
                return strcmp($a->title, $b->title);
            }

            return $a->date->lt($b->date) ? -1 : 1;
        });

        return new BookingTimelineViewModel($events);
    }

    /**
     * Format payment method for display
     */
    private function formatPaymentMethod(string $method): string
    {
        return match ($method) {
            'cash' => 'Cash',
            'stripe' => 'Stripe',
            'card' => 'Card',
            default => ucfirst($method),
        };
    }
}

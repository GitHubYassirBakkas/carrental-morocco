<?php

namespace App\Domain\Booking;

use App\Models\Booking;
use App\Models\BookingStateTransition;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BookingStateMachine
{
    private const TRANSITIONS = [
        Booking::STATUS_PENDING => [Booking::STATUS_CONFIRMED],
        Booking::STATUS_CONFIRMED => [
            Booking::STATUS_ACTIVE,
            Booking::STATUS_COMPLETED,
            Booking::STATUS_CANCELLED,
            Booking::STATUS_CANCELED_ALIAS,
        ],
        Booking::STATUS_ACTIVE => [Booking::STATUS_COMPLETED],
        Booking::STATUS_COMPLETED => [],
        Booking::STATUS_CANCELLED => [],
        Booking::STATUS_CANCELED_ALIAS => [],
    ];

    public function transition(Booking $booking, string $to, array $context = []): bool
    {
        $booking->refresh();
        $from = $booking->status;
        $to = $this->normalizeStatus($to);
        $sourceEventId = $context['source_event_id'] ?? $context['event_id'] ?? null;
        $traceId = $context['trace_id'] ?? null;

        if ($from === $to) {
            Log::info('booking.state_transition.idempotent', [
                'booking_id' => $booking->id,
                'from_status' => $from,
                'to_status' => $to,
                'trace_id' => $traceId,
                'source_event_id' => $sourceEventId,
            ]);

            return false;
        }

        if ($sourceEventId && BookingStateTransition::where('booking_id', $booking->id)
            ->where('source_event_id', $sourceEventId)
            ->where('to_status', $to)
            ->where('accepted', true)
            ->exists()) {
            return false;
        }

        $allowed = in_array($to, self::TRANSITIONS[$from] ?? [], true);

        BookingStateTransition::create([
            'booking_id' => $booking->id,
            'from_status' => $from,
            'to_status' => $to,
            'source' => $context['source'] ?? 'domain',
            'source_event_id' => $sourceEventId,
            'trace_id' => $traceId,
            'accepted' => $allowed,
            'reason' => $allowed ? null : 'Illegal booking state transition.',
        ]);

        $logMethod = $allowed ? 'info' : 'warning';
        Log::$logMethod('booking.state_transition.' . ($allowed ? 'accepted' : 'rejected'), [
            'booking_id' => $booking->id,
            'from_status' => $from,
            'to_status' => $to,
            'trace_id' => $traceId,
            'source_event_id' => $sourceEventId,
            'accepted' => $allowed,
        ]);

        if (!$allowed) {
            throw new RuntimeException("Illegal booking state transition from {$from} to {$to}.");
        }

        $booking->forceFill(['status' => $to])->save();

        return true;
    }

    private function normalizeStatus(string $status): string
    {
        return $status === Booking::STATUS_CANCELED_ALIAS ? Booking::STATUS_CANCELLED : $status;
    }
}

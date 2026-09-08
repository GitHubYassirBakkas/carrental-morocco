<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymentStateTransitionValidator
{
    private const BOOKING_TRANSITIONS = [
        Booking::STATUS_PENDING => [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED],
        Booking::STATUS_CONFIRMED => [Booking::STATUS_CONFIRMED, Booking::STATUS_ACTIVE, Booking::STATUS_CANCELLED],
        Booking::STATUS_ACTIVE => [Booking::STATUS_ACTIVE, Booking::STATUS_COMPLETED],
        Booking::STATUS_COMPLETED => [Booking::STATUS_COMPLETED],
        Booking::STATUS_CANCELLED => [Booking::STATUS_CANCELLED],
    ];

    private const SECURITY_DEPOSIT_TRANSITIONS = [
        Booking::SECURITY_DEPOSIT_STATUS_PENDING => [
            Booking::SECURITY_DEPOSIT_STATUS_PENDING,
            Booking::SECURITY_DEPOSIT_STATUS_HELD,
            Booking::SECURITY_DEPOSIT_STATUS_CAPTURED,
            Booking::SECURITY_DEPOSIT_STATUS_REFUND_PENDING,
            Booking::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED,
            Booking::SECURITY_DEPOSIT_STATUS_REFUNDED,
        ],
        Booking::SECURITY_DEPOSIT_STATUS_HELD => [
            Booking::SECURITY_DEPOSIT_STATUS_HELD,
            Booking::SECURITY_DEPOSIT_STATUS_PENDING,
            Booking::SECURITY_DEPOSIT_STATUS_CAPTURED,
            Booking::SECURITY_DEPOSIT_STATUS_REFUND_PENDING,
            Booking::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED,
            Booking::SECURITY_DEPOSIT_STATUS_REFUNDED,
        ],
        Booking::SECURITY_DEPOSIT_STATUS_CAPTURED => [
            Booking::SECURITY_DEPOSIT_STATUS_CAPTURED,
            Booking::SECURITY_DEPOSIT_STATUS_REFUND_PENDING,
            Booking::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED,
            Booking::SECURITY_DEPOSIT_STATUS_REFUNDED,
        ],
        Booking::SECURITY_DEPOSIT_STATUS_REFUND_PENDING => [
            Booking::SECURITY_DEPOSIT_STATUS_REFUND_PENDING,
            Booking::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED,
            Booking::SECURITY_DEPOSIT_STATUS_REFUNDED,
        ],
        Booking::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED => [
            Booking::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED,
            Booking::SECURITY_DEPOSIT_STATUS_REFUNDED,
        ],
        Booking::SECURITY_DEPOSIT_STATUS_REFUNDED => [Booking::SECURITY_DEPOSIT_STATUS_REFUNDED],
        Booking::SECURITY_DEPOSIT_STATUS_RELEASED => [Booking::SECURITY_DEPOSIT_STATUS_REFUNDED],
    ];

    public function validateBookingTransition(?string $from, string $to, array $context = []): bool
    {
        return $this->validate('booking', self::BOOKING_TRANSITIONS, $from, $to, $context);
    }

    public function validateSecurityDepositTransition(?string $from, string $to, array $context = []): bool
    {
        return $this->validate('security_deposit', self::SECURITY_DEPOSIT_TRANSITIONS, $from, $to, $context);
    }

    private function validate(string $machine, array $transitions, ?string $from, string $to, array $context): bool
    {
        $from = $from ?: $to;
        $allowed = in_array($to, $transitions[$from] ?? [], true);

        $message = 'Payment state transition ' . ($allowed ? 'accepted.' : 'rejected.');
        $logContext = array_merge($context, [
            'state_machine' => $machine,
            'previous_status' => $from,
            'new_status' => $to,
            'accepted' => $allowed,
        ]);

        if ($allowed) {
            Log::info($message, $logContext);
        } else {
            Log::warning($message, $logContext);
            throw new RuntimeException("Illegal {$machine} state transition from {$from} to {$to}.");
        }

        return $allowed;
    }
}

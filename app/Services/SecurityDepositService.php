<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\OutboxEvent;
use App\Models\PaymentEventAudit;
use App\Models\PaymentIdempotencyKey;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Stripe\Refund;
use Stripe\PaymentIntent;
use Throwable;

class SecurityDepositService
{
    public const ACTIONABLE_STATES = Booking::SECURITY_DEPOSIT_ACTIONABLE_STATUSES;
    public const FINAL_STATES = Booking::SECURITY_DEPOSIT_FINAL_STATUSES;

    public function getSecurityDepositEffectiveState(Booking $booking): string
    {
        $expectedAmount = $this->getExpectedSecurityDepositAmount($booking);
        $chargedAmount = max(0, (float) $booking->security_deposit_charged_amount);
        $refundedAmount = max(0, (float) ($booking->security_deposit_refunded_amount ?? 0));
        $status = $booking->security_deposit_status ?: Booking::SECURITY_DEPOSIT_STATUS_PENDING;

        if (in_array($status, Booking::SECURITY_DEPOSIT_REFUNDED_STATUSES, true)) {
            return Booking::SECURITY_DEPOSIT_STATUS_REFUNDED;
        }

        if ($status === Booking::SECURITY_DEPOSIT_STATUS_REFUND_PENDING) {
            return Booking::SECURITY_DEPOSIT_STATUS_REFUND_PENDING;
        }

        if ($chargedAmount > 0 || in_array($status, [Booking::SECURITY_DEPOSIT_STATUS_CAPTURED, Booking::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED], true)) {
            if ($refundedAmount >= $chargedAmount && $chargedAmount > 0) {
                return Booking::SECURITY_DEPOSIT_STATUS_REFUNDED;
            }

            if (
                $status === Booking::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED
                || (float) ($booking->security_deposit_penalty_amount ?? 0) > 0
                || ($refundedAmount > 0 && $refundedAmount < $chargedAmount)
            ) {
                return Booking::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED;
            }

            return Booking::SECURITY_DEPOSIT_STATUS_CAPTURED;
        }

        if (!$booking->security_deposit_intent_id) {
            return Booking::SECURITY_DEPOSIT_STATUS_PENDING;
        }

        if ((float) $booking->security_deposit_capturable_amount > 0) {
            return Booking::SECURITY_DEPOSIT_STATUS_HELD;
        }

        if ($status === Booking::SECURITY_DEPOSIT_STATUS_HELD) {
            return Booking::SECURITY_DEPOSIT_STATUS_HELD;
        }

        return Booking::SECURITY_DEPOSIT_STATUS_PENDING;
    }

    public function isSecurityDepositActionable(Booking $booking): bool
    {
        return (bool) $booking->security_deposit_intent_id
            && $this->getCapturableAmount($booking) > 0
            && in_array($this->getSecurityDepositEffectiveState($booking), self::ACTIONABLE_STATES, true);
    }

    public function isSecurityDepositRefundable(Booking $booking): bool
    {
        if (!$booking->security_deposit_intent_id || $this->getSecurityDepositEffectiveState($booking) !== Booking::SECURITY_DEPOSIT_STATUS_CAPTURED) {
            return false;
        }

        $chargedAmount = max(0, (float) $booking->security_deposit_charged_amount);
        $refundedAmount = $this->getRecordedRefundedAmount($booking, $booking->security_deposit_intent_id);

        return $chargedAmount > 0 && $refundedAmount < $chargedAmount;
    }

    public function isSecurityDepositFinal(Booking $booking): bool
    {
        return in_array($this->getSecurityDepositEffectiveState($booking), self::FINAL_STATES, true);
    }

    public function getExpectedSecurityDepositAmount(Booking $booking): float
    {
        return (float) ($booking->security_deposit_amount ?: ($booking->car->security_deposit_amount ?? 0));
    }

    public function getCapturableAmount(Booking $booking): float
    {
        return max(0, (float) $booking->security_deposit_capturable_amount);
    }

    public function requiresSecurityDepositAuthorization(Booking $booking): bool
    {
        return $this->getExpectedSecurityDepositAmount($booking) > 0
            && !$this->isSecurityDepositFinal($booking);
    }

    public function shouldRequestLogicalRelease(Booking $booking): bool
    {
        return $this->isSecurityDepositActionable($booking)
            || $booking->hasSecurityDepositStatus(Booking::SECURITY_DEPOSIT_STATUS_HELD);
    }

    public function normalizeSecurityDepositState(Booking $booking): Booking
    {
        $booking = Booking::whereKey($booking->id)->firstOrFail();
        $effectiveState = $this->getSecurityDepositEffectiveState($booking);

        $updates = [];

        if ($booking->security_deposit_status !== $effectiveState) {
            $updates['security_deposit_status'] = $effectiveState;
        }

        if ($effectiveState === Booking::SECURITY_DEPOSIT_STATUS_PENDING && !$booking->security_deposit_intent_id && (float) $booking->security_deposit_capturable_amount !== 0.0) {
            $updates['security_deposit_capturable_amount'] = 0;
        }

        if (in_array($effectiveState, Booking::SECURITY_DEPOSIT_HELD_OR_FINAL_UNRELEASED_STATUSES, true) && $booking->security_deposit_released_at) {
            $updates['security_deposit_released_at'] = null;
        }

        $normalizedCapturableAmount = $this->normalizedCapturableAmountForState($booking, $effectiveState);

        if (
            $normalizedCapturableAmount !== null
            && (float) $booking->security_deposit_capturable_amount !== $normalizedCapturableAmount
        ) {
            $updates['security_deposit_capturable_amount'] = $normalizedCapturableAmount;
        }

        if ($updates !== []) {
            return DB::transaction(function () use ($booking) {
                $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
                $previousStatus = $lockedBooking->security_deposit_status ?: Booking::SECURITY_DEPOSIT_STATUS_PENDING;
                $effectiveState = $this->getSecurityDepositEffectiveState($lockedBooking);
                $updates = [];

                if ($lockedBooking->security_deposit_status !== $effectiveState) {
                    $updates['security_deposit_status'] = $effectiveState;
                }

                if ($effectiveState === Booking::SECURITY_DEPOSIT_STATUS_PENDING && !$lockedBooking->security_deposit_intent_id && (float) $lockedBooking->security_deposit_capturable_amount !== 0.0) {
                    $updates['security_deposit_capturable_amount'] = 0;
                }

                if (in_array($effectiveState, Booking::SECURITY_DEPOSIT_HELD_OR_FINAL_UNRELEASED_STATUSES, true) && $lockedBooking->security_deposit_released_at) {
                    $updates['security_deposit_released_at'] = null;
                }

                $normalizedCapturableAmount = $this->normalizedCapturableAmountForState($lockedBooking, $effectiveState);

                if (
                    $normalizedCapturableAmount !== null
                    && (float) $lockedBooking->security_deposit_capturable_amount !== $normalizedCapturableAmount
                ) {
                    $updates['security_deposit_capturable_amount'] = $normalizedCapturableAmount;
                }

                if ($updates === []) {
                    return $lockedBooking;
                }

                if (isset($updates['security_deposit_status']) && $updates['security_deposit_status'] !== $previousStatus) {
                    app(PaymentStateTransitionValidator::class)->validateSecurityDepositTransition($previousStatus, $updates['security_deposit_status'], [
                        'booking_id' => $lockedBooking->id,
                        'intent_id' => $lockedBooking->security_deposit_intent_id,
                        'action' => 'normalize_security_deposit_state',
                    ]);
                }

                $lockedBooking->update($updates);

                $this->auditAdminAction($lockedBooking, 'security_deposit.state_normalized', [
                    'previous_status' => $previousStatus,
                    'new_status' => $lockedBooking->security_deposit_status,
                    'capturable_amount' => $lockedBooking->security_deposit_capturable_amount,
                    'charged_amount' => $lockedBooking->security_deposit_charged_amount,
                    'changed' => array_keys($updates),
                ], 'processed');

                Log::info('Security deposit state normalized.', [
                    'booking_id' => $lockedBooking->id,
                    'intent_id' => $lockedBooking->security_deposit_intent_id,
                    'previous_status' => $previousStatus,
                    'new_status' => $lockedBooking->security_deposit_status,
                    'capturable_amount' => $lockedBooking->security_deposit_capturable_amount,
                    'changed' => array_keys($updates),
                ]);

                return $lockedBooking->fresh();
            }, 3);
        }

        return $booking;
    }

    public function normalizeLocalHoldState(Booking $booking): Booking
    {
        return $this->normalizeSecurityDepositState($booking);
    }

    public function capture(Booking $booking, ?float $amount = null): object
    {
        return $this->captureSecurityDeposit($booking, $amount);
    }

    public function captureSecurityDeposit(Booking $booking, ?float $amount = null, ?string $penaltyReason = null): object
    {
        $booking = $this->lockedBooking($this->normalizeSecurityDepositState($booking));
        $audit = null;
        $idempotencyRecord = null;
        $fullDepositAmount = $this->getExpectedSecurityDepositAmount($booking);
        $penaltyAmount = min(max(0, (float) ($amount ?? 0)), $fullDepositAmount);
        $penaltyReason = $this->normalizePenaltyReason($penaltyReason);

        try {
            if ($booking->isCompleted()) {
                throw new Exception('Cannot capture security deposit after booking completion.');
            }

            if (!$booking->security_deposit_intent_id) {
                throw new Exception('No security deposit PaymentIntent found.');
            }

            $audit = $this->auditAdminAction($booking, 'security_deposit.capture.requested', [
                'penalty_amount' => $penaltyAmount,
                'penalty_reason' => $penaltyReason,
                'capture_amount' => $fullDepositAmount,
                'previous_status' => $booking->security_deposit_status,
                'capturable_amount' => $booking->security_deposit_capturable_amount,
            ], 'processing');

            if ($booking->hasSecurityDepositStatus(Booking::SECURITY_DEPOSIT_STATUS_REFUNDED)) {
                Log::info('Security deposit capture skipped: already refunded.', [
                    'booking_id' => $booking->id,
                    'intent_id' => $booking->security_deposit_intent_id,
                    'action' => 'ignore_refunded_deposit',
                ]);

                $this->markAdminAudit($audit, 'ignored');

                return (object) [
                    'id' => $booking->security_deposit_intent_id,
                    'status' => Booking::SECURITY_DEPOSIT_STATUS_REFUNDED,
                ];
            }

            if ($booking->hasSecurityDepositStatus(Booking::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED)) {
                Log::info('Security deposit capture skipped: already partially refunded.', [
                    'booking_id' => $booking->id,
                    'intent_id' => $booking->security_deposit_intent_id,
                    'action' => 'ignore_partially_refunded_deposit',
                ]);

                $this->markAdminAudit($audit, 'ignored');

                return (object) [
                    'id' => $booking->security_deposit_intent_id,
                    'status' => Booking::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED,
                ];
            }

            if ($booking->hasSecurityDepositStatus(Booking::SECURITY_DEPOSIT_STATUS_REFUND_PENDING)) {
                Log::info('Security deposit capture skipped: refund already pending.', [
                    'booking_id' => $booking->id,
                    'intent_id' => $booking->security_deposit_intent_id,
                    'action' => 'ignore_refund_pending_deposit',
                ]);

                $this->markAdminAudit($audit, 'ignored');

                return (object) [
                    'id' => $booking->security_deposit_intent_id,
                    'status' => Booking::SECURITY_DEPOSIT_STATUS_REFUND_PENDING,
                ];
            }

            if ($booking->hasSecurityDepositStatus(Booking::SECURITY_DEPOSIT_STATUS_CAPTURED)) {
                Log::info('Security deposit capture skipped: already captured.', [
                    'booking_id' => $booking->id,
                    'intent_id' => $booking->security_deposit_intent_id,
                    'action' => 'ignore',
                ]);

                $this->markAdminAudit($audit, 'ignored');

                return (object) [
                    'id' => $booking->security_deposit_intent_id,
                    'status' => 'succeeded',
                ];
            }

            $intent = PaymentIntent::retrieve($booking->security_deposit_intent_id);

            if ($intent->status === 'succeeded') {
                Log::info('Security deposit capture skipped: Stripe intent already succeeded.', [
                    'booking_id' => $booking->id,
                    'intent_id' => $intent->id,
                    'action' => 'sync_existing_capture',
                ]);

                $this->syncFromStripeIntent($intent, 'payment_intent.succeeded', 'admin_security_deposit_capture_' . $intent->id);
                if ($amount !== null) {
                    $freshBooking = $booking->fresh();
                    $this->recordSecurityDepositCapturedAudit($freshBooking, $penaltyReason);
                    $this->applyPenaltyAndRefundRemainder($freshBooking, $intent, $penaltyAmount, $penaltyReason);
                }
                $this->markAdminAudit($audit, 'processed');

                return $intent;
            }

            if ($intent->status !== 'requires_capture') {
                Log::info('Security deposit capture skipped: Stripe intent is not capturable.', [
                    'booking_id' => $booking->id,
                    'intent_id' => $intent->id,
                    'stripe_status' => $intent->status,
                    'action' => 'ignore_not_requires_capture',
                ]);

                $this->markAdminAudit($audit, 'ignored');

                return $intent;
            }

            $idempotency = app(PaymentIdempotencyService::class);
            $begin = $idempotency->begin(
                'security_deposit_capture:' . $intent->id,
                'admin_security_deposit_capture_' . $intent->id,
                $intent->id
            );
            $idempotencyRecord = $begin['record'] ?? null;

            if (($begin['status'] ?? null) === PaymentIdempotencyService::RESULT_COMPLETED) {
                Log::info('Security deposit capture skipped: capture idempotency already completed.', [
                    'booking_id' => $booking->id,
                    'intent_id' => $intent->id,
                    'action' => 'ignore_duplicate_capture',
                ]);

                $this->syncFromStripeIntent($intent, 'payment_intent.succeeded', 'admin_security_deposit_capture_' . $intent->id);
                $this->markAdminAudit($audit, 'ignored');

                return (object) [
                    'id' => $intent->id,
                    'status' => 'succeeded',
                ];
            }

            if (($begin['status'] ?? null) === PaymentIdempotencyService::RESULT_PROCESSING_TIMEOUT) {
                Log::warning('Security deposit capture skipped: capture idempotency key is still processing.', [
                    'booking_id' => $booking->id,
                    'intent_id' => $intent->id,
                    'action' => 'ignore_capture_processing',
                ]);

                $this->markAdminAudit($audit, 'ignored');

                return (object) [
                    'id' => $intent->id,
                    'status' => 'processing',
                ];
            }

            Log::info('Security deposit capture requested.', [
                'booking_id' => $booking->id,
                'intent_id' => $intent->id,
                'penalty_amount' => $penaltyAmount,
                'penalty_reason' => $penaltyReason,
                'capture_amount' => $fullDepositAmount,
                'action' => 'capture_full_deposit',
            ]);

            $capturedIntent = $intent->capture();
            $this->syncFromStripeIntent($capturedIntent, 'payment_intent.succeeded', 'admin_security_deposit_capture_' . $capturedIntent->id);

            $freshBooking = $booking->fresh();
            $this->recordSecurityDepositCapturedAudit($freshBooking, $penaltyReason);
            $this->applyPenaltyAndRefundRemainder($freshBooking, $capturedIntent, $penaltyAmount, $penaltyReason);

            if ($idempotencyRecord instanceof PaymentIdempotencyKey) {
                $idempotency->markCompleted($idempotencyRecord);
            }

            $this->markAdminAudit($audit, 'processed');

            return $capturedIntent;
        } catch (Throwable $e) {
            if ($idempotencyRecord instanceof PaymentIdempotencyKey) {
                app(PaymentIdempotencyService::class)->markFailed($idempotencyRecord);
            }

            $this->markAdminAudit($audit, 'failed', $e->getMessage());
            throw $e;
        }
    }

    public function release(Booking $booking): object
    {
        return $this->releaseSecurityDeposit($booking);
    }

    public function releaseSecurityDeposit(Booking $booking): object
    {
        $booking = $this->lockedBooking($this->normalizeSecurityDepositState($booking));
        $audit = null;

        try {
            if ($booking->isCompleted()) {
                throw new Exception('Cannot release security deposit after booking completion.');
            }

            if (!$booking->security_deposit_intent_id) {
                throw new Exception('No security deposit PaymentIntent found.');
            }

            $audit = $this->auditAdminAction($booking, 'security_deposit.release.requested', [
                'previous_status' => $booking->security_deposit_status,
                'capturable_amount' => $booking->security_deposit_capturable_amount,
                'charged_amount' => $booking->security_deposit_charged_amount,
            ], 'processing');

            if ($booking->hasSecurityDepositStatus(Booking::SECURITY_DEPOSIT_STATUS_REFUNDED)) {
                Log::info('Security deposit release skipped: already refunded.', [
                    'booking_id' => $booking->id,
                    'intent_id' => $booking->security_deposit_intent_id,
                    'action' => 'ignore',
                ]);

                $this->markAdminAudit($audit, 'ignored');

                return (object) [
                    'id' => $booking->security_deposit_intent_id,
                    'status' => Booking::SECURITY_DEPOSIT_STATUS_REFUNDED,
                ];
            }

            $intent = PaymentIntent::retrieve($booking->security_deposit_intent_id);

            if ($intent->status === 'canceled') {
                Log::info('Security deposit refund skipped: Stripe intent already canceled.', [
                    'booking_id' => $booking->id,
                    'intent_id' => $intent->id,
                    'action' => 'sync_existing_refund',
                ]);

                $this->syncFromStripeIntent($intent, 'payment_intent.canceled', 'admin_security_deposit_release_' . $intent->id);
                $this->recordSecurityDepositRefundedAudit($booking->fresh());
                $this->markAdminAudit($audit, 'processed');

                return $intent;
            }

            if ($intent->status === 'succeeded') {
                if (!$booking->hasSecurityDepositStatus(Booking::SECURITY_DEPOSIT_STATUS_CAPTURED)) {
                    $this->syncFromStripeIntent($intent, 'payment_intent.succeeded', 'admin_security_deposit_refund_sync_' . $intent->id);
                    $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
                }

                if (!$booking->hasSecurityDepositStatus(Booking::SECURITY_DEPOSIT_STATUS_CAPTURED)) {
                    Log::info('Security deposit refund skipped: booking deposit is not captured.', [
                        'booking_id' => $booking->id,
                        'intent_id' => $intent->id,
                        'booking_deposit_status' => $booking->security_deposit_status,
                        'stripe_status' => $intent->status,
                        'action' => 'ignore_not_captured_for_refund',
                    ]);

                    $this->markAdminAudit($audit, 'ignored');

                    return $intent;
                }

                $chargedAmount = (float) ($booking->security_deposit_charged_amount ?: $this->centsToAmount($intent->amount_received ?? $intent->amount ?? 0));
                $alreadyRefunded = $this->getRecordedRefundedAmount($booking, $intent->id);
                $refundAmount = max(0, $chargedAmount - $alreadyRefunded);

                if ($refundAmount <= 0) {
                    Log::info('Security deposit refund skipped: no remaining refundable amount.', [
                        'booking_id' => $booking->id,
                        'intent_id' => $intent->id,
                        'charged_amount' => $chargedAmount,
                        'refunded_amount' => $alreadyRefunded,
                        'action' => 'ignore_nothing_to_refund',
                    ]);

                    $this->markAdminAudit($audit, 'ignored');

                    return (object) [
                        'id' => $intent->id,
                        'status' => Booking::SECURITY_DEPOSIT_STATUS_REFUNDED,
                    ];
                }

                $this->refundCapturedSecurityDeposit($booking, $intent, $refundAmount);
                $this->markAdminAudit($audit, 'processed');

                return (object) [
                    'id' => $intent->id,
                    'status' => Booking::SECURITY_DEPOSIT_STATUS_REFUNDED,
                ];
            }

            if (!in_array($intent->status, ['requires_capture', 'requires_payment_method'], true)) {
                Log::info('Security deposit release skipped: Stripe intent cannot be canceled or refunded.', [
                    'booking_id' => $booking->id,
                    'intent_id' => $intent->id,
                    'stripe_status' => $intent->status,
                    'action' => 'ignore_not_releasable',
                ]);

                $this->markAdminAudit($audit, 'ignored');

                return $intent;
            }

            Log::info('Security deposit release requested.', [
                'booking_id' => $booking->id,
                'intent_id' => $intent->id,
                'stripe_status' => $intent->status,
                'action' => 'release',
            ]);

            $canceledIntent = $intent->cancel();
            $this->syncFromStripeIntent($canceledIntent, 'payment_intent.canceled', 'admin_security_deposit_release_' . $canceledIntent->id);
            $this->recordSecurityDepositRefundedAudit($booking->fresh());
            $this->markAdminAudit($audit, 'processed');

            return $canceledIntent;
        } catch (Throwable $e) {
            $this->markAdminAudit($audit, 'failed', $e->getMessage());
            throw $e;
        }
    }

    public function retrySecurityDepositRefund(Booking $booking): object
    {
        $booking = $this->lockedBooking($this->normalizeSecurityDepositState($booking));

        if (!$booking->hasSecurityDepositStatus(Booking::SECURITY_DEPOSIT_STATUS_REFUND_PENDING)) {
            throw new Exception('Security deposit refund is not pending.');
        }

        if (!empty($booking->security_deposit_refund_id)) {
            return (object) [
                'id' => $booking->security_deposit_intent_id,
                'status' => $booking->security_deposit_status,
            ];
        }

        if (!$booking->security_deposit_intent_id) {
            throw new Exception('No security deposit PaymentIntent found.');
        }

        $intent = PaymentIntent::retrieve($booking->security_deposit_intent_id);

        if ($intent->status !== 'succeeded') {
            throw new Exception('Security deposit PaymentIntent is not captured.');
        }

        $chargedAmount = (float) ($booking->security_deposit_charged_amount ?: $this->centsToAmount($intent->amount_received ?? $intent->amount ?? 0));
        $alreadyRefunded = $this->getRecordedRefundedAmount($booking, $intent->id);
        $penaltyAmount = min(max(0, (float) ($booking->security_deposit_penalty_amount ?? 0)), $chargedAmount);
        $targetRefund = max(0, $chargedAmount - $penaltyAmount);
        $refundAmount = max(0, $targetRefund - $alreadyRefunded);

        if ($refundAmount <= 0) {
            $this->finalizeCapturedSecurityDepositAfterPenalty(
                $booking,
                $intent->id,
                $chargedAmount,
                $alreadyRefunded,
                $penaltyAmount,
                $booking->security_deposit_penalty_reason
            );

            return (object) [
                'id' => $intent->id,
                'status' => $targetRefund <= $alreadyRefunded ? Booking::SECURITY_DEPOSIT_STATUS_REFUNDED : 'succeeded',
            ];
        }

        try {
            $this->refundCapturedSecurityDeposit($booking, $intent, $refundAmount);
        } catch (Throwable $e) {
            $this->markSecurityDepositRefundPending(
                $booking,
                $intent->id,
                $chargedAmount,
                $alreadyRefunded,
                $penaltyAmount,
                $booking->security_deposit_penalty_reason,
                $e
            );

            throw $e;
        }

        return (object) [
            'id' => $intent->id,
            'status' => Booking::SECURITY_DEPOSIT_STATUS_REFUNDED,
        ];
    }

    public function syncFromStripeIntent(object $paymentIntent, string $eventType, ?string $eventId = null): array
    {
        $metadata = isset($paymentIntent->metadata)
            ? (method_exists($paymentIntent->metadata, 'toArray') ? $paymentIntent->metadata->toArray() : (array) $paymentIntent->metadata)
            : [];

        $bookingId = $metadata['booking_id'] ?? null;
        $intentId = $paymentIntent->id ?? null;
        $stripeStatus = $paymentIntent->status ?? null;

        if (!$bookingId || !$intentId) {
            Log::warning('Security deposit webhook ignored: missing booking or intent id.', [
                'event_type' => $eventType,
                'event_id' => $eventId,
                'intent_id' => $intentId,
                'action' => 'ignore',
            ]);

            return ['action' => 'ignore', 'previous_status' => null, 'new_status' => null];
        }

        return DB::transaction(function () use ($bookingId, $intentId, $paymentIntent, $eventType, $eventId, $stripeStatus) {
            $traceId = (string) Str::uuid();
            $booking = Booking::whereKey($bookingId)->lockForUpdate()->first();

            if (!$booking) {
                Log::warning('Security deposit webhook ignored: booking not found.', [
                    'event_type' => $eventType,
                    'event_id' => $eventId,
                    'booking_id' => $bookingId,
                    'intent_id' => $intentId,
                    'action' => 'ignore',
                ]);

                return ['action' => 'ignore', 'previous_status' => null, 'new_status' => null];
            }

            $previousStatus = $booking->security_deposit_status ?? Booking::SECURITY_DEPOSIT_STATUS_PENDING;

            if ($booking->isCompleted()) {
                Log::warning('Security deposit webhook ignored: booking already completed.', [
                    'event_type' => $eventType,
                    'event_id' => $eventId,
                    'booking_id' => $booking->id,
                    'intent_id' => $intentId,
                    'previous_status' => $previousStatus,
                    'action' => 'ignore_completed_booking',
                ]);

                return ['action' => 'ignore_completed_booking', 'previous_status' => $previousStatus, 'new_status' => $previousStatus];
            }

            $updates = ['security_deposit_intent_id' => $booking->security_deposit_intent_id ?: $intentId];
            $action = 'ignore';
            $validator = app(PaymentStateTransitionValidator::class);

            if ($booking->security_deposit_intent_id && $booking->security_deposit_intent_id !== $intentId) {
                Log::warning('Security deposit webhook ignored: stale or mismatched intent id.', [
                    'event_type' => $eventType,
                    'event_id' => $eventId,
                    'booking_id' => $booking->id,
                    'booking_intent_id' => $booking->security_deposit_intent_id,
                    'event_intent_id' => $intentId,
                    'previous_status' => $previousStatus,
                    'stripe_status' => $stripeStatus,
                    'action' => 'ignore_stale_intent',
                ]);

                return ['action' => 'ignore_stale_intent', 'previous_status' => $previousStatus, 'new_status' => $previousStatus];
            }

            if ($previousStatus === Booking::SECURITY_DEPOSIT_STATUS_RELEASED) {
                $previousStatus = Booking::SECURITY_DEPOSIT_STATUS_REFUNDED;
            }

            if ($previousStatus === Booking::SECURITY_DEPOSIT_STATUS_REFUNDED && $eventType !== 'payment_intent.canceled') {
                Log::warning('Security deposit webhook ignored: deposit already refunded.', [
                    'event_type' => $eventType,
                    'event_id' => $eventId,
                    'booking_id' => $booking->id,
                    'intent_id' => $intentId,
                    'previous_status' => $previousStatus,
                    'stripe_status' => $stripeStatus,
                    'action' => 'ignore_refunded_deposit',
                ]);

                return ['action' => 'ignore_refunded_deposit', 'previous_status' => $previousStatus, 'new_status' => $previousStatus];
            }

            if ($previousStatus === Booking::SECURITY_DEPOSIT_STATUS_REFUND_PENDING) {
                Log::warning('Security deposit webhook ignored: refund retry is pending.', [
                    'event_type' => $eventType,
                    'event_id' => $eventId,
                    'booking_id' => $booking->id,
                    'intent_id' => $intentId,
                    'previous_status' => $previousStatus,
                    'stripe_status' => $stripeStatus,
                    'action' => 'ignore_refund_pending_deposit',
                ]);

                return ['action' => 'ignore_refund_pending_deposit', 'previous_status' => $previousStatus, 'new_status' => $previousStatus];
            }

            if ($previousStatus === Booking::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED) {
                Log::warning('Security deposit webhook ignored: deposit already partially refunded.', [
                    'event_type' => $eventType,
                    'event_id' => $eventId,
                    'booking_id' => $booking->id,
                    'intent_id' => $intentId,
                    'previous_status' => $previousStatus,
                    'stripe_status' => $stripeStatus,
                    'action' => 'ignore_partially_refunded_deposit',
                ]);

                return ['action' => 'ignore_partially_refunded_deposit', 'previous_status' => $previousStatus, 'new_status' => $previousStatus];
            }

            if ($previousStatus === Booking::SECURITY_DEPOSIT_STATUS_CAPTURED && $eventType !== 'payment_intent.succeeded') {
                Log::warning('Security deposit webhook ignored: deposit already captured.', [
                    'event_type' => $eventType,
                    'event_id' => $eventId,
                    'booking_id' => $booking->id,
                    'intent_id' => $intentId,
                    'previous_status' => $previousStatus,
                    'stripe_status' => $stripeStatus,
                    'action' => 'ignore_captured_deposit',
                ]);

                return ['action' => 'ignore_captured_deposit', 'previous_status' => $previousStatus, 'new_status' => $previousStatus];
            }

            if ($eventType === 'payment_intent.amount_capturable_updated' && $stripeStatus === 'requires_capture') {
                $amount = $paymentIntent->amount ?? ((float) $booking->security_deposit_amount * 100);
                $capturableAmount = $paymentIntent->amount_capturable ?? $amount;
                $chargedAmount = max(
                    (float) $booking->security_deposit_charged_amount,
                    $this->centsToAmount($paymentIntent->amount_received ?? 0)
                );
                $newStatus = $chargedAmount > 0 ? Booking::SECURITY_DEPOSIT_STATUS_CAPTURED : Booking::SECURITY_DEPOSIT_STATUS_HELD;

                $updates = array_merge($updates, [
                    'security_deposit_status' => $newStatus,
                    'security_deposit_amount' => $this->centsToAmount($amount),
                    'security_deposit_capturable_amount' => $chargedAmount > 0 ? 0 : $this->centsToAmount($capturableAmount),
                    'security_deposit_charged_amount' => $chargedAmount,
                    'security_deposit_released_at' => null,
                ]);
                $action = $previousStatus === $newStatus ? 'sync_' . $newStatus . '_idempotent' : 'mark_' . $newStatus;
            } elseif ($eventType === 'payment_intent.succeeded' && $stripeStatus === 'succeeded') {
                $amountCapturable = $this->centsToAmount($paymentIntent->amount_capturable ?? 0);
                $amountReceived = $this->centsToAmount($paymentIntent->amount_received ?? 0);

                if ((float) $booking->security_deposit_charged_amount <= 0 && $amountReceived <= 0 && $amountCapturable > 0) {
                    $updates = array_merge($updates, [
                        'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_HELD,
                        'security_deposit_capturable_amount' => $amountCapturable,
                        'security_deposit_released_at' => null,
                    ]);
                    $action = $previousStatus === Booking::SECURITY_DEPOSIT_STATUS_HELD ? 'sync_held_idempotent' : 'mark_held_from_success_without_capture';
                } else {
                    $expectedAmount = $this->centsToAmount($paymentIntent->amount ?? ((float) $booking->security_deposit_amount * 100));
                    $capturedAmount = max(
                        (float) $booking->security_deposit_charged_amount,
                        $this->centsToAmount($paymentIntent->amount_received ?? $paymentIntent->amount ?? 0)
                    );
                    $newStatus = $capturedAmount > 0 ? Booking::SECURITY_DEPOSIT_STATUS_CAPTURED : Booking::SECURITY_DEPOSIT_STATUS_HELD;

                    $updates = array_merge($updates, [
                        'security_deposit_status' => $newStatus,
                        'security_deposit_amount' => $expectedAmount,
                        'security_deposit_charged_amount' => $capturedAmount,
                        'security_deposit_capturable_amount' => 0,
                    ]);
                    $action = $previousStatus === $newStatus ? 'sync_' . $newStatus . '_idempotent' : 'mark_' . $newStatus;
                }
            } elseif ($eventType === 'payment_intent.canceled') {
                if ($previousStatus === Booking::SECURITY_DEPOSIT_STATUS_CAPTURED) {
                    Log::warning('Security deposit release ignored: booking deposit already captured.', [
                        'event_type' => $eventType,
                        'event_id' => $eventId,
                        'booking_id' => $booking->id,
                        'intent_id' => $intentId,
                        'previous_status' => $previousStatus,
                        'action' => 'ignore',
                    ]);

                    return ['action' => 'ignore', 'previous_status' => $previousStatus, 'new_status' => $previousStatus];
                }

                $updates = array_merge($updates, $this->withRefundedAmount([
                    'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_REFUNDED,
                    'security_deposit_capturable_amount' => 0,
                    'security_deposit_released_at' => $booking->security_deposit_released_at ?? now(),
                ], 0));
                $action = $previousStatus === Booking::SECURITY_DEPOSIT_STATUS_REFUNDED ? 'sync_refunded_idempotent' : 'mark_refunded';
            } elseif ($eventType === 'payment_intent.payment_failed') {
                $updates = array_merge($updates, [
                    'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_PENDING,
                    'security_deposit_capturable_amount' => 0,
                ]);
                $action = $previousStatus === Booking::SECURITY_DEPOSIT_STATUS_PENDING ? 'sync_failed_idempotent' : 'mark_pending_after_failure';
            }

            if (
                isset($updates['security_deposit_status']) &&
                $updates['security_deposit_status'] !== $previousStatus &&
                !$validator->validateSecurityDepositTransition($previousStatus, $updates['security_deposit_status'], [
                    'event_type' => $eventType,
                    'event_id' => $eventId,
                    'booking_id' => $booking->id,
                    'intent_id' => $intentId,
                    'action' => $action,
                ])
            ) {
                return ['action' => 'reject_invalid_transition', 'previous_status' => $previousStatus, 'new_status' => $previousStatus];
            }

            $updates = $this->changedAttributes($booking, $updates);

            if ($updates !== []) {
                $booking->update($updates);
            }

            $freshForPayment = $booking->fresh();
            $freshBooking = $booking->fresh();

            Log::info('Security deposit state synchronized.', [
                'event_type' => $eventType,
                'event_id' => $eventId,
                'booking_id' => $booking->id,
                'intent_id' => $intentId,
                'stripe_status' => $stripeStatus,
                'previous_status' => $previousStatus,
                'new_status' => $freshBooking->security_deposit_status,
                'action' => $action,
                'changed' => array_keys($updates),
            ]);

            OutboxEvent::create([
                'event_type' => 'security_deposit_synchronized',
                'trace_id' => $traceId,
                'source_event_id' => $eventId,
                'payload' => [
                    'event_id' => $eventId,
                    'trace_id' => $traceId,
                    'correlation_id' => $traceId,
                    'source_event_id' => $eventId,
                    'event_type' => $eventType,
                    'booking_id' => $booking->id,
                    'payment_intent_id' => $intentId,
                    'previous_status' => $previousStatus,
                    'new_status' => $freshBooking->security_deposit_status,
                    'action' => $action,
                    'changed' => array_keys($updates),
                ],
            ]);

            return [
                'action' => $action,
                'previous_status' => $previousStatus,
                'new_status' => $freshBooking->security_deposit_status,
            ];
        }, 3);
    }

    private function refundCapturedSecurityDeposit(Booking $booking, object $intent, float $refundAmount): void
    {
        DB::transaction(function () use ($booking, $intent, $refundAmount) {
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $previousStatus = $lockedBooking->security_deposit_status;
            $alreadyRefunded = $this->getRecordedRefundedAmount($lockedBooking, $intent->id);
            $chargedAmount = (float) $lockedBooking->security_deposit_charged_amount;
            $remainingRefundable = max(0, $chargedAmount - $alreadyRefunded);
            $refundAmount = min($refundAmount, $remainingRefundable);

            if (!empty($lockedBooking->security_deposit_refund_id)) {
                Log::info('Security deposit refund skipped: refund id already recorded.', [
                    'booking_id' => $lockedBooking->id,
                    'intent_id' => $intent->id,
                    'refund_id' => $lockedBooking->security_deposit_refund_id,
                    'action' => 'ignore_duplicate_refund',
                ]);

                return;
            }

            if ($refundAmount <= 0) {
                return;
            }

            if (!$this->stripePaymentHasCapturedFunds($intent, $chargedAmount)) {
                throw new Exception('Stripe payment is missing captured funds for refund.');
            }

            $idempotency = app(PaymentIdempotencyService::class);
            $refundKey = 'security_deposit_refund:' . $intent->id . ':' . (int) round($refundAmount * 100);
            $begin = $idempotency->begin($refundKey, 'admin_security_deposit_refund_' . $intent->id, $intent->id);
            $idempotencyRecord = $begin['record'] ?? null;

            if (in_array($begin['status'] ?? null, [PaymentIdempotencyService::RESULT_COMPLETED, PaymentIdempotencyService::RESULT_PROCESSING_TIMEOUT], true)) {
                return;
            }

            try {
                $stripeRefund = Refund::create([
                    'payment_intent' => $intent->id,
                    'amount' => (int) round($refundAmount * 100),
                    'metadata' => [
                        'booking_id' => (string) $lockedBooking->id,
                        'type' => 'security_deposit_refund',
                    ],
                ]);
            } catch (Throwable $e) {
                if ($idempotencyRecord instanceof PaymentIdempotencyKey) {
                    $idempotency->markFailed($idempotencyRecord);
                }

                throw $e;
            }

            if ($idempotencyRecord instanceof PaymentIdempotencyKey) {
                $idempotency->markCompleted($idempotencyRecord);
            }

            $newRefundedAmount = $alreadyRefunded + $refundAmount;
            $penaltyKept = max(0, $chargedAmount - $newRefundedAmount);
            $newStatus = $newRefundedAmount >= $chargedAmount
                ? Booking::SECURITY_DEPOSIT_STATUS_REFUNDED
                : Booking::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED;

            $lockedBooking->update($this->withRefundError($this->withRefundedAudit($this->withRefundId($this->withPenaltyAmount($this->withRefundedAmount([
                'security_deposit_status' => $newStatus,
                'security_deposit_capturable_amount' => 0,
                'security_deposit_released_at' => $newStatus === Booking::SECURITY_DEPOSIT_STATUS_REFUNDED
                    ? ($lockedBooking->security_deposit_released_at ?? now())
                    : null,
            ], $newRefundedAmount), $penaltyKept), $stripeRefund->id ?? null)), null));

            $traceId = (string) Str::uuid();

            OutboxEvent::create([
                'event_type' => 'security_deposit_synchronized',
                'trace_id' => $traceId,
                'source_event_id' => 'admin_security_deposit_refund_' . $intent->id . '_' . (string) Str::uuid(),
                'payload' => [
                    'event_id' => 'admin_security_deposit_refund_' . $intent->id,
                    'trace_id' => $traceId,
                    'correlation_id' => $traceId,
                    'source_event_id' => 'admin_security_deposit_refund_' . $intent->id,
                    'event_type' => 'admin.security_deposit.refunded',
                    'booking_id' => $lockedBooking->id,
                    'payment_intent_id' => $intent->id,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'action' => 'mark_security_deposit_refunded',
                    'refund_amount' => $refundAmount,
                    'refund_id' => $stripeRefund->id ?? null,
                    'penalty_amount' => $penaltyKept,
                    'refunded_amount' => $newRefundedAmount,
                    'changed' => ['security_deposit_status', 'security_deposit_capturable_amount', 'security_deposit_released_at', 'security_deposit_refunded_amount', 'security_deposit_penalty_amount', 'security_deposit_refund_id', 'security_deposit_refunded_by', 'security_deposit_refunded_at', 'security_deposit_refund_error_message'],
                ],
            ]);
        }, 3);
    }

    private function applyPenaltyAndRefundRemainder(Booking $booking, object $intent, float $penaltyAmount, ?string $penaltyReason = null): void
    {
        $booking = Booking::whereKey($booking->id)->firstOrFail();
        $chargedAmount = (float) ($booking->security_deposit_charged_amount ?: $this->centsToAmount($intent->amount_received ?? $intent->amount ?? 0));
        $penaltyAmount = min(max(0, $penaltyAmount), $chargedAmount);
        $alreadyRefunded = $this->getRecordedRefundedAmount($booking, $intent->id);
        $targetRefund = max(0, $chargedAmount - min($penaltyAmount, $chargedAmount));
        $refundAmount = max(0, $targetRefund - $alreadyRefunded);

        if ($refundAmount <= 0) {
            $this->finalizeCapturedSecurityDepositAfterPenalty($booking, $intent->id, $chargedAmount, $alreadyRefunded, $penaltyAmount, $penaltyReason);
            return;
        }

        $this->recordSecurityDepositPenalty($booking, $penaltyAmount, $penaltyReason);

        try {
            $this->refundCapturedSecurityDeposit($booking, $intent, $refundAmount);
        } catch (Throwable $e) {
            $this->markSecurityDepositRefundPending($booking, $intent->id, $chargedAmount, $alreadyRefunded, $penaltyAmount, $penaltyReason, $e);

            throw new Exception('Security deposit captured, but refund failed. Refund is pending retry: ' . $e->getMessage(), 0, $e);
        }
    }

    private function finalizeCapturedSecurityDepositAfterPenalty(
        Booking $booking,
        string $intentId,
        float $chargedAmount,
        float $refundedAmount,
        float $penaltyAmount,
        ?string $penaltyReason = null
    ): void {
        DB::transaction(function () use ($booking, $intentId, $chargedAmount, $refundedAmount, $penaltyAmount, $penaltyReason) {
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $previousStatus = $lockedBooking->security_deposit_status ?: Booking::SECURITY_DEPOSIT_STATUS_CAPTURED;
            $newStatus = $refundedAmount >= $chargedAmount && $chargedAmount > 0
                ? Booking::SECURITY_DEPOSIT_STATUS_REFUNDED
                : ($penaltyAmount > 0 ? Booking::SECURITY_DEPOSIT_STATUS_PARTIALLY_REFUNDED : Booking::SECURITY_DEPOSIT_STATUS_CAPTURED);

            $updates = $this->withRefundError($this->withPenaltyReason($this->withPenaltyAmount($this->withRefundedAmount([
                'security_deposit_status' => $newStatus,
                'security_deposit_capturable_amount' => 0,
                'security_deposit_released_at' => $newStatus === Booking::SECURITY_DEPOSIT_STATUS_REFUNDED
                    ? ($lockedBooking->security_deposit_released_at ?? now())
                    : null,
            ], $refundedAmount), $penaltyAmount), $penaltyReason), null);

            $updates = $this->changedAttributes($lockedBooking, $updates);

            if ($updates === []) {
                return;
            }

            if (isset($updates['security_deposit_status']) && $updates['security_deposit_status'] !== $previousStatus) {
                app(PaymentStateTransitionValidator::class)->validateSecurityDepositTransition($previousStatus, $updates['security_deposit_status'], [
                    'booking_id' => $lockedBooking->id,
                    'intent_id' => $intentId,
                    'action' => 'finalize_security_deposit_penalty',
                ]);
            }

            $lockedBooking->update($updates);
        }, 3);
    }

    private function recordSecurityDepositPenalty(Booking $booking, float $penaltyAmount, ?string $penaltyReason = null): void
    {
        $updates = $this->withPenaltyReason($this->withPenaltyAmount([], $penaltyAmount), $penaltyReason);

        if ($updates === []) {
            return;
        }

        Booking::whereKey($booking->id)->update($updates);
    }

    private function markSecurityDepositRefundPending(
        Booking $booking,
        string $intentId,
        float $chargedAmount,
        float $alreadyRefunded,
        float $penaltyAmount,
        ?string $penaltyReason,
        Throwable $e
    ): void {
        DB::transaction(function () use ($booking, $intentId, $chargedAmount, $alreadyRefunded, $penaltyAmount, $penaltyReason, $e) {
            $lockedBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $previousStatus = $lockedBooking->security_deposit_status ?: Booking::SECURITY_DEPOSIT_STATUS_CAPTURED;

            $updates = $this->withProcessedBy($this->withRefundError($this->withPenaltyReason($this->withPenaltyAmount($this->withRefundedAmount([
                'security_deposit_status' => Booking::SECURITY_DEPOSIT_STATUS_REFUND_PENDING,
                'security_deposit_charged_amount' => $chargedAmount,
                'security_deposit_capturable_amount' => 0,
                'security_deposit_released_at' => null,
            ], $alreadyRefunded), $penaltyAmount), $penaltyReason), $e->getMessage()));

            if (isset($updates['security_deposit_status']) && $updates['security_deposit_status'] !== $previousStatus) {
                app(PaymentStateTransitionValidator::class)->validateSecurityDepositTransition($previousStatus, $updates['security_deposit_status'], [
                    'booking_id' => $lockedBooking->id,
                    'intent_id' => $intentId,
                    'action' => 'mark_security_deposit_refund_pending',
                ]);
            }

            $lockedBooking->update($updates);
        }, 3);

        Log::error('Security deposit refund failed after capture.', [
            'booking_id' => $booking->id,
            'intent_id' => $intentId,
            'charged_amount' => $chargedAmount,
            'already_refunded' => $alreadyRefunded,
            'penalty_amount' => $penaltyAmount,
            'error' => $e->getMessage(),
            'action' => 'mark_security_deposit_refund_pending',
        ]);
    }

    private function auditAdminAction(Booking $booking, string $eventType, array $payload = [], string $outcome = 'processing'): PaymentEventAudit
    {
        return PaymentEventAudit::updateOrCreate(
            [
                'event_id' => 'admin_' . $eventType . '_' . $booking->security_deposit_intent_id,
            ],
            [
                'booking_id' => $booking->id,
                'event_type' => $eventType,
                'payment_intent_id' => $booking->security_deposit_intent_id,
                'payload' => array_merge([
                    'booking_id' => $booking->id,
                    'payment_intent_id' => $booking->security_deposit_intent_id,
                    'admin_user_id' => auth()->id(),
                ], $payload),
                'processed_at' => now(),
                'outcome' => $outcome,
                'error_message' => null,
            ]
        );
    }

    private function markAdminAudit(?PaymentEventAudit $audit, string $outcome, ?string $errorMessage = null): void
    {
        if (!$audit) {
            return;
        }

        $audit->forceFill([
            'processed_at' => now(),
            'outcome' => $outcome,
            'error_message' => $errorMessage ? substr($errorMessage, 0, 65000) : null,
        ])->save();
    }

    private function changedAttributes(Booking $booking, array $updates): array
    {
        return array_filter(
            $updates,
            fn ($value, $key) => (string) ($booking->{$key} ?? '') !== (string) ($value ?? ''),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function centsToAmount(int|float $amount): float
    {
        return ((float) $amount) / 100;
    }

    private function normalizedCapturableAmountForState(Booking $booking, string $effectiveState): ?float
    {
        if (in_array($effectiveState, Booking::SECURITY_DEPOSIT_FINAL_STATUSES, true)) {
            return 0.0;
        }

        return null;
    }

    private function withRefundedAmount(array $updates, float $refundedAmount): array
    {
        if (Schema::hasColumn('bookings', 'security_deposit_refunded_amount')) {
            $updates['security_deposit_refunded_amount'] = $refundedAmount;
        }

        return $updates;
    }

    private function withPenaltyAmount(array $updates, float $penaltyAmount): array
    {
        if (Schema::hasColumn('bookings', 'security_deposit_penalty_amount')) {
            $updates['security_deposit_penalty_amount'] = $penaltyAmount;
        }

        return $updates;
    }

    private function withPenaltyReason(array $updates, ?string $penaltyReason): array
    {
        if ($penaltyReason !== null && Schema::hasColumn('bookings', 'security_deposit_penalty_reason')) {
            $updates['security_deposit_penalty_reason'] = $penaltyReason;
        }

        return $updates;
    }

    private function withRefundError(array $updates, ?string $errorMessage): array
    {
        if (Schema::hasColumn('bookings', 'security_deposit_refund_error_message')) {
            $updates['security_deposit_refund_error_message'] = $errorMessage ? substr($errorMessage, 0, 65000) : null;
        }

        return $updates;
    }

    private function withCapturedAudit(array $updates): array
    {
        $adminId = auth()->id();

        if ($adminId && Schema::hasColumn('bookings', 'security_deposit_captured_by')) {
            $updates['security_deposit_captured_by'] = $adminId;
        }

        if (Schema::hasColumn('bookings', 'security_deposit_captured_at')) {
            $updates['security_deposit_captured_at'] = now();
        }

        return $this->withProcessedBy($updates);
    }

    private function withRefundedAudit(array $updates): array
    {
        $adminId = auth()->id();

        if ($adminId && Schema::hasColumn('bookings', 'security_deposit_refunded_by')) {
            $updates['security_deposit_refunded_by'] = $adminId;
        }

        if (Schema::hasColumn('bookings', 'security_deposit_refunded_at')) {
            $updates['security_deposit_refunded_at'] = now();
        }

        return $this->withProcessedBy($updates);
    }

    private function withProcessedBy(array $updates): array
    {
        $adminId = auth()->id();

        if ($adminId && Schema::hasColumn('bookings', 'security_deposit_processed_by')) {
            $updates['security_deposit_processed_by'] = $adminId;
        }

        return $updates;
    }

    private function withRefundId(array $updates, ?string $refundId): array
    {
        if ($refundId && Schema::hasColumn('bookings', 'security_deposit_refund_id')) {
            $updates['security_deposit_refund_id'] = $refundId;
        }

        return $updates;
    }

    private function recordSecurityDepositCapturedAudit(Booking $booking, ?string $penaltyReason = null): void
    {
        $updates = $this->withPenaltyReason($this->withCapturedAudit([]), $penaltyReason);

        if ($updates === []) {
            return;
        }

        Booking::whereKey($booking->id)->update($updates);
    }

    private function recordSecurityDepositRefundedAudit(?Booking $booking): void
    {
        if (!$booking) {
            return;
        }

        $updates = $this->withRefundedAudit([]);

        if ($updates === []) {
            return;
        }

        Booking::whereKey($booking->id)->update($updates);
    }

    private function normalizePenaltyReason(?string $penaltyReason): ?string
    {
        $penaltyReason = trim((string) $penaltyReason);

        return $penaltyReason === '' ? null : $penaltyReason;
    }

    private function stripePaymentHasCapturedFunds(object $intent, float $chargedAmount): bool
    {
        if (empty($intent->id) || $chargedAmount <= 0) {
            return false;
        }

        $amountReceived = $this->centsToAmount($intent->amount_received ?? 0);

        if ($amountReceived > 0) {
            return true;
        }

        return ($intent->status ?? null) === 'succeeded' && $chargedAmount > 0;
    }

    private function getRecordedRefundedAmount(Booking $booking, string $intentId): float
    {
        if (Schema::hasColumn('bookings', 'security_deposit_refunded_amount')) {
            return max(0, (float) ($booking->security_deposit_refunded_amount ?? 0));
        }

        return PaymentIdempotencyKey::query()
            ->where('payment_intent_id', $intentId)
            ->where('status', PaymentIdempotencyKey::STATUS_COMPLETED)
            ->where('idempotency_key', 'like', 'security_deposit_refund:' . $intentId . ':%')
            ->get()
            ->sum(function (PaymentIdempotencyKey $record): float {
                $parts = explode(':', $record->idempotency_key);

                return ((float) end($parts)) / 100;
            });
    }

    private function lockedBooking(Booking $booking): Booking
    {
        return DB::transaction(fn () => Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail());
    }
}

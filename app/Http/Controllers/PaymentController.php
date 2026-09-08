<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessStripeWebhookEventJob;
use App\Mail\BookingPendingMail;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentIdempotencyKey;
use App\Models\StripeWebhookEvent;
use App\Services\InvoiceService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\PaymentWebhookMetrics;
use App\Services\SecurityDepositService;
use App\Services\StripePaymentIntentGateway;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\CardException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class PaymentController extends Controller
{
    private const STRIPE_CURRENCY = 'mad';

    private const STRIPE_RENTAL_TYPE = 'rental';

    private const STRIPE_SECURITY_DEPOSIT_TYPE = 'security_deposit';

    public function __construct(
        private readonly StripePaymentIntentGateway $stripePaymentIntents,
        private readonly SecurityDepositService $securityDeposits,
        private readonly InvoiceService $invoices,
        private readonly PaymentService $payments,
        private readonly NotificationService $notificationService
    ) {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = $this->verifiedStripeEvent($payload, $signature, $webhookSecret);

            $object = $event->data->object ?? null;
            $eventType = $event->type ?? 'unknown';
            $eventId = $event->id ?? null;
            $paymentIntentId = $this->value($object, 'payment_intent') ?? $this->value($object, 'id');
            $eventPayload = method_exists($event, 'toArray')
                ? $event->toArray()
                : json_decode(json_encode($event), true);
            $eventPayload = $this->sanitizeStripePayload($eventPayload);

            if (! $eventId) {
                Log::warning('Stripe webhook rejected: missing event id.', [
                    'event_type' => $eventType,
                    'payment_intent_id' => $paymentIntentId,
                ]);

                return response()->json(['received' => false], 400);
            }

            $eventPayload['correlation_id'] = $eventPayload['correlation_id'] ?? $eventId;

            $metadata = $this->toArraySafe($this->value($object, 'metadata'));

            $bookingId = $metadata['booking_id'] ?? null;
            $metadataType = $metadata['type'] ?? null;
            $paymentIntentStatus = $this->value($object, 'status');
            $needsRefundReconciliation = $this->needsRentalRefundReconciliation($eventType, $metadataType, $object);

            Log::info('payment.webhook.lifecycle', [
                'event_type' => $eventType,
                'booking_id' => $bookingId,
                'payment_intent_id' => $paymentIntentId,
                'status' => $paymentIntentStatus,
            ]);

            if (PaymentIdempotencyKey::where('idempotency_key', $eventId)
                ->where('status', PaymentIdempotencyKey::STATUS_COMPLETED)
                ->exists()
                && ! $needsRefundReconciliation) {
                app(PaymentWebhookMetrics::class)->duplicate([
                    'event_id' => $eventId,
                    'booking_id' => $bookingId ? (int) $bookingId : null,
                    'payment_intent_id' => $paymentIntentId,
                    'lock_status' => 'not_required',
                    'idempotency_status' => PaymentIdempotencyKey::STATUS_COMPLETED,
                ]);

                return response()->json(['received' => true], 200);
            }

            $storedEvent = $this->storeStripeWebhookEvent($eventId, $eventType, $eventPayload);

            if ($storedEvent->status === StripeWebhookEvent::STATUS_PROCESSED) {
                if ($needsRefundReconciliation) {
                    $storedEvent->forceFill([
                        'status' => StripeWebhookEvent::STATUS_PENDING,
                        'error_message' => null,
                        'failed_at' => null,
                        'updated_at' => now(),
                    ])->save();
                } else {
                    app(PaymentWebhookMetrics::class)->duplicate([
                        'event_id' => $eventId,
                        'booking_id' => $bookingId ? (int) $bookingId : null,
                        'payment_intent_id' => $paymentIntentId,
                        'lock_status' => 'not_required',
                        'idempotency_status' => 'event_store_processed',
                    ]);

                    return response()->json(['received' => true], 200);
                }
            }

            if ($storedEvent->status === StripeWebhookEvent::STATUS_FAILED) {
                $storedEvent->forceFill([
                    'status' => StripeWebhookEvent::STATUS_PENDING,
                    'error_message' => null,
                    'updated_at' => now(),
                ])->save();
            }

            if (in_array($storedEvent->status, [StripeWebhookEvent::STATUS_PENDING, StripeWebhookEvent::STATUS_FAILED], true)) {
                ProcessStripeWebhookEventJob::dispatch($storedEvent->id)->afterResponse();
            }

            app(PaymentWebhookMetrics::class)->ingested([
                'event_id' => $eventId,
                'booking_id' => $bookingId ? (int) $bookingId : null,
                'payment_intent_id' => $paymentIntentId,
                'event_type' => $eventType,
            ]);

            Log::info('payment.webhook.lifecycle', [
                'event_type' => $eventType,
                'booking_id' => $bookingId,
                'payment_intent_id' => $paymentIntentId,
                'status' => $storedEvent->status,
            ]);
        } catch (HttpResponseException $e) {
            return $e->getResponse();
        } catch (\Throwable $e) {
            app(PaymentWebhookMetrics::class)->fail([
                'lock_status' => 'unknown',
                'idempotency_status' => 'unknown',
            ]);

            Log::error('Stripe webhook ingestion failed', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['received' => false], 500);
        }

        return response()->json(['received' => true], 200);
    }

    private function storeStripeWebhookEvent(string $eventId, string $eventType, array $payload): StripeWebhookEvent
    {
        $storedEvent = StripeWebhookEvent::where('event_id', $eventId)->first();

        if ($storedEvent) {
            return $storedEvent;
        }

        try {
            return StripeWebhookEvent::create([
                'event_id' => $eventId,
                'type' => $eventType,
                'payload' => $payload,
                'status' => StripeWebhookEvent::STATUS_PENDING,
                'attempts' => 0,
            ]);
        } catch (QueryException $e) {
            $storedEvent = StripeWebhookEvent::where('event_id', $eventId)->first();

            if ($storedEvent) {
                return $storedEvent;
            }

            throw $e;
        }
    }

    private function needsRentalRefundReconciliation(string $eventType, ?string $metadataType, mixed $object): bool
    {
        if (! in_array($eventType, ['refund.created', 'refund.updated', 'refund.succeeded', 'refund.failed'], true)) {
            return false;
        }

        if ($metadataType !== 'rental_refund') {
            return false;
        }

        $refundId = $this->value($object, 'id');

        if (! is_string($refundId) || $refundId === '') {
            return false;
        }

        return ! Payment::where('stripe_refund_id', $refundId)
            ->orWhere('transaction_id', $refundId)
            ->exists();
    }

    private function sanitizeStripePayload(array $payload): array
    {
        $blockedKeys = [
            'client_secret',
            'payment_method',
            'payment_method_details',
            'payment_method_options',
            'billing_details',
            'receipt_url',
            'source',
            'card',
        ];

        foreach ($payload as $key => $value) {
            if (in_array($key, $blockedKeys, true)) {
                $payload[$key] = '[redacted]';

                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->sanitizeStripePayload($value);
            }
        }

        return $payload;
    }

    private function verifiedStripeEvent(string $payload, ?string $signature, ?string $webhookSecret): object
    {
        if (! $webhookSecret || ! $signature) {
            Log::warning('Stripe webhook rejected: missing signing secret or signature.', [
                'has_secret' => (bool) $webhookSecret,
                'has_signature' => (bool) $signature,
                'environment' => app()->environment(),
            ]);

            throw new HttpResponseException(response()->json(['received' => false], 400));
        }

        try {
            return Webhook::constructEvent($payload, $signature, $webhookSecret);
        } catch (SignatureVerificationException|\UnexpectedValueException $e) {
            Log::warning('Stripe webhook rejected: signature verification failed.', [
                'message' => $e->getMessage(),
                'environment' => app()->environment(),
            ]);

            throw new HttpResponseException(response()->json(['received' => false], 400));
        }
    }

    private function toArraySafe(mixed $value): array
    {
        $normalized = $this->normalizeValue($value);

        return is_array($normalized) ? $normalized : [];
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizeValue($item), $value);
        }

        if (is_object($value)) {
            if (method_exists($value, 'toArray')) {
                return $this->normalizeValue($value->toArray());
            }

            if ($value instanceof \JsonSerializable) {
                return $this->normalizeValue($value->jsonSerialize());
            }

            if ($value instanceof \Traversable) {
                return $this->normalizeValue(iterator_to_array($value));
            }

            return $this->normalizeValue(get_object_vars($value));
        }

        return $value;
    }

    private function value(mixed $source, string $key, mixed $default = null): mixed
    {
        if (is_array($source)) {
            return $source[$key] ?? $default;
        }

        if (is_object($source) && isset($source->{$key})) {
            return $source->{$key};
        }

        return $default;
    }

    /**
     * Show payment page
     */
    public function show(Booking $booking)
    {
        abort_if($booking->user_id !== auth()->id(), 403);

        $booking = $this->securityDeposits->normalizeSecurityDepositState($booking);

        $invoice = $this->invoices->firstOrCreateForPaymentPage($booking);
        $invoice->loadMissing('payments');

        $remainingAmount = max(0, $this->amountToStripeMinorUnits(
            (float) $invoice->total_amount - (float) $invoice->paid_amount
        ));
        $amountToPay = $remainingAmount / 100;
        $rentalPaymentAlreadySettled = $remainingAmount <= 0;

        $securityDepositAmount = $this->securityDeposits->getExpectedSecurityDepositAmount($booking);
        $rentalIntent = null;

        if ($rentalPaymentAlreadySettled) {
            \Log::info('Skipping rental PaymentIntent creation: booking already fully paid', [
                'booking_id' => $booking->id,
                'invoice_id' => $invoice->id,
            ]);
        } else {

            // ── Rental Intent: نعيد الاستخدام إذا موجود ──
            if ($booking->rental_payment_intent_id) {
                try {
                    $rentalIntent = $this->stripePaymentIntents->retrieve($booking->rental_payment_intent_id);
                    // ila succeeded deja → redirect للـ success
                    if ($rentalIntent->status === 'succeeded') {
                        return redirect()->route('bookings.success', $booking);
                    }
                } catch (Exception $e) {
                    $booking->update(['rental_payment_intent_id' => null]);
                    $rentalIntent = null;
                }
            }

            // نخلقو جديد فقط إذا مكاينش
            if (empty($rentalIntent) || ! isset($rentalIntent)) {
                $rentalIntent = $this->stripePaymentIntents->create([
                    'amount' => $remainingAmount,
                    'currency' => self::STRIPE_CURRENCY,
                    'metadata' => [
                        'booking_id' => (string) $booking->id,
                        'invoice_id' => (string) $invoice->id,
                        'type' => self::STRIPE_RENTAL_TYPE,
                    ],
                ]);
                $booking->update(['rental_payment_intent_id' => $rentalIntent->id]);
            }
        }

        // ── Security Deposit Intent: نعيد الاستخدام إذا موجود ──
        $securityDepositIntent = null;
        if ($this->securityDeposits->requiresSecurityDepositAuthorization($booking)) {
            if ($booking->security_deposit_intent_id) {
                try {
                    $securityDepositIntent = $this->stripePaymentIntents->retrieve($booking->security_deposit_intent_id);
                    // ila cancelled wla succeeded → نخلقو جديد
                    if (in_array($securityDepositIntent->status, ['canceled', 'succeeded'])) {
                        $securityDepositIntent = null;
                        $booking->update(['security_deposit_intent_id' => null]);
                    }
                } catch (Exception $e) {
                    $booking->update(['security_deposit_intent_id' => null]);
                    $securityDepositIntent = null;
                }
            }

            if (! $securityDepositIntent) {
                $securityDepositIntent = $this->stripePaymentIntents->create([
                    'amount' => $this->amountToStripeMinorUnits($securityDepositAmount),
                    'currency' => self::STRIPE_CURRENCY,
                    'capture_method' => 'manual',
                    'confirmation_method' => 'automatic',
                    'metadata' => [
                        'booking_id' => (string) $booking->id,
                        'type' => self::STRIPE_SECURITY_DEPOSIT_TYPE,
                    ],
                ]);
                $booking->update([
                    'security_deposit_amount' => $securityDepositAmount,
                    'security_deposit_intent_id' => $securityDepositIntent->id,
                ]);
            }
        }

        return view('payments.index', compact(
            'booking',
            'invoice',
            'amountToPay',
            'securityDepositAmount',
            'rentalIntent',
            'securityDepositIntent',
            'rentalPaymentAlreadySettled'
        ));
    }

    /**
     * Process payment
     */
    public function store(Request $request, Booking $booking)
    {
        abort_if($booking->user_id !== auth()->id(), 403);

        $data = $request->validate([
            'payment_method' => 'required|in:card,cash',
            'rental_payment_intent' => 'required_if:payment_method,card',
            'security_deposit_intent' => 'nullable|string',
        ]);

        $booking = $this->securityDeposits->normalizeSecurityDepositState($booking);
        $requiresSecurityDeposit = $this->securityDeposits->requiresSecurityDepositAuthorization($booking);

        if ($data['payment_method'] === 'card' && $requiresSecurityDeposit && ! $request->filled('security_deposit_intent')) {
            return back()->withErrors(['payment' => 'Security deposit authorization was not completed. Please try again.']);
        }

        if ($data['payment_method'] === 'cash') {
            $result = $this->payments->recordPendingCashRequestForBooking(
                $booking,
                (int) auth()->id(),
                'Cash on delivery'
            );

            $booking = $result['booking'];

            if ($result['created']) {
                try {
                    Mail::to($booking->user->email)
                        ->send(new BookingPendingMail($booking));
                } catch (\Throwable $e) {
                    Log::warning('Booking pending cash email failed.', [
                        'booking_id' => $booking->id,
                        'user_id' => $booking->user_id,
                        'mailable' => BookingPendingMail::class,
                        'exception' => $e::class,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            $message = match (true) {
                $result['already_paid'] => 'This booking is already paid. No cash payment request was created.',
                $result['already_pending'] => 'Cash payment request already exists. Please visit our agency within 24 hours.',
                default => 'Booking created! Please visit our agency within 24 hours.',
            };

            return redirect()
                ->route('bookings.success', $booking)
                ->with('success', $message);
        }

        $expectedRentalIntentId = $this->requireStoredPaymentIntentId(
            $booking,
            'rental_payment_intent_id',
            $data['rental_payment_intent'] ?? null,
            self::STRIPE_RENTAL_TYPE
        );

        // ── Ensure invoice exists ──
        $invoice = $this->invoices->firstOrCreateForPaymentProcessing($booking);

        if ($data['payment_method'] === 'card') {
            try {
                // ── 1. Confirm rental payment (charges immediately) ──
                $rentalIntent = $this->stripePaymentIntents->retrieve($expectedRentalIntentId);

                $this->verifyPaymentIntent(
                    $rentalIntent,
                    $booking,
                    $invoice,
                    self::STRIPE_RENTAL_TYPE,
                    $expectedRentalIntentId,
                    'succeeded',
                    $this->expectedRentalPaymentAmount($invoice, $expectedRentalIntentId)
                );

                \Log::info('Stripe rental intent status', [
                    'booking_id' => $booking->id,
                    'payment_intent_id' => $rentalIntent->id,
                    'status' => $rentalIntent->status,
                ]);

                // ── 2. Confirm security deposit authorization (blocks, doesn't charge) ──
                // Webhook is the source of truth for security deposits.
                if ($request->filled('security_deposit_intent')) {
                    $expectedDepositIntentId = $this->requireStoredPaymentIntentId(
                        $booking,
                        'security_deposit_intent_id',
                        $data['security_deposit_intent'] ?? null,
                        self::STRIPE_SECURITY_DEPOSIT_TYPE
                    );
                    $securityDepositIntent = $this->stripePaymentIntents->retrieve($expectedDepositIntentId);

                    \Log::info('Stripe security deposit intent status', [
                        'booking_id' => $booking->id,
                        'payment_intent_id' => $securityDepositIntent->id,
                        'status' => $securityDepositIntent->status,
                    ]);

                    $this->verifyPaymentIntent(
                        $securityDepositIntent,
                        $booking,
                        $invoice,
                        self::STRIPE_SECURITY_DEPOSIT_TYPE,
                        $expectedDepositIntentId,
                        'requires_capture',
                        $this->amountToStripeMinorUnits($this->securityDeposits->getExpectedSecurityDepositAmount($booking))
                    );

                    if ($securityDepositIntent->status === 'requires_capture') {
                        // ✅ Security deposit is blocked on card — don't capture yet
                        $this->securityDeposits->syncFromStripeIntent(
                            $securityDepositIntent,
                            'payment_intent.amount_capturable_updated',
                            'controller_security_deposit_'.$securityDepositIntent->id
                        );

                        \Log::info('security deposit verified in store; webhook will persist hold', [
                            'booking_id' => $booking->id,
                            'payment_intent_id' => $securityDepositIntent->id,
                            'status' => $securityDepositIntent->status,
                        ]);
                    } else {
                        \Log::warning('security deposit not held properly', [
                            'booking_id' => $booking->id,
                            'payment_intent_id' => $securityDepositIntent->id,
                            'status' => $securityDepositIntent->status,
                        ]);

                        return back()->withErrors(['payment' => 'Security deposit authorization was not completed. Please try again.']);
                    }
                }

                return redirect()
                    ->route('bookings.success', $booking)
                    ->with('success', 'Payment submitted. We are confirming it with Stripe.');

            } catch (HttpResponseException $e) {
                throw $e;
            } catch (CardException $e) {
                return back()->withErrors(['payment' => 'Card declined: '.$e->getMessage()]);
            } catch (Exception $e) {
                \Log::error('Payment error: '.$e->getMessage());

                return back()->withErrors(['payment' => 'Payment failed. Please try again.']);
            }
        }

    }

    private function requireStoredPaymentIntentId(Booking $booking, string $field, ?string $suppliedIntentId, string $type): string
    {
        $expectedIntentId = trim((string) $booking->getAttribute($field));
        $suppliedIntentId = trim((string) $suppliedIntentId);

        if ($expectedIntentId === '' || $suppliedIntentId === '' || $expectedIntentId !== $suppliedIntentId) {
            $this->rejectPaymentIntentVerification('Stripe PaymentIntent ID mismatch in controller.', [
                'booking_id' => $booking->id,
                'payment_type' => $type,
                'stored_field' => $field,
                'expected_intent_id' => $expectedIntentId ?: null,
                'supplied_intent_id' => $suppliedIntentId ?: null,
            ]);
        }

        return $expectedIntentId;
    }

    private function verifyPaymentIntent(
        object $paymentIntent,
        Booking $booking,
        Invoice $invoice,
        string $expectedType,
        string $expectedIntentId,
        string $expectedStatus,
        int $expectedAmount
    ): void {
        $actualIntentId = trim((string) $this->value($paymentIntent, 'id', ''));
        $actualStatus = trim((string) $this->value($paymentIntent, 'status', ''));
        $actualCurrency = strtolower(trim((string) $this->value($paymentIntent, 'currency', '')));
        $actualAmount = $this->value($paymentIntent, 'amount');
        $actualAmountReceived = $this->value($paymentIntent, 'amount_received');
        $metadata = $this->value($paymentIntent, 'metadata', []);
        $metadataBookingId = trim((string) $this->value($metadata, 'booking_id', ''));
        $metadataType = trim((string) $this->value($metadata, 'type', ''));

        if ($actualIntentId === '' || $actualIntentId !== $expectedIntentId) {
            $this->rejectPaymentIntentVerification('Stripe PaymentIntent retrieved ID mismatch in controller.', [
                'booking_id' => $booking->id,
                'invoice_id' => $invoice->id,
                'payment_type' => $expectedType,
                'expected_intent_id' => $expectedIntentId,
                'actual_intent_id' => $actualIntentId ?: null,
            ]);
        }

        if ($actualStatus !== $expectedStatus) {
            $this->rejectPaymentIntentVerification('Stripe PaymentIntent status mismatch in controller.', [
                'booking_id' => $booking->id,
                'invoice_id' => $invoice->id,
                'payment_type' => $expectedType,
                'payment_intent_id' => $expectedIntentId,
                'expected_status' => $expectedStatus,
                'actual_status' => $actualStatus ?: null,
            ]);
        }

        if ($actualCurrency !== self::STRIPE_CURRENCY) {
            $this->rejectPaymentIntentVerification('Stripe PaymentIntent currency mismatch in controller.', [
                'booking_id' => $booking->id,
                'invoice_id' => $invoice->id,
                'payment_type' => $expectedType,
                'payment_intent_id' => $expectedIntentId,
                'expected_currency' => self::STRIPE_CURRENCY,
                'actual_currency' => $actualCurrency ?: null,
            ]);
        }

        if (! is_numeric($actualAmount) || (int) $actualAmount !== $expectedAmount) {
            $this->rejectPaymentIntentVerification('Stripe PaymentIntent amount mismatch in controller.', [
                'booking_id' => $booking->id,
                'invoice_id' => $invoice->id,
                'payment_type' => $expectedType,
                'payment_intent_id' => $expectedIntentId,
                'expected_amount' => $expectedAmount,
                'actual_amount' => is_numeric($actualAmount) ? (int) $actualAmount : null,
            ]);
        }

        if ($expectedStatus === 'succeeded' && $actualAmountReceived !== null && (! is_numeric($actualAmountReceived) || (int) $actualAmountReceived !== $expectedAmount)) {
            $this->rejectPaymentIntentVerification('Stripe PaymentIntent received amount mismatch in controller.', [
                'booking_id' => $booking->id,
                'invoice_id' => $invoice->id,
                'payment_type' => $expectedType,
                'payment_intent_id' => $expectedIntentId,
                'expected_amount_received' => $expectedAmount,
                'actual_amount_received' => is_numeric($actualAmountReceived) ? (int) $actualAmountReceived : null,
            ]);
        }

        if ($metadataBookingId === '' || $metadataBookingId !== (string) $booking->id || $metadataType === '' || $metadataType !== $expectedType) {
            $this->rejectPaymentIntentVerification('Stripe PaymentIntent metadata mismatch in controller.', [
                'booking_id' => $booking->id,
                'invoice_id' => $invoice->id,
                'payment_type' => $expectedType,
                'payment_intent_id' => $expectedIntentId,
                'metadata_booking_id' => $metadataBookingId ?: null,
                'metadata_type' => $metadataType ?: null,
            ]);
        }
    }

    private function expectedRentalPaymentAmount(Invoice $invoice, string $paymentIntentId): int
    {
        $existingPayment = Payment::where('invoice_id', $invoice->id)
            ->where('transaction_id', $paymentIntentId)
            ->where('type', Payment::TYPE_PAYMENT)
            ->where('status', Payment::STATUS_COMPLETED)
            ->first();

        if ($existingPayment) {
            return $this->amountToStripeMinorUnits((float) $existingPayment->amount);
        }

        return $this->amountToStripeMinorUnits((float) $invoice->balance);
    }

    private function amountToStripeMinorUnits(float $amount): int
    {
        return max(0, (int) round($amount * 100));
    }

    private function rejectPaymentIntentVerification(string $message, array $context): never
    {
        Log::warning($message, $context);

        throw new HttpResponseException(
            back()->withErrors(['payment' => 'Payment verification failed. Please try again.'])
        );
    }

    // ══════════════════════════════════════════
    // ADMIN: Release or charge security deposit
    // ══════════════════════════════════════════

    /**
     * Record an admin-side cash payment for a booking.
     */
    public function processCash(Request $request, Booking $booking)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $result = $this->payments->recordRemainingCashPaymentForBooking(
            $booking,
            (int) auth()->id(),
            'Cash payment recorded by admin'
        );

        if ($result['already_paid']) {
            return back()->with('success', 'Invoice is already fully paid. No additional cash payment was recorded.');
        }

        $payment = $result['payment'];
        $invoice = $result['invoice'];
        $booking = $result['booking'];
        $amount = $result['amount'];

        // ✅ CREATE NOTIFICATION FOR CASH PAYMENT
        $this->notificationService->create(
            $booking->user_id,
            'payment_success',
            __('messages.notification_payment_success'),
            __('messages.notification_payment_success_message', [
                'amount' => number_format($amount, 2),
                'currency' => 'MAD',
            ]),
            [
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'currency' => 'MAD',
                'payment_method' => 'cash',
                'transaction_id' => $payment->transaction_id,
            ]
        );

        return back()->with('success', 'Cash payment recorded successfully.');
    }

    /**
     * Release security deposit (car returned OK)
     */
    public function releaseSecurityDeposit(Booking $booking)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        try {
            $intent = $this->securityDeposits->releaseSecurityDeposit($booking);

            return response()->json([
                'success' => true,
                'message' => 'Security deposit refund/release requested. Stripe webhook will update the booking.',
                'status' => $intent->status,
            ]);
        } catch (\Exception $e) {
            Log::error('SECURITY DEPOSIT RELEASE REQUEST ERROR', [
                'message' => $e->getMessage(),
                'exception' => $e,
                'booking_id' => $booking->id,
                'intent_id' => $booking->security_deposit_intent_id,
                'action' => 'release',
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unable to release this security deposit. Please try again or review the logs.',
            ], 500);
        }
    }

    /**
     * Capture the full security deposit, then refund everything except the penalty.
     */
    public function chargeSecurityDeposit(Request $request, Booking $booking)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $booking = $this->securityDeposits->normalizeSecurityDepositState($booking);
        $maxPenalty = $this->securityDeposits->getExpectedSecurityDepositAmount($booking);

        $request->validate([
            'penalty_amount' => 'required|numeric|min:0|max:'.$maxPenalty,
            'penalty_reason' => 'nullable|string|in:Late return,Damage,Cleaning fee,Other',
        ]);

        try {
            $this->securityDeposits->captureSecurityDeposit(
                $booking,
                (float) $request->penalty_amount,
                $request->input('penalty_reason')
            );

            return back()->with('success', 'Full security deposit captured. The penalty was kept and the remaining deposit was refunded.');

        } catch (Exception $e) {
            Log::error('Security deposit capture request failed.', [
                'exception' => $e,
                'booking_id' => $booking->id,
                'intent_id' => $booking->security_deposit_intent_id,
            ]);

            return back()->withErrors([
                'security_deposit' => 'Unable to capture this security deposit. Please try again or review the logs.',
            ]);
        }
    }

    public function retrySecurityDepositRefund(Booking $booking)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        try {
            $this->securityDeposits->retrySecurityDepositRefund($booking);

            return back()->with('success', 'Security deposit refund retried successfully.');
        } catch (Exception $e) {
            Log::error('Security deposit refund retry failed.', [
                'exception' => $e,
                'booking_id' => $booking->id,
                'intent_id' => $booking->security_deposit_intent_id,
            ]);

            return back()->withErrors([
                'security_deposit' => 'Unable to retry this security deposit refund. Please try again or review the logs.',
            ]);
        }
    }
}

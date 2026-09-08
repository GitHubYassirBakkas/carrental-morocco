<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Jobs\ProcessStripeWebhookEventJob;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Exception\CardException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Exception;
use App\Mail\BookingConfirmedMail;
use App\Mail\BookingPendingMail;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\PaymentWebhookMetrics;
use App\Services\SecurityDepositService;
use App\Services\StripePaymentIntentGateway;
use App\Models\PaymentIdempotencyKey;
use App\Models\StripeWebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


class PaymentController extends Controller
{
    public function __construct(
        private readonly StripePaymentIntentGateway $stripePaymentIntents,
        private readonly SecurityDepositService $securityDeposits,
        private readonly InvoiceService $invoices,
        private readonly PaymentService $payments
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

            if (!$eventId) {
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

            Log::info('payment.webhook.lifecycle', [
                'event_type' => $eventType,
                'booking_id' => $bookingId,
                'payment_intent_id' => $paymentIntentId,
                'status' => $paymentIntentStatus,
            ]);

            if (PaymentIdempotencyKey::where('idempotency_key', $eventId)
                ->where('status', PaymentIdempotencyKey::STATUS_COMPLETED)
                ->exists()) {
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
                app(PaymentWebhookMetrics::class)->duplicate([
                    'event_id' => $eventId,
                    'booking_id' => $bookingId ? (int) $bookingId : null,
                    'payment_intent_id' => $paymentIntentId,
                    'lock_status' => 'not_required',
                    'idempotency_status' => 'event_store_processed',
                ]);

                return response()->json(['received' => true], 200);
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
        if (!$webhookSecret || !$signature) {
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

    $remainingAmount = max(
        0,
        (int) round(((float) $invoice->total_amount - (float) $invoice->paid_amount) * 100)
    );
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
    if (empty($rentalIntent) || !isset($rentalIntent)) {
        $rentalIntent = $this->stripePaymentIntents->create([
            'amount'   => $remainingAmount,
            'currency' => 'mad',
            'metadata' => [
                'booking_id' => (string) $booking->id,
                'invoice_id' => (string) $invoice->id,
                'type'       => 'rental',
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

        if (!$securityDepositIntent) {
            $securityDepositIntent = $this->stripePaymentIntents->create([
                'amount'              => (int)($securityDepositAmount * 100),
                'currency'            => 'mad',
                'capture_method'      => 'manual',
                'confirmation_method' => 'automatic',
                'metadata'            => [
                    'booking_id' => (string) $booking->id,
                    'type'       => 'security_deposit',
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
            'payment_method'         => 'required|in:card,cash',
            'rental_payment_intent'  => 'required_if:payment_method,card',
            'security_deposit_intent' => 'nullable|string',
        ]);

        $booking = $this->securityDeposits->normalizeSecurityDepositState($booking);
        $requiresSecurityDeposit = $this->securityDeposits->requiresSecurityDepositAuthorization($booking);

        if ($data['payment_method'] === 'card' && $requiresSecurityDeposit && !$request->filled('security_deposit_intent')) {
            return back()->withErrors(['payment' => 'Security deposit authorization was not completed. Please try again.']);
        }

        // ── Ensure invoice exists ──
        $invoice = $this->invoices->firstOrCreateForPaymentProcessing($booking);

        $isPaid      = false;
        $transactionId = null;
        $paymentAmount = $booking->total_amount;

        if ($data['payment_method'] === 'card') {
            try {
                // ── 1. Confirm rental payment (charges immediately) ──
                $rentalIntent = $this->stripePaymentIntents->retrieve($data['rental_payment_intent']);

                if ($rentalIntent->status !== 'succeeded') {
                    return back()->withErrors(['payment' => 'Rental payment not completed. Please try again.']);
                }

                if (($rentalIntent->metadata->type ?? null) !== 'rental' || (string) ($rentalIntent->metadata->booking_id ?? $booking->id) !== (string) $booking->id) {
                    \Log::warning('Stripe rental intent metadata mismatch in controller', [
                        'booking_id' => $booking->id,
                        'payment_intent_id' => $rentalIntent->id,
                        'status' => $rentalIntent->status,
                    ]);

                    return back()->withErrors(['payment' => 'Payment verification failed. Please try again.']);
                }

                \Log::info('Stripe rental intent status', [
                    'booking_id' => $booking->id,
                    'payment_intent_id' => $rentalIntent->id,
                    'status' => $rentalIntent->status,
                ]);

                // ── 2. Confirm security deposit authorization (blocks, doesn't charge) ──
                // Webhook is the source of truth for security deposits.
                if ($request->filled('security_deposit_intent')) {
                    $securityDepositIntent = $this->stripePaymentIntents->retrieve($data['security_deposit_intent']);

                    \Log::info('Stripe security deposit intent status', [
                        'booking_id' => $booking->id,
                        'payment_intent_id' => $securityDepositIntent->id,
                        'status' => $securityDepositIntent->status,
                    ]);

                    if (($securityDepositIntent->metadata->type ?? null) !== 'security_deposit' || (string) ($securityDepositIntent->metadata->booking_id ?? $booking->id) !== (string) $booking->id) {
                        \Log::warning('Stripe security deposit intent metadata mismatch in controller', [
                            'booking_id' => $booking->id,
                            'payment_intent_id' => $securityDepositIntent->id,
                            'status' => $securityDepositIntent->status,
                        ]);

                        return back()->withErrors(['payment' => 'Security deposit verification failed. Please try again.']);
                    }

                    if ($securityDepositIntent->status === 'requires_capture') {
                        // ✅ Security deposit is blocked on card — don't capture yet
                        if (empty($booking->security_deposit_intent_id)) {
                            $booking->update(['security_deposit_intent_id' => $securityDepositIntent->id]);
                        }

                        $this->securityDeposits->syncFromStripeIntent(
                            $securityDepositIntent,
                            'payment_intent.amount_capturable_updated',
                            'controller_security_deposit_' . $securityDepositIntent->id
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

            } catch (CardException $e) {
                return back()->withErrors(['payment' => 'Card declined: ' . $e->getMessage()]);
            } catch (Exception $e) {
                \Log::error('Payment error: ' . $e->getMessage());
                return back()->withErrors(['payment' => 'Payment failed. Please try again.']);
            }
        }

        // ── Create payment record ──
        if (
            !$transactionId ||
            !$invoice->payments()->where('transaction_id', $transactionId)->exists()
        ) {
            if ($isPaid) {
                $this->payments->recordCompletedPayment(
                    $invoice,
                    auth()->id(),
                    $paymentAmount,
                    $data['payment_method'],
                    $transactionId,
                    "Stripe PI: $transactionId"
                );
            } else {
                $this->payments->recordPendingPayment(
                    $invoice,
                    auth()->id(),
                    $paymentAmount,
                    $data['payment_method'],
                    $transactionId,
                    'Cash on delivery'
                );
            }
        }

        // ── Update invoice ──
        $this->payments->updateInvoiceStatus($invoice);

        // ── Update booking ──
        if ($isPaid && $rentalIntent->status === 'succeeded') {
            $this->payments->confirmBookingAfterRentalPayment($booking);
        } elseif ($booking->isPending()) {
            $this->payments->markBookingPendingPayment($booking);
        }

        // ── Send Email ──
        if ($isPaid) {
            Mail::to($booking->user->email)
                ->send(new BookingConfirmedMail($booking));
        } else {
            Mail::to($booking->user->email)
                ->send(new BookingPendingMail($booking));
        }

        return redirect()
            ->route('bookings.success', $booking)
            ->with('success', $isPaid
                ? 'Payment successful!'
                : 'Booking created! Please visit our agency within 24 hours.'
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

        $invoice = $this->invoices->firstOrCreateForPaymentProcessing($booking);

        $this->payments->recordCompletedPayment(
            $invoice,
            auth()->id(),
            $booking->total_amount,
            'cash',
            null,
            'Cash payment recorded by admin'
        );

        $this->payments->updateInvoiceStatus($invoice);
        $this->payments->markBookingPaidByCash($booking);

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
                'booking_id' => $booking->id,
                'intent_id' => $booking->security_deposit_intent_id,
                'action' => 'release',
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
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
            'penalty_amount' => 'required|numeric|min:0|max:' . $maxPenalty,
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
            return back()->withErrors(['security_deposit' => 'Failed to capture security deposit: ' . $e->getMessage()]);
        }
    }

    public function retrySecurityDepositRefund(Booking $booking)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        try {
            $this->securityDeposits->retrySecurityDepositRefund($booking);

            return back()->with('success', 'Security deposit refund retried successfully.');
        } catch (Exception $e) {
            return back()->withErrors(['security_deposit' => 'Failed to retry security deposit refund: ' . $e->getMessage()]);
        }
    }
}

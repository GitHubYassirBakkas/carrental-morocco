<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\CardException;
use Exception;
use App\Mail\BookingConfirmedMail;
use App\Mail\BookingPendingMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            if ($webhookSecret && $signature) {
                $event = \Stripe\Webhook::constructEvent($payload, $signature, $webhookSecret);
            } else {
                \Log::warning('Stripe webhook secret or signature missing.');
                $event = json_decode($payload);
            }

            $object = $event->data->object ?? null;
            $eventType = $event->type ?? 'unknown';
            $paymentIntentId = $object->payment_intent ?? $object->id ?? null;

            if (app()->environment('local')) {
                \Log::debug('FULL STRIPE EVENT', [
                    'payload' => method_exists($event, 'toArray')
                        ? $event->toArray()
                        : json_decode(json_encode($event), true),
                ]);
            }

            if (str_starts_with($eventType, 'charge.')) {
                \Log::info('Stripe webhook ignored charge event.', [
                    'event_type' => $eventType,
                    'payment_intent_id' => $paymentIntentId,
                ]);

                return response()->json(['received' => true], 200);
            }

            $metadata = [];
            if ($object && isset($object->metadata)) {
                $metadata = method_exists($object->metadata, 'toArray')
                    ? $object->metadata->toArray()
                    : (array) $object->metadata;
            }

            $bookingId = $metadata['booking_id'] ?? null;
            $metadataType = $metadata['type'] ?? null;
            $paymentIntentStatus = $object->status ?? null;

            \Log::info('Stripe webhook received', [
                'event_type' => $eventType,
                'payment_intent_id' => $paymentIntentId,
                'booking_id' => $bookingId,
                'payment_intent_status' => $paymentIntentStatus,
                'type' => $metadataType,
                'metadata' => $metadata,
            ]);

            if (in_array($eventType, ['payment_intent.amount_capturable_updated', 'payment_intent.canceled'], true)) {
                // Webhook is the ONLY source of truth for deposit
                // Never update deposit from controllers or store()
                if ($metadataType !== 'deposit' || !$bookingId) {
                    \Log::error('invalid deposit metadata', [
                        'event_type' => $eventType,
                        'payment_intent_id' => $paymentIntentId,
                        'metadata' => $metadata,
                    ]);

                    return response()->json(['received' => true], 200);
                }

                $booking = Booking::find($bookingId);

                if (!$booking) {
                    \Log::error('booking not found for deposit webhook', [
                        'booking_id' => $bookingId,
                        'payment_intent_id' => $paymentIntentId,
                    ]);

                    return response()->json(['received' => true], 200);
                }

                \Log::info('DEPOSIT WEBHOOK INPUT', [
                    'booking_id' => $bookingId,
                    'intent_id' => $paymentIntentId,
                    'status' => $paymentIntentStatus,
                    'event' => $eventType,
                ]);

                if ($eventType === 'payment_intent.amount_capturable_updated') {
                    if ($booking->deposit_status === 'held') {
                        \Log::info('webhook deposit already handled', [
                            'event_type' => $eventType,
                            'payment_intent_id' => $paymentIntentId,
                            'booking_id' => $bookingId,
                            'payment_intent_status' => $paymentIntentStatus,
                        ]);

                        return response()->json(['received' => true], 200);
                    }

                    \DB::flushQueryLog();
                    \DB::enableQueryLog();

                    $updated = $booking->update([
                        'deposit_payment_intent_id' => $paymentIntentId,
                        'deposit_status' => 'held',
                        'deposit_paid' => true,
                        'deposit_paid_at' => $booking->deposit_paid_at ?? now(),
                    ]);

                    $queries = \DB::getQueryLog();
                    \DB::disableQueryLog();
                    $freshBooking = $booking->fresh();

                    if ($updated === false) {
                        \Log::error('deposit update failed', [
                            'booking_id' => $bookingId,
                            'payment_intent_id' => $paymentIntentId,
                            'sql' => $queries,
                            'booking_state' => $booking->toArray(),
                        ]);

                        return response()->json(['received' => true], 200);
                    }

                    if (
                        !$freshBooking ||
                        $freshBooking->deposit_payment_intent_id !== $paymentIntentId ||
                        $freshBooking->deposit_status !== 'held' ||
                        !$freshBooking->deposit_paid
                    ) {
                        \Log::error('deposit update verification failed', [
                            'booking_id' => $bookingId,
                            'payment_intent_id' => $paymentIntentId,
                            'sql' => $queries,
                            'booking_state' => $freshBooking?->toArray(),
                        ]);
                    }

                    \Log::info('DEPOSIT BOOKING AFTER UPDATE', [
                        'booking' => $freshBooking?->toArray(),
                    ]);

                    return response()->json(['received' => true], 200);
                }

                \DB::flushQueryLog();
                \DB::enableQueryLog();

                $updated = $booking->update([
                    'deposit_status' => 'released',
                    'deposit_paid' => false,
                ]);

                $queries = \DB::getQueryLog();
                \DB::disableQueryLog();

                if ($updated === false) {
                    \Log::error('deposit release update failed', [
                        'booking_id' => $bookingId,
                        'payment_intent_id' => $paymentIntentId,
                        'sql' => $queries,
                        'booking_state' => $booking->toArray(),
                    ]);

                    return response()->json(['received' => true], 200);
                }

                \Log::info('webhook deposit released', [
                    'event_type' => $eventType,
                    'payment_intent_id' => $paymentIntentId,
                    'booking_id' => $bookingId,
                    'payment_intent_status' => $paymentIntentStatus,
                ]);

                \Log::info('DEPOSIT BOOKING AFTER UPDATE', [
                    'booking' => $booking->fresh()?->toArray(),
                ]);

                return response()->json(['received' => true], 200);
            }

            if (str_starts_with($eventType, 'payment_intent.') && $eventType !== 'payment_intent.succeeded') {
                \Log::info('Stripe webhook ignored unrelated payment intent event.', [
                    'event_type' => $eventType,
                    'payment_intent_id' => $paymentIntentId,
                    'booking_id' => $bookingId,
                    'type' => $metadataType,
                    'payment_intent_status' => $paymentIntentStatus,
                ]);

                return response()->json(['received' => true], 200);
            }

            // Ignore non-rental success events so deposit never enters invoice/payment logic.
            if ($eventType === 'payment_intent.succeeded' && $metadataType !== 'rental') {
                \Log::info('Stripe webhook ignored non-rental success event.', [
                    'payment_intent_id' => $paymentIntentId,
                    'booking_id' => $bookingId,
                    'type' => $metadataType,
                    'payment_intent_status' => $paymentIntentStatus,
                ]);

                return response()->json(['received' => true], 200);
            }

            if ($eventType === 'payment_intent.succeeded' && $paymentIntentId && $bookingId && $metadataType === 'rental') {
                $booking = Booking::find($bookingId);

                if (!$booking) {
                    \Log::warning('Stripe webhook booking not found.', [
                        'payment_intent_id' => $paymentIntentId,
                        'booking_id' => $bookingId,
                    ]);

                    return response()->json(['received' => true], 200);
                }

                if (Payment::where('transaction_id', $paymentIntentId)->exists()) {
                    \Log::info('Stripe webhook already processed.', [
                        'payment_intent_id' => $paymentIntentId,
                        'booking_id' => $bookingId,
                    ]);

                    return response()->json(['received' => true], 200);
                }

                $invoice = $booking->invoice ?? Invoice::create([
                    'booking_id' => (string) $booking->id,
                    'user_id' => $booking->user_id,
                    'subtotal' => $booking->total_amount,
                    'tax_amount' => 0,
                    'total_amount' => $booking->total_amount,
                    'status' => 'pending',
                    'issued_at' => now(),
                ]);

                $invoice->payments()->create([
                    'user_id' => $booking->user_id,
                    'amount' => $booking->total_amount,
                    'method' => 'card',
                    'type' => 'payment',
                    'status' => 'completed',
                    'paid_at' => now(),
                    'transaction_id' => $paymentIntentId,
                    'notes' => 'Stripe webhook payment confirmation',
                ]);

                $invoice->update([
                    'status' => 'paid',
                ]);

                $booking->update([
                    'payment_method' => 'stripe',
                    'status' => 'confirmed',
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('Stripe webhook handling failed', [
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json(['received' => true], 200);
    }

    /**
     * Show payment page
     */
    public function show(Booking $booking)
    {
        abort_if($booking->user_id !== auth()->id(), 403);

    $invoice     = $booking->invoice;
    $amountToPay = $invoice
        ? ($invoice->total_amount - ($invoice->paid_amount ?? 0))
        : $booking->total_amount;

    $depositAmount = $booking->car->deposit_amount ?? 0;
    $rentalIntent = null;

    // ── Rental Intent: نعيد الاستخدام إذا موجود ──
    if ($booking->rental_payment_intent_id) {
        try {
            $rentalIntent = PaymentIntent::retrieve($booking->rental_payment_intent_id);
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
        $rentalIntent = PaymentIntent::create([
            'amount'   => (int)($amountToPay * 100),
            'currency' => 'mad',
            'metadata' => [
                'booking_id' => (string) $booking->id,
                'type'       => 'rental',
            ],
        ]);
        $booking->update(['rental_payment_intent_id' => $rentalIntent->id]);
    }

    // ── Deposit Intent: نعيد الاستخدام إذا موجود ──
    $depositIntent = null;
    if ($depositAmount > 0) {
        if ($booking->deposit_payment_intent_id) {
            try {
                $depositIntent = PaymentIntent::retrieve($booking->deposit_payment_intent_id);
                // ila cancelled wla succeeded → نخلقو جديد
                if (in_array($depositIntent->status, ['canceled', 'succeeded'])) {
                    $depositIntent = null;
                    $booking->update(['deposit_payment_intent_id' => null]);
                }
            } catch (Exception $e) {
                $booking->update(['deposit_payment_intent_id' => null]);
                $depositIntent = null;
            }
        }

        if (!$depositIntent) {
            $depositIntent = PaymentIntent::create([
                'amount'              => (int)($depositAmount * 100),
                'currency'            => 'mad',
                'capture_method'      => 'manual',
                'confirmation_method' => 'automatic',
                'metadata'            => [
                    'booking_id' => (string) $booking->id,
                    'type'       => 'deposit',
                ],
            ]);
            $booking->update(['deposit_payment_intent_id' => $depositIntent->id]);
        }
    }

    return view('payments.index', compact(
        'booking',
        'invoice',
        'amountToPay',
        'depositAmount',
        'rentalIntent',
        'depositIntent'
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
            'deposit_payment_intent' => 'nullable|string',
        ]);

        // ── Ensure invoice exists ──
        $invoice = $booking->invoice ?? Invoice::create([
            'booking_id'   => $booking->id,
            'user_id'      => $booking->user_id,
            'subtotal'     => $booking->total_amount,
            'tax_amount'   => 0,
            'total_amount' => $booking->total_amount,
            'status'       => 'pending',
            'issued_at'    => now(),
        ]);

        $isPaid      = false;
        $transactionId = null;

        if ($data['payment_method'] === 'card') {
            try {
                // ── 1. Confirm rental payment (charges immediately) ──
                $rentalIntent = PaymentIntent::retrieve($data['rental_payment_intent']);

                if ($rentalIntent->status !== 'succeeded') {
                    return back()->withErrors(['payment' => 'Rental payment not completed. Please try again.']);
                }

                $isPaid        = true;
                $transactionId = $rentalIntent->id;

                \Log::info('Stripe rental intent status', [
                    'booking_id' => $booking->id,
                    'payment_intent_id' => $rentalIntent->id,
                    'status' => $rentalIntent->status,
                    'type' => $rentalIntent->metadata->type ?? 'rental',
                ]);

                // ── 2. Confirm deposit authorization (blocks, doesn't charge) ──
                // Webhook is the ONLY source of truth for deposit.
                $depositIntentId = null;
                if ($request->filled('deposit_payment_intent')) {
                    $depositIntent = PaymentIntent::retrieve($data['deposit_payment_intent']);

                    \Log::info('Stripe deposit intent status', [
                        'booking_id' => $booking->id,
                        'payment_intent_id' => $depositIntent->id,
                        'status' => $depositIntent->status,
                        'type' => $depositIntent->metadata->type ?? 'deposit',
                    ]);

                    if ($depositIntent->status === 'requires_capture') {
                        // ✅ Deposit is blocked on card — don't capture yet
                        $depositIntentId = $depositIntent->id;

                        \Log::info('deposit accepted in store', [
                            'booking_id' => $booking->id,
                            'payment_intent_id' => $depositIntentId,
                            'status' => $depositIntent->status,
                            'type' => $depositIntent->metadata->type ?? 'deposit',
                        ]);
                    } else {
                        \Log::warning('deposit not held properly', [
                            'booking_id' => $booking->id,
                            'payment_intent_id' => $depositIntent->id,
                            'status' => $depositIntent->status,
                            'type' => $depositIntent->metadata->type ?? 'deposit',
                        ]);
                    }
                }

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
            $invoice->payments()->create([
                'user_id'        => auth()->id(),
                'amount'         => $booking->total_amount,
                'method'         => $data['payment_method'],
                'type'           => 'payment',
                'status'         => $isPaid ? 'completed' : 'pending',
                'paid_at'        => $isPaid ? now() : null,
                'transaction_id' => $transactionId,
                'notes'          => $isPaid ? "Stripe PI: $transactionId" : "Cash on delivery",
            ]);
        }

        // ── Update invoice ──
        $invoice->update($isPaid
            ? ['status' => 'paid', 'paid_amount' => $booking->total_amount]
            : ['status' => 'pending']
        );

        // ── Update booking ──
        $booking->update($isPaid ? [
            'payment_method'  => 'stripe',
            'status'          => 'confirmed',
            'deposit_due_at'  => null,
        ] : [
            'payment_method' => 'cash',
            'status'         => 'pending',
            'deposit_paid'   => false,
            'deposit_status' => 'pending',
            'deposit_due_at' => now()->addHours(24),
        ]);
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
    // ADMIN: Release or charge deposit
    // ══════════════════════════════════════════

    /**
     * Process Stripe payment through the existing payment flow.
     */
    public function processStripe(Request $request, Booking $booking)
    {
        $request->merge([
            'payment_method' => 'card',
        ]);

        return $this->store($request, $booking);
    }

    /**
     * Record an admin-side cash payment for a booking.
     */
    public function processCash(Request $request, Booking $booking)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $invoice = $booking->invoice ?? Invoice::create([
            'booking_id'   => $booking->id,
            'user_id'      => $booking->user_id,
            'subtotal'     => $booking->total_amount,
            'tax_amount'   => 0,
            'total_amount' => $booking->total_amount,
            'status'       => 'pending',
            'issued_at'    => now(),
        ]);

        $invoice->payments()->create([
            'user_id'        => auth()->id(),
            'amount'         => $booking->total_amount,
            'method'         => 'cash',
            'type'           => 'payment',
            'status'         => 'completed',
            'paid_at'        => now(),
            'transaction_id' => null,
            'notes'          => 'Cash payment recorded by admin',
        ]);

        $invoice->update([
            'status' => 'paid',
        ]);

        $booking->update([
            'payment_method' => 'cash',
            'status'         => 'confirmed',
            'deposit_due_at' => null,
        ]);

        return back()->with('success', 'Cash payment recorded successfully.');
    }

    /**
     * Release deposit (car returned OK)
     */
    public function releaseDeposit(Booking $booking)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        Log::info('STEP 1: entering releaseDeposit', [
            'booking_id' => $booking->id,
            'deposit_payment_intent_id' => $booking->deposit_payment_intent_id,
            'deposit_status' => $booking->deposit_status
        ]);

        if (request()->boolean('debug')) {
            return response()->json([
                'debug' => 'releaseDeposit method reached',
                'booking_id' => $booking->id,
                'deposit_payment_intent_id' => $booking->deposit_payment_intent_id,
                'deposit_status' => $booking->deposit_status,
            ]);
        }

        // Validation
        if (!$booking->deposit_payment_intent_id) {
            Log::error('STEP 2: validation failed - no payment intent id', [
                'booking_id' => $booking->id,
                'deposit_status' => $booking->deposit_status,
            ]);

            return response()->json(['success' => false, 'error' => 'No deposit PaymentIntent ID found'], 400);
        }

        if ($booking->deposit_status !== 'held') {
            Log::error('STEP 2: validation failed - wrong status', [
                'booking_id' => $booking->id,
                'current_status' => $booking->deposit_status,
                'expected_status' => 'held'
            ]);

            return response()->json(['success' => false, 'error' => 'Deposit not held. Status: ' . $booking->deposit_status], 400);
        }

        Log::info('STEP 2: validation passed', [
            'booking_id' => $booking->id,
            'payment_intent_id' => $booking->deposit_payment_intent_id
        ]);

        try {
            $intent = \Stripe\PaymentIntent::retrieve($booking->deposit_payment_intent_id);

            Log::info('STEP 3: intent retrieved', [
                'id' => $intent->id,
                'status' => $intent->status,
                'amount' => $intent->amount,
                'currency' => $intent->currency
            ]);

            if ($intent->status !== 'requires_capture') {
                Log::error('INVALID STATUS FOR CANCEL', [
                    'intent_id' => $intent->id,
                    'status' => $intent->status,
                    'required_status' => 'requires_capture',
                    'booking_id' => $booking->id,
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Cannot cancel PaymentIntent with status: ' . $intent->status,
                    'status' => $intent->status,
                ], 409);
            }

            Log::info('DEPOSIT CANCEL ATTEMPT', [
                'intent_id' => $intent->id,
                'status' => $intent->status,
                'amount' => $intent->amount,
                'booking_id' => $booking->id
            ]);

            $canceledIntent = $intent->cancel();

            Log::info('DEPOSIT CANCEL SUCCESS', [
                'intent_id' => $canceledIntent->id,
                'booking_id' => $booking->id,
                'final_status' => $canceledIntent->status
            ]);

            $booking->update([
                'deposit_status' => 'released',
                'deposit_paid' => false,
                'deposit_charged_amount' => 0,
            ]);

            return response()->json(['success' => true, 'message' => 'Deposit released successfully']);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('DEPOSIT CANCEL ERROR', [
                'message' => $e->getMessage(),
                'code' => $e->getStripeCode(),
                'type' => $e->getError()?->type,
                'http_status' => $e->getHttpStatus(),
                'booking_id' => $booking->id,
                'payment_intent_id' => $booking->deposit_payment_intent_id
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('DEPOSIT RELEASE ERROR', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'type' => get_class($e),
                'http_status' => null,
                'trace' => $e->getTraceAsString(),
                'booking_id' => $booking->id
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Charge deposit partially or fully (damage/late)
     */
    public function chargeDeposit(Request $request, Booking $booking)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $request->validate([
            'charge_amount' => 'required|numeric|min:1|max:' . ($booking->car->deposit_amount ?? 9999),
            'reason'        => 'required|string|max:255',
        ]);

        if (!$booking->deposit_payment_intent_id) {
            return back()->withErrors(['deposit' => 'No deposit intent found.']);
        }

        try {
            $intent = PaymentIntent::retrieve($booking->deposit_payment_intent_id);

            $chargeAmountCents = (int)($request->charge_amount * 100);
            $totalDepositCents = (int)(($booking->car->deposit_amount ?? 0) * 100);

            if ($intent->status === 'requires_capture') {

                if ($chargeAmountCents < $totalDepositCents) {
                    // Capture partial amount
                    $intent->capture(['amount_to_capture' => $chargeAmountCents]);

                    // Cancel remaining
                    // Note: Stripe cancels the rest automatically on partial capture
                } else {
                    // Capture full deposit
                    $intent->capture();
                }
            }

            $booking->update([
                'deposit_status'          => 'charged',
                'deposit_charged_amount'  => $request->charge_amount,
            ]);

            // Log the charge
            $booking->invoice?->payments()->create([
                'user_id'  => auth()->id(),
                'amount'   => $request->charge_amount,
                'method'   => 'stripe',
                'type'     => 'deposit_charge',
                'status'   => 'completed',
                'paid_at'  => now(),
                'notes'    => 'Deposit charge: ' . $request->reason,
            ]);

            return back()->with('success', 'Deposit of ' . $request->charge_amount . ' MAD charged. Reason: ' . $request->reason);

        } catch (Exception $e) {
            return back()->withErrors(['deposit' => 'Failed to charge: ' . $e->getMessage()]);
        }
    }
}

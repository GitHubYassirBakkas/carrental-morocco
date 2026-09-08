<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\CardException;
use Exception;
use App\Mail\BookingConfirmedMail;
use App\Mail\BookingPendingMail;
use Illuminate\Support\Facades\Mail;


class PaymentController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
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
                'booking_id' => $booking->id,
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
                'amount'         => (int)($depositAmount * 100),
                'currency'       => 'mad',
                'capture_method' => 'manual',
                'metadata'       => [
                    'booking_id' => $booking->id,
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

                // ── 2. Confirm deposit authorization (blocks, doesn't charge) ──
                $depositIntentId = null;
                if ($request->filled('deposit_payment_intent')) {
                    $depositIntent = PaymentIntent::retrieve($data['deposit_payment_intent']);

                    if ($depositIntent->status === 'requires_capture') {
                        // ✅ Deposit is blocked on card — don't capture yet
                        $depositIntentId = $depositIntent->id;

                        $booking->update([
                            'deposit_payment_intent_id' => $depositIntentId,
                            'deposit_status'             => 'held',
                            'deposit_paid'               => true,
                            'deposit_paid_at'            => now(),
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

        // ── Update invoice ──
        $invoice->update($isPaid
            ? ['status' => 'paid', 'paid_amount' => $booking->total_amount]
            : ['status' => 'pending']
        );

        // ── Update booking ──
        $booking->update($isPaid ? [
            'payment_method'  => 'stripe',
            'status'          => 'confirmed',
            'deposit_paid'    => true,
            'deposit_paid_at' => now(),
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
                ? 'Payment successful! Deposit of ' . number_format($booking->car->deposit_amount, 0) . ' MAD is held on your card.'
                : 'Booking created! Please visit our agency within 24 hours.'
            );
    }

    // ══════════════════════════════════════════
    // ADMIN: Release or charge deposit
    // ══════════════════════════════════════════

    /**
     * Release deposit (car returned OK)
     */
    public function releaseDeposit(Booking $booking)
    {
        abort_if(!auth()->user()->is_admin, 403);

        if (!$booking->deposit_payment_intent_id) {
            return back()->withErrors(['deposit' => 'No deposit intent found.']);
        }

        try {
            $intent = PaymentIntent::retrieve($booking->deposit_payment_intent_id);

            if ($intent->status === 'requires_capture') {
                // Cancel = release the hold ✅
                $intent->cancel();
            }

            $booking->update([
                'deposit_status' => 'released',
                'deposit_paid'   => false, // money back to user
            ]);

            return back()->with('success', 'Deposit released successfully. Funds returned to customer.');

        } catch (Exception $e) {
            return back()->withErrors(['deposit' => 'Failed to release: ' . $e->getMessage()]);
        }
    }

    /**
     * Charge deposit partially or fully (damage/late)
     */
    public function chargeDeposit(Request $request, Booking $booking)
    {
        abort_if(!auth()->user()->is_admin, 403);

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
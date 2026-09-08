<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\AdvancePaymentService;
use App\Services\PaymentService;
use App\Services\RefundService;
use Illuminate\Http\Request;

class AdminInvoiceController extends Controller
{
    private PaymentService $paymentService;
    private AdvancePaymentService $advancePaymentService;
    private RefundService $refundService;

    public function __construct(
        PaymentService $paymentService,
        AdvancePaymentService $advancePaymentService,
        RefundService $refundService
    ) {
        $this->paymentService = $paymentService;
        $this->advancePaymentService = $advancePaymentService;
        $this->refundService = $refundService;
    }

    public function index()
    {
        $invoices = Invoice::with('booking.user')
            ->latest()
            ->paginate(20);

        return view('admin.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['booking.user', 'payments']);

        $booking = $invoice->booking;

        // Calculate advance payment info.
        $minimumAdvancePayment = $this->advancePaymentService->calculateMinimumAdvancePayment($booking);
        $remainingAdvancePayment = $this->advancePaymentService->getRemainingAdvancePayment($booking);

        return view('admin.invoices.show', compact(
            'invoice',
            'minimumAdvancePayment',
            'remainingAdvancePayment'
        ));
    }

    public function recordPayment(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,card,online,bank_transfer',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            // Record payment
            $result = $this->paymentService->recordPaymentWithBookingSync(
                $invoice,
                $request->amount,
                $request->method,
                auth()->id(),
                $request->notes
            );

            $message = $result['booking_confirmed']
                ? 'Payment recorded & booking confirmed!'
                : 'Payment recorded successfully!';

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function refund(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'reason' => 'nullable|string|max:500'
        ]);

        try {
            $this->refundService->processRefund(
                $invoice,
                $request->amount,
                $request->reason
            );

            return back()->with('success', 'Refund processed successfully.');
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }
}

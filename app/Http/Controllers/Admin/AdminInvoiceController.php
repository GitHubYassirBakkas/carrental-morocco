<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\RefundService;
use App\Services\PaymentService;
use App\Services\DepositService;
use Illuminate\Http\Request;

class AdminInvoiceController extends Controller
{
    private PaymentService $paymentService;
    private DepositService $depositService;
    private RefundService $refundService;

    public function __construct(
        PaymentService $paymentService,
        DepositService $depositService,
        RefundService $refundService
    ) {
        $this->paymentService = $paymentService;
        $this->depositService = $depositService;
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
        
        // Calculate deposit info
        $minimumDeposit = $this->depositService->calculateMinimumDeposit($booking);
        $remainingDeposit = $this->depositService->getRemainingDeposit($booking);
        
        return view('admin.invoices.show', compact(
            'invoice',
            'minimumDeposit',
            'remainingDeposit'
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
                $payment = $this->paymentService->recordPayment(
                $invoice,
                $request->amount,
                $request->method,
                auth()->id(),  // ← Pass userId
                $request->notes
        );

            // Check if deposit reached and confirm booking
            $booking = $invoice->booking;
            $newPaidAmount = $invoice->fresh()->paid_amount;
            
            $confirmed = $this->depositService->confirmBookingIfDepositReached(
                $booking,
                $newPaidAmount
            );

            $message = $confirmed 
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
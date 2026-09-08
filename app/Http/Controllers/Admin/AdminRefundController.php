<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\RefundService;
use Illuminate\Http\Request;

class AdminRefundController extends Controller
{
    private RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * Display refund management page
     */
    public function index(Request $request)
    {
        $refunds = $this->refundService->getRefundHistory($request->all());
        $stats = $this->refundService->getRefundStatistics();

        return view('admin.refunds.index', compact('refunds', 'stats'));
    }

    /**
     * Download refund receipt
     */
    public function downloadReceipt(Payment $payment)
    {
        return $this->refundService->downloadRefundReceipt($payment);
    }

    /**
     * Resend refund email
     */
    public function resendEmail(Payment $payment)
    {
        $this->refundService->resendRefundEmail($payment);

        return back()->with('success', 'Refund email resent successfully.');
    }
}

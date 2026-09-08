<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class CustomerInvoiceController extends Controller
{
    public function show(Invoice $invoice): View
    {
        $this->authorizeCustomerInvoice($invoice);

        $invoice->load([
            'booking.car',
            'booking.pickupLocation',
            'booking.dropoffLocation',
            'payments',
        ]);

        return view('invoices.show', compact('invoice'));
    }

    public function download(Invoice $invoice): Response
    {
        $this->authorizeCustomerInvoice($invoice);

        $invoice->load([
            'booking.user',
            'booking.car',
            'booking.pickupLocation',
            'booking.dropoffLocation',
            'payments',
        ]);

        $booking = $invoice->booking;
        $pdf = Pdf::loadView('admin.bookings.invoice', compact('booking', 'invoice'));

        return $pdf->download('invoice-'.$invoice->id.'.pdf');
    }

    private function authorizeCustomerInvoice(Invoice $invoice): void
    {
        $invoice->loadMissing('booking');

        $ownerId = $invoice->user_id ?? $invoice->booking?->user_id;

        abort_unless((int) $ownerId === (int) auth()->id(), 403);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Invoice;
use App\Services\BookingService;
use App\Services\AdvancePaymentService;
use App\Services\SecurityDepositService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminBookingController extends Controller
{
    private BookingService $bookingService;
    private AdvancePaymentService $advancePaymentService;

    public function __construct(
        BookingService $bookingService,
        AdvancePaymentService $advancePaymentService,
        private readonly SecurityDepositService $securityDepositService
    ) {
        $this->bookingService = $bookingService;
        $this->advancePaymentService = $advancePaymentService;
    }

    public function index(Request $request)
    {
        $query = Booking::with(['car', 'user'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->whereDate('start_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('end_date', '<=', $request->to);
        }

        $bookings = $query->paginate(15);
        
        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        $booking = $this->securityDepositService->normalizeSecurityDepositState($booking);

        $booking->load([
            'user',
            'car',
            'pickupLocation',
            'dropoffLocation',
            'damages',
            'checkinInspection.photos',
            'checkoutInspection.photos',
            'invoice',
            'securityDepositCapturedBy',
            'securityDepositRefundedBy',
            'securityDepositProcessedBy',
        ]);

        // Calculate advance payment info using service.
        $advancePaymentProgress = $this->advancePaymentService->calculateAdvancePaymentProgress($booking);
        $minimumAdvancePayment = $this->advancePaymentService->calculateMinimumAdvancePayment($booking);
        $remainingAdvancePayment = $this->advancePaymentService->getRemainingAdvancePayment($booking);
        $isOverdue = $this->advancePaymentService->isAdvancePaymentOverdue($booking);

        return view('admin.bookings.show', compact(
            'booking',
            'advancePaymentProgress',
            'minimumAdvancePayment',
            'remainingAdvancePayment',
            'isOverdue'
        ));
    }

   public function confirm(Booking $booking)
{
    try {
        $this->bookingService->confirmBooking($booking);
        return back()->with('success', 'Booking confirmed & email sent!');
    } catch (\Exception $e) {
        \Log::error('Confirm error: ' . $e->getMessage());
        return back()->withErrors('Error: ' . $e->getMessage()); // ← show real error
    }
}

    public function cancel(Booking $booking)
    {
        try {
            $this->bookingService->cancelBooking($booking, 'Cancelled by admin');
            return back()->with('success', 'Booking cancelled successfully.');
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function start(Booking $booking)
    {
        try {
            $this->bookingService->startRental($booking);
            return back()->with('success', 'Rental started successfully.');
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function complete(Booking $booking)
    {
        try {
            $this->bookingService->completeRental($booking);
            return back()->with('success', 'Rental completed successfully.');
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }

     public function update(Request $request, Booking $booking)
    {
        $request->validate([
            'status' => ['required', Rule::in([
                Booking::STATUS_PENDING,
                Booking::STATUS_CONFIRMED,
                Booking::STATUS_CANCELLED,
            ])],
        ]);

        if (!$booking->isPending()) {
            return back()->with('error', 'This booking cannot be updated.');
        }

        $booking->update([
            'status' => $request->status,
        ]);

        return back()->with('success', 'Booking status updated successfully.');
    }

    public function invoice(Booking $booking)
    {
        if (!$booking->invoiced_at) {
            $booking->update(['invoiced_at' => now()]);
        }

        $invoice = $booking->invoice;
        $pdf = Pdf::loadView('admin.bookings.invoice', compact('booking', 'invoice'));

        return $pdf->download('invoice-booking-' . $booking->id . '.pdf');
    }

}




<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\AdvancePaymentService;
use App\Services\BookingService;
use App\Services\BookingTimelineService;
use App\Services\RefundPolicyService;
use App\Services\SecurityDepositService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminBookingController extends Controller
{
    private BookingService $bookingService;

    private AdvancePaymentService $advancePaymentService;

    public function __construct(
        BookingService $bookingService,
        AdvancePaymentService $advancePaymentService,
        private readonly SecurityDepositService $securityDepositService,
        private readonly RefundPolicyService $refundPolicyService,
        private readonly BookingTimelineService $bookingTimelineService
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
            'user.customerProfile',
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

        // Build admin timeline
        $timeline = $this->bookingTimelineService->buildAdminTimeline($booking);

        return view('admin.bookings.show', compact(
            'booking',
            'advancePaymentProgress',
            'minimumAdvancePayment',
            'remainingAdvancePayment',
            'isOverdue',
            'timeline'
        ));
    }

    public function confirm(Booking $booking)
    {
        try {
            $this->bookingService->confirmBooking($booking);

            return back()->with('success', 'Booking confirmed & email sent!');
        } catch (\Exception $e) {
            Log::error('Admin booking confirm failed.', [
                'exception' => $e,
                'booking_id' => $booking->id,
            ]);

            return back()->withErrors('Unable to confirm this booking. Please try again or review the logs.');
        }
    }

    public function cancel(Booking $booking)
    {
        // Check cancellation eligibility using RefundPolicyService
        if (! $this->refundPolicyService->canAdminCancel($booking)) {
            return back()->withErrors('This booking cannot be cancelled.');
        }

        try {
            $this->bookingService->cancelBooking($booking, 'Cancelled by admin');

            return back()->with('success', 'Booking cancelled successfully.');
        } catch (\Exception $e) {
            Log::error('Admin booking cancellation failed.', [
                'exception' => $e,
                'booking_id' => $booking->id,
            ]);

            return back()->withErrors('Unable to cancel this booking. Please try again or review the logs.');
        }
    }

    public function start(Booking $booking)
    {
        try {
            $this->bookingService->startRental($booking);

            return back()->with('success', 'Rental started successfully.');
        } catch (\DomainException $e) {
            Log::warning('Admin rental start blocked by business rule.', [
                'message' => $e->getMessage(),
                'booking_id' => $booking->id,
            ]);

            return back()->withErrors($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Admin rental start failed.', [
                'exception' => $e,
                'booking_id' => $booking->id,
            ]);

            return back()->withErrors('Unable to start this rental. Please try again or review the logs.');
        }
    }

    public function complete(Booking $booking)
    {
        try {
            $this->bookingService->completeRental($booking);

            return back()->with('success', 'Rental completed successfully.');
        } catch (\Exception $e) {
            Log::error('Admin rental completion failed.', [
                'exception' => $e,
                'booking_id' => $booking->id,
            ]);

            return back()->withErrors('Unable to complete this rental. Please try again or review the logs.');
        }
    }

    public function invoice(Booking $booking)
    {
        $invoice = $booking->invoice;
        $pdf = Pdf::loadView('admin.bookings.invoice', compact('booking', 'invoice'));

        return $pdf->download('invoice-booking-'.$booking->id.'.pdf');
    }
}

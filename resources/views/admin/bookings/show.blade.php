@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] text-gray-100">
    <div class="max-w-[1600px] mx-auto p-8 space-y-6">

        @include('admin.bookings.partials.booking-header', ['booking' => $booking])

        @include('admin.bookings.partials.booking-status-actions', ['booking' => $booking])

        @include('admin.bookings.partials.invoice-card', ['booking' => $booking])

        @include('admin.bookings.partials.advance-payment-card', [
            'booking' => $booking,
            'minimumAdvancePayment' => $minimumAdvancePayment ?? 0,
        ])

        @include('admin.bookings.partials.security-deposit-card', ['booking' => $booking])

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Customer & Car -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @include('admin.bookings.partials.customer-card', ['booking' => $booking])

                    @include('admin.bookings.partials.vehicle-card', ['booking' => $booking])
                </div>

                @include('admin.bookings.partials.period-location-card', ['booking' => $booking])

                @include('admin.bookings.partials.damages-card', ['booking' => $booking])

            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                @include('admin.bookings.partials.pricing-card', ['booking' => $booking])

                @include('admin.bookings.partials.timeline-card', ['booking' => $booking])

            </div>
        </div>

        <!-- Inspections -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @include('admin.bookings.partials.inspections-card', ['booking' => $booking])

            @include('admin.bookings.partials.checkin-damages-card', ['booking' => $booking])

            @include('admin.bookings.partials.checkout-damages-card', ['booking' => $booking])

            @include('admin.bookings.partials.checkout-inspection-card', ['booking' => $booking])

        </div>

    </div>
</div>
@endsection

@push('scripts')
@include('admin.bookings.partials.security-deposit-script')
@endpush

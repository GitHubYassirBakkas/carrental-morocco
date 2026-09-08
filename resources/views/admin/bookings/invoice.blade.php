<table width="100%" cellpadding="5" cellspacing="0">
    <tr>
        <td width="50%">
            <img src="{{ public_path('images/logo.png') }}" width="140">
        </td>
        <td width="50%" align="right">
            <strong>Car Rental Morocco</strong><br>
            Meknes, Morocco<br>
            Phone: +212 6 12 34 56 78<br>
            Email: contact@carrental.ma
        </td>
    </tr>
</table>

<hr>
<table width="100%" cellpadding="5" cellspacing="0">
    <tr>
        <td>
            <strong>Invoice #:</strong> INV-{{ $booking->id }}<br>
            <strong>Booking #:</strong> {{ $booking->id }}<br>
            <strong>Invoice Date:</strong> {{ now()->format('d/m/Y') }}
        </td>

        <td align="right">
            <strong>Client:</strong><br>
            {{ $booking->user->name ?? '—' }}<br>
            {{ $booking->user->email ?? '' }}
        </td>
    </tr>
</table>

<br>
<table width="100%" cellpadding="10" cellspacing="0" border="1" style="border-collapse:collapse;font-size:14px">
    <thead style="background:#f3f4f6">
        <tr>
            <th align="left">Description</th>
            <th align="right">Amount (MAD)</th>
        </tr>
    </thead>
    <tbody>

        {{-- Base rental --}}
        <tr>
            <td>Total Rental</td>
            <td align="right">
                {{ number_format($booking->total_amount, 2) }}
            </td>
        </tr>

        {{-- Adjustments --}}
        @if($booking->fuel_charge > 0 || $booking->late_fee > 0)
        <tr style="background:#fafafa">
            <td colspan="2">
                <strong>Additional Charges (after check-out)</strong>
            </td>
        </tr>
        @endif

      @if(($booking->fuel_charge ?? 0) > 0)
        <tr>
            <td>
                Fuel Charge
                <br>
                <small style="color:#666">
                    Fuel level at return lower than check-in
                </small>
            </td>
            <td align="right">
                {{ number_format($booking->fuel_charge, 2) }}
            </td>
        </tr>
        @endif

        @if(($booking->late_fee ?? 0) > 0)
        <tr>
            <td>
                Late Return Fee
                <br>
                <small style="color:#666">
                    Vehicle returned after scheduled time
                </small>
            </td>
            <td align="right">
                {{ number_format($booking->late_fee, 2) }}
            </td>
        </tr>
        @endif

        {{-- Final total --}}
        <tr style="background:#111827;color:#fff">
            <td><strong>Final Total</strong></td>
            <td align="right">
               @php($bookingStatusLabel = (new \App\View\Components\Admin\BookingStatusBadge($booking->status))->label())
               <p style="font-size:12px">
                    Status: <strong>{{ $bookingStatusLabel }}</strong>
                </p>

            </td>
        </tr>

    </tbody>
</table>

@if($booking->fuel_charge > 0 || $booking->late_fee > 0)
<p style="font-size:12px;color:#555;margin-top:10px">
    * Additional charges are calculated based on the check-out inspection.
</p>
@endif

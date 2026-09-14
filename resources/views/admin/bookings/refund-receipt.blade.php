@php
    $siteName = setting('site_name', 'Car Rental Morocco');
    $sitePhone = setting('site_phone', '+212 6 12 34 56 78');
    $siteEmail = setting('site_email', config('mail.from.address'));
    $siteAddress = setting('site_address', 'Meknes, Morocco');
@endphp
<table width="100%" cellpadding="5" cellspacing="0">
    <tr>
        <td width="50%">
            <img src="{{ public_path('images/logo.png') }}" width="140">
        </td>
        <td width="50%" align="right">
            <strong>{{ $siteName }}</strong><br>
            {{ $siteAddress }}<br>
            Phone: {{ $sitePhone }}<br>
            Email: {{ $siteEmail }}
        </td>
    </tr>
</table>

<hr>

<h2 style="text-align: center; color: #111827;">REFUND RECEIPT</h2>

<table width="100%" cellpadding="5" cellspacing="0">
    <tr>
        <td width="50%">
            <strong>Refund Reference #:</strong> {{ $payment->transaction_id ?? 'N/A' }}<br>
            @if($payment->stripe_refund_id)
            <strong>Stripe Refund ID:</strong> {{ $payment->stripe_refund_id }}<br>
            @endif
            <strong>Booking Reference #:</strong> {{ $booking->id }}<br>
            <strong>Refund Date:</strong> {{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}
        </td>
        <td width="50%" align="right">
            <strong>Customer:</strong><br>
            {{ $booking->user->name ?? '—' }}<br>
            {{ $booking->user->email ?? '' }}
        </td>
    </tr>
</table>

<br>

<table width="100%" cellpadding="5" cellspacing="0">
    <tr>
        <td width="50%">
            <strong>Refund Type:</strong><br>
            @if($refundType === 'full')
                <span style="color: #059669; font-weight: bold;">FULL REFUND</span>
            @elseif($refundType === 'partial')
                <span style="color: #d97706; font-weight: bold;">PARTIAL REFUND</span>
            @else
                <span style="color: #dc2626; font-weight: bold;">NO REFUND</span>
            @endif
        </td>
        <td width="50%">
            <strong>Refund Method:</strong><br>
            <span style="font-weight: bold;">
                @if($payment->method === 'cash')
                    CASH
                @elseif($payment->method === 'card' || $payment->method === 'stripe')
                    Card / Stripe
                @else
                    {{ ucfirst($payment->method) }}
                @endif
            </span>
        </td>
    </tr>
</table>

<br>

<table width="100%" cellpadding="5" cellspacing="0">
    <tr>
        <td width="50%">
            <strong>Car:</strong><br>
            {{ $booking->car->name ?? '—' }}<br>
            <small style="color: #666;">{{ $booking->car->full_name ?? '' }}</small>
        </td>
        <td width="50%">
            <strong>Rental Period:</strong><br>
            {{ $booking->start_date ? $booking->start_date->format('d/m/Y') : '—' }}<br>
            to {{ $booking->end_date ? $booking->end_date->format('d/m/Y') : '—' }}
        </td>
    </tr>
</table>

<br>

<table width="100%" cellpadding="10" cellspacing="0" border="1" style="border-collapse: collapse; font-size: 14px;">
    <thead style="background: #f3f4f6;">
        <tr>
            <th align="left">Description</th>
            <th align="right">Amount (MAD)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Original Amount Paid</td>
            <td align="right">{{ number_format($originalAmount, 2) }}</td>
        </tr>
        <tr style="background: #fef3c7;">
            <td><strong>Refund Amount</strong></td>
            <td align="right"><strong>{{ number_format($payment->amount, 2) }}</strong></td>
        </tr>
    </tbody>
</table>

<br>

@if($payment->notes)
<table width="100%" cellpadding="5" cellspacing="0">
    <tr>
        <td>
            <strong>Refund Reason:</strong><br>
            {{ $payment->notes }}
        </td>
    </tr>
</table>
@endif

<br>

<hr>

<p style="font-size: 12px; color: #666; text-align: center;">
    This document serves as an official refund receipt. Please retain for your records.<br>
    If you have any questions, contact us at {{ $siteEmail }}
</p>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .wrap { max-width: 620px; margin: auto; }
        .header { background: #0a0a0a; padding: 30px; text-align: center; border-radius: 12px 12px 0 0; }
        .header h1 { color: #C89D66; font-size: 22px; margin-bottom: 4px; }
        .header p { color: #666; font-size: 13px; }
        .body { background: #fff; padding: 30px; }
        .pending-badge {
            display: inline-block;
            background: #fef3c7; color: #92400e;
            padding: 8px 20px; border-radius: 100px;
            font-size: 14px; font-weight: bold; margin-bottom: 20px;
        }
        .car-box {
            background: #f9fafb; border-radius: 10px;
            padding: 16px; margin: 20px 0;
            border-left: 4px solid #C89D66;
        }
        .car-box h2 { font-size: 18px; color: #111; margin-bottom: 4px; }
        .car-box p { font-size: 13px; color: #666; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 20px 0; }
        .info-item { background: #f9fafb; border-radius: 8px; padding: 12px; }
        .info-item .label { font-size: 11px; color: #999; text-transform: uppercase; margin-bottom: 4px; }
        .info-item .value { font-size: 14px; color: #111; font-weight: bold; }
        .warning-box {
            background: #fff7ed; border: 1px solid #fed7aa;
            border-radius: 10px; padding: 16px; margin: 20px 0;
        }
        .warning-box h3 { font-size: 14px; color: #c2410c; margin-bottom: 10px; }
        .warning-box ul { font-size: 13px; color: #666; padding-left: 20px; }
        .warning-box ul li { margin-bottom: 6px; }
        .deadline-box {
            background: #fef2f2; border: 1px solid #fecaca;
            border-radius: 10px; padding: 14px; margin: 15px 0;
            text-align: center;
        }
        .deadline-box p { font-size: 13px; color: #dc2626; }
        .deadline-box strong { font-size: 16px; display: block; margin-top: 4px; }
        .amount-box {
            background: #fffbf0; border: 1px solid #e5d5a3;
            border-radius: 10px; padding: 14px; margin: 15px 0;
        }
        .amount-row { display: flex; justify-content: space-between; font-size: 13px; color: #555; padding: 5px 0; }
        .amount-total { display: flex; justify-content: space-between; font-size: 16px; font-weight: bold; color: #C89D66; padding-top: 8px; border-top: 1px solid #e5d5a3; margin-top: 5px; }
        .footer { background: #f8f8f8; padding: 20px; text-align: center; border-radius: 0 0 12px 12px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
@php
    $siteName = setting('site_name', 'Car Rental Morocco');
    $sitePhone = setting('site_phone', '+212 6 00 00 00 00');
    $siteEmail = setting('site_email', config('mail.from.address'));
    $siteAddress = setting('site_address', 'Meknes, Morocco');
    $deadlineHours = (int) setting('advance_payment_deadline_hours', config('rental.advance_payment_deadline_hours', 24));
    $pricingBreakdown = $booking->pricing_breakdown;
@endphp
<div class="wrap">

    <div class="header">
        <h1>{{ $siteName }}</h1>
        <p>Your trusted car rental partner</p>
    </div>

    <div class="body">
        <div class="pending-badge">⏳ Booking Pending</div>

        <p style="color:#333;font-size:15px;">Hello <strong>{{ $booking->user->name }}</strong>,</p>
        <p style="color:#666;font-size:13px;margin-top:8px;">
            Your booking has been received! To confirm your reservation, please visit our agency and complete the payment within <strong>{{ $deadlineHours }} hours</strong>.
        </p>

        {{-- Car Info --}}
        <div class="car-box">
            <h2>{{ $booking->car->brand }} {{ $booking->car->model }}</h2>
            <p>{{ $booking->car->year }} • {{ ucfirst($booking->car->type) }}</p>
        </div>

        {{-- Booking Details --}}
        <div class="info-grid">
            <div class="info-item">
                <div class="label">Booking Reference</div>
                <div class="value">#{{ str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}</div>
            </div>
            <div class="info-item">
                <div class="label">Status</div>
                <div class="value" style="color:#d97706;">Pending ⏳</div>
            </div>
            <div class="info-item">
                <div class="label">Pickup Date</div>
                <div class="value">{{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }}</div>
            </div>
            <div class="info-item">
                <div class="label">Return Date</div>
                <div class="value">{{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}</div>
            </div>
        </div>

        {{-- Amount to Pay --}}
        <div class="amount-box">
            <div class="amount-row">
                <span>Car Rental ({{ $booking->total_days }} days)</span>
                <span>{{ number_format($booking->total_days * $booking->rental_price_per_day, 0) }} MAD</span>
            </div>
            @if($booking->insurance)
            @php
                $protectionPlanName = match (true) {
                    str_contains(strtolower($booking->insurance->name ?? ''), 'zero')     => 'Zero Excess Protection',
                    str_contains(strtolower($booking->insurance->name ?? ''), 'premium')  => 'Premium Protection',
                    str_contains(strtolower($booking->insurance->name ?? ''), 'standard') => 'Standard Protection',
                    str_contains(strtolower($booking->insurance->name ?? ''), 'basic')    => 'Basic Coverage Included',
                    default => str_ireplace(['Insurance', 'insurance'], ['Protection Plan', 'protection plan'], $booking->insurance->name ?? ''),
                };
            @endphp
            <div class="amount-row">
                <span>Protection Plan - {{ $protectionPlanName }}</span>
                <span>{{ number_format($booking->insurance->fixed_price ?? 0, 0) }} MAD</span>
            </div>
            @endif
            @if($booking->car->security_deposit_amount > 0)
            <div class="amount-row" style="color:#7c3aed;">
                <span>Security Deposit (cash, refundable)</span>
                <span>{{ number_format($booking->car->security_deposit_amount, 0) }} MAD</span>
            </div>
            @endif
            @if(($pricingBreakdown['tax_amount'] ?? 0) > 0)
            <div class="amount-row">
                <span>Tax</span>
                <span>{{ number_format($pricingBreakdown['tax_amount'], 0) }} MAD</span>
            </div>
            @endif
            <div class="amount-total">
                <span>Total to Pay at Agency</span>
                <span>{{ number_format($booking->total_amount + ($booking->car->security_deposit_amount ?? 0), 0) }} MAD</span>
            </div>
        </div>

        {{-- What to bring --}}
        <div class="warning-box">
            <h3>📋 What to bring to the agency:</h3>
            <ul>
                <li>✅ Valid driving license</li>
                <li>✅ National ID or Passport</li>
                <li>✅ Payment: <strong>{{ number_format($booking->total_amount, 0) }} MAD</strong> (rental + protection plan)</li>
                @if($booking->car->security_deposit_amount > 0)
                <li>✅ Security Deposit: <strong>{{ number_format($booking->car->security_deposit_amount, 0) }} MAD</strong> (cash, fully refundable)</li>
                @endif
            </ul>
        </div>

        {{-- Payment deadline --}}
        <div class="deadline-box">
            <p>⚠️ Your booking will be automatically cancelled if not paid within:</p>
            <strong>{{ $deadlineHours }} hours from now</strong>
        </div>

        <p style="font-size:13px;color:#666;margin-top:15px;">
            Questions? Contact us on WhatsApp or call us directly.
        </p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
        <p style="margin-top:5px;">{{ $siteAddress }} | {{ $siteEmail }} | {{ $sitePhone }}</p>
    </div>

</div>
</body>
</html>

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
        .refund-badge {
            display: inline-block;
            background: #d1fae5; color: #065f46;
            padding: 8px 20px; border-radius: 100px;
            font-size: 14px; font-weight: bold;
            margin-bottom: 20px;
        }
        .car-box {
            background: #f9fafb; border-radius: 10px;
            padding: 16px; margin: 20px 0;
            border-left: 4px solid #C89D66;
        }
        .car-box h2 { font-size: 18px; color: #111; margin-bottom: 4px; }
        .car-box p { font-size: 13px; color: #666; }
        .info-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 12px; margin: 20px 0;
        }
        .info-item { background: #f9fafb; border-radius: 8px; padding: 12px; }
        .info-item .label { font-size: 11px; color: #999; text-transform: uppercase; margin-bottom: 4px; }
        .info-item .value { font-size: 14px; color: #111; font-weight: bold; }
        .refund-box {
            background: #fffbf0; border: 1px solid #e5d5a3;
            border-radius: 10px; padding: 16px; margin: 20px 0;
        }
        .refund-box h3 { font-size: 14px; color: #C89D66; margin-bottom: 12px; }
        .refund-row {
            display: flex; justify-content: space-between;
            font-size: 13px; color: #555; padding: 5px 0;
            border-bottom: 1px solid #f0e8d0;
        }
        .refund-row:last-child { border-bottom: none; }
        .refund-total {
            display: flex; justify-content: space-between;
            font-size: 16px; font-weight: bold;
            color: #C89D66; padding-top: 10px; margin-top: 5px;
        }
        .policy-box {
            background: #f3f4f6; border-radius: 10px;
            padding: 16px; margin: 20px 0;
        }
        .policy-box h3 { font-size: 14px; color: #111; margin-bottom: 8px; }
        .policy-box p { font-size: 13px; color: #666; line-height: 1.6; }
        .footer { background: #0a0a0a; padding: 20px; text-align: center; border-radius: 0 0 12px 12px; }
        .footer p { color: #999; font-size: 12px; line-height: 1.6; }
        .footer a { color: #C89D66; text-decoration: none; }
        .refund-type-full { color: #059669; font-weight: bold; }
        .refund-type-partial { color: #d97706; font-weight: bold; }
        .refund-type-none { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
@php
    $siteName = setting('site_name', 'Car Rental Morocco');
    $sitePhone = setting('site_phone', '+212 6 12 34 56 78');
    $siteEmail = setting('site_email', config('mail.from.address'));
    $siteAddress = setting('site_address', 'Meknes, Morocco');
@endphp
    <div class="wrap">
        <div class="header">
            <h1>{{ $siteName }}</h1>
            <p>Your Trusted Car Rental Partner</p>
        </div>

        <div class="body">
            <div class="refund-badge">
                💰 Refund Processed Successfully
            </div>

            <p style="font-size: 14px; color: #333; margin-bottom: 20px;">
                Dear {{ $payment->invoice->booking->user->name ?? 'Customer' }},
            </p>

            <p style="font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 20px;">
                We have processed your refund for booking #{{ $payment->invoice->booking->id }}.
                Please find the details below.
            </p>

            <div class="car-box">
                <h2>{{ $payment->invoice->booking->car->name ?? 'Car' }}</h2>
                <p>{{ $payment->invoice->booking->car->full_name ?? '' }}</p>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="label">Booking Reference</div>
                    <div class="value">#{{ $payment->invoice->booking->id }}</div>
                </div>
                <div class="info-item">
                    <div class="label">Refund Reference</div>
                    <div class="value">{{ $payment->transaction_id ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="label">Refund Type</div>
                    <div class="value @if($refundType === 'full') refund-type-full @elseif($refundType === 'partial') refund-type-partial @else refund-type-none @endif">
                        @if($refundType === 'full') Full Refund @elseif($refundType === 'partial') Partial Refund @else No Refund @endif
                    </div>
                </div>
                <div class="info-item">
                    <div class="label">Refund Method</div>
                    <div class="value">Cash</div>
                </div>
            </div>

            <div class="refund-box">
                <h3>Refund Details</h3>
                <div class="refund-row">
                    <span>Original Amount Paid</span>
                    <span>{{ number_format($originalAmount, 2) }} MAD</span>
                </div>
                <div class="refund-row">
                    <span>Refund Amount</span>
                    <span>{{ number_format($payment->amount, 2) }} MAD</span>
                </div>
                <div class="refund-total">
                    <span>Total Refund</span>
                    <span>{{ number_format($payment->amount, 2) }} MAD</span>
                </div>
            </div>

            <div class="policy-box">
                <h3>Refund Policy</h3>
                <p>
                    Refunds are processed based on our cancellation policy.
                    Full refunds are available for cancellations made at least 48 hours before the rental start date.
                    Partial refunds may apply for late cancellations.
                    If you have any questions about your refund, please contact our customer service.
                </p>
            </div>

            <p style="font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 20px;">
                <strong>Your Refund Receipt PDF is attached to this email.</strong>
                Please save it for your records.
            </p>

            <p style="font-size: 14px; color: #555; line-height: 1.6;">
                If you have any questions or need further assistance, please don't hesitate to contact us.
            </p>
        </div>

        <div class="footer">
            <p>
                <strong>{{ $siteName }}</strong><br>
                {{ $siteAddress }}<br>
                Phone: {{ $sitePhone }}<br>
                Email: <a href="mailto:{{ $siteEmail }}">{{ $siteEmail }}</a>
            </p>
            <p style="margin-top: 15px;">
                © {{ date('Y') }} Car Rental Morocco. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>

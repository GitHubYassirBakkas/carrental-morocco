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
        .success-badge {
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
        .receipt-box {
            background: #fffbf0; border: 1px solid #e5d5a3;
            border-radius: 10px; padding: 16px; margin: 20px 0;
        }
        .receipt-box h3 { font-size: 14px; color: #C89D66; margin-bottom: 12px; }
        .receipt-row {
            display: flex; justify-content: space-between;
            font-size: 13px; color: #555; padding: 5px 0;
            border-bottom: 1px solid #f0e8d0;
        }
        .receipt-row:last-child { border-bottom: none; }
        .receipt-total {
            display: flex; justify-content: space-between;
            font-size: 16px; font-weight: bold;
            color: #C89D66; padding-top: 10px; margin-top: 5px;
        }
        .deposit-box {
            background: #f5f3ff; border: 1px solid #ddd6fe;
            border-radius: 10px; padding: 14px; margin: 15px 0;
        }
        .deposit-box p { font-size: 13px; color: #5b21b6; }
        .btn {
            display: inline-block; background: #C89D66;
            color: #fff; padding: 12px 28px;
            border-radius: 8px; text-decoration: none;
            font-weight: bold; font-size: 14px; margin: 10px 0;
        }
        .footer {
            background: #f8f8f8; padding: 20px; text-align: center;
            border-radius: 0 0 12px 12px; font-size: 12px; color: #999;
        }
        .divider { border: none; border-top: 1px solid #eee; margin: 15px 0; }
    </style>
</head>
<body>
<div class="wrap">

    <div class="header">
        <h1>🚗 CarRental Morocco</h1>
        <p>Your trusted car rental partner</p>
    </div>

    <div class="body">
        <div class="success-badge">✅ Booking Confirmed!</div>

        <p style="color:#333;font-size:15px;">Hello <strong>{{ $booking->user->name }}</strong>,</p>
        <p style="color:#666;font-size:13px;margin-top:8px;">
            Great news! Your booking has been confirmed and your payment was processed successfully.
        </p>

        {{-- Car Info --}}
        <div class="car-box">
            <h2>{{ $booking->car->brand }} {{ $booking->car->model }}</h2>
            <p>{{ $booking->car->year }} • {{ ucfirst($booking->car->type) }} • {{ ucfirst($booking->car->transmission) }}</p>
        </div>

        {{-- Booking Details --}}
        <div class="info-grid">
            <div class="info-item">
                <div class="label">Booking Reference</div>
                <div class="value">#{{ str_pad($booking->id, 6, '0', STR_PAD_LEFT) }}</div>
            </div>
            <div class="info-item">
                <div class="label">Status</div>
                <div class="value" style="color:#065f46;">Confirmed ✓</div>
            </div>
            <div class="info-item">
                <div class="label">Pickup Date</div>
                <div class="value">{{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }}</div>
            </div>
            <div class="info-item">
                <div class="label">Return Date</div>
                <div class="value">{{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}</div>
            </div>
            <div class="info-item">
                <div class="label">Pickup Location</div>
                <div class="value">{{ $booking->pickupLocation->name ?? '-' }}</div>
            </div>
            <div class="info-item">
                <div class="label">Duration</div>
                <div class="value">{{ $booking->total_days }} days</div>
            </div>
        </div>

        {{-- Receipt --}}
        <div class="receipt-box">
            <h3>🧾 Payment Receipt</h3>
            <div class="receipt-row">
                <span>Car Rental ({{ $booking->total_days }} days × {{ number_format($booking->daily_rate, 0) }} MAD)</span>
                <span>{{ number_format($booking->total_days * $booking->daily_rate, 0) }} MAD</span>
            </div>
            @if($booking->insurance)
            <div class="receipt-row">
                <span>Insurance - {{ $booking->insurance->name }}</span>
                <span>{{ number_format($booking->insurance->daily_rate ?? 0, 0) }} MAD</span>
            </div>
            @endif
            @if($booking->discount_amount > 0)
            <div class="receipt-row" style="color:#059669;">
                <span>Discount (Coupon)</span>
                <span>−{{ number_format($booking->discount_amount, 0) }} MAD</span>
            </div>
            @endif
            <hr class="divider">
            <div class="receipt-total">
                <span>Total Paid</span>
                <span>{{ number_format($booking->total_amount, 0) }} MAD</span>
            </div>
        </div>

        {{-- Deposit Notice --}}
        @if($booking->car->deposit_amount > 0)
        <div class="deposit-box">
            <p>
                🔒 <strong>Security Deposit:</strong>
                {{ number_format($booking->car->deposit_amount, 0) }} MAD has been temporarily held on your card.
                This amount will be automatically released after vehicle inspection upon return.
            </p>
        </div>
        @endif

        <p style="font-size:13px;color:#666;margin:15px 0;">
            Please bring your driving license and the card used for payment on pickup day.
        </p>

        <a href="{{ route('bookings.show', $booking) }}" class="btn">View My Booking →</a>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} CarRental Morocco. All rights reserved.</p>
        <p style="margin-top:5px;">Meknes, Morocco | contact@carrental.ma | +212 6 00 00 00 00</p>
    </div>

</div>
</body>
</html>
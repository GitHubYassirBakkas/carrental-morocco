<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { background: #0a0a0a; padding: 30px; text-align: center; }
        .header h1 { color: #C89D66; margin: 0; font-size: 24px; }
        .header p { color: #888; margin: 5px 0 0; font-size: 14px; }
        .body { padding: 30px; }
        .reason { background: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid #dc2626; border-radius: 8px; padding: 16px; margin: 20px 0; }
        .reason strong { color: #991b1b; display: block; font-size: 13px; margin-bottom: 6px; }
        .reason p { color: #7f1d1d; font-size: 14px; line-height: 1.6; margin: 0; }
        .btn { display: inline-block; background: #C89D66; color: #fff !important; padding: 14px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; margin: 20px 0; }
        .footer { background: #f8f8f8; padding: 20px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>CarRental Morocco</h1>
            <p>Your trusted car rental partner</p>
        </div>

        <div class="body">
            <h2 style="color:#333;margin-top:0;">Driver verification needs attention</h2>
            <p style="color:#333;font-size:15px;">Hello <strong>{{ $user->name }}</strong>,</p>
            <p style="color:#666;font-size:14px;line-height:1.7;">
                We reviewed your driver profile, but we need you to update some information before you can reserve a vehicle.
            </p>

            <div class="reason">
                <strong>Reason</strong>
                <p>{{ $reason }}</p>
            </div>

            <a href="{{ route('profile.driver.edit') }}" class="btn">Update Driver Profile</a>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} CarRental Morocco. All rights reserved.</p>
            <p>Meknes, Morocco | contact@carrental.ma</p>
        </div>
    </div>
</body>
</html>

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
        .badge { display: inline-block; background: #d1fae5; color: #065f46; padding: 8px 18px; border-radius: 999px; font-size: 13px; font-weight: bold; margin-bottom: 18px; }
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
            <div class="badge">Driver profile verified</div>
            <p style="color:#333;font-size:15px;">Hello <strong>{{ $user->name }}</strong>,</p>
            <p style="color:#666;font-size:14px;line-height:1.7;">
                Good news - your driver profile has been reviewed and verified.
            </p>
            <p style="color:#666;font-size:14px;line-height:1.7;">
                You can now reserve vehicles with CarRental Morocco.
            </p>

            <a href="{{ route('cars.index') }}" class="btn">Browse Cars</a>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} CarRental Morocco. All rights reserved.</p>
            <p>Meknes, Morocco | contact@carrental.ma</p>
        </div>
    </div>
</body>
</html>

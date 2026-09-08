<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Congratulations! 🎉</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 50px 30px;
            text-align: center;
            color: white;
        }
        
        .emoji {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 18px;
            opacity: 0.95;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .milestone {
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin-bottom: 30px;
            border: 3px dashed #f39c12;
        }
        
        .milestone-number {
            font-size: 48px;
            font-weight: bold;
            color: #d63031;
            margin-bottom: 10px;
        }
        
        .milestone-text {
            font-size: 18px;
            color: #2d3436;
            font-weight: 600;
        }
        
        .coupon-box {
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
        }
        
        .coupon-box::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .coupon-label {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }
        
        .coupon-code {
            background: white;
            color: #00b894;
            font-size: 36px;
            font-weight: bold;
            padding: 20px 30px;
            border-radius: 10px;
            letter-spacing: 4px;
            font-family: 'Courier New', monospace;
            margin: 15px 0;
            border: 3px dashed #00b894;
            position: relative;
        }
        
        .coupon-discount {
            color: white;
            font-size: 48px;
            font-weight: bold;
            margin: 15px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .coupon-details {
            color: rgba(255,255,255,0.95);
            font-size: 14px;
            margin-top: 15px;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 30px 0;
        }
        
        .detail-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .detail-label {
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        
        .detail-value {
            color: #2d3436;
            font-size: 16px;
            font-weight: bold;
        }
        
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            margin: 20px 0;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
        }
        
        .message {
            color: #2d3436;
            font-size: 16px;
            line-height: 1.6;
            margin: 20px 0;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            border-top: 1px solid #e9ecef;
        }
        
        .footer-links {
            margin: 20px 0;
        }
        
        .footer-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .social-icons {
            margin: 20px 0;
        }
        
        .social-icons a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            line-height: 40px;
            margin: 0 5px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        
        <!-- Header -->
        <div class="header">
            <div class="emoji">🎊</div>
            <h1>Congratulations {{ $user->name }}!</h1>
            <p>You've reached an incredible milestone!</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            
            <!-- Milestone Achievement -->
            <div class="milestone">
                <div class="milestone-number">{{ $milestone }}</div>
                <div class="milestone-text">Completed Bookings! 🚗</div>
            </div>
            
            <!-- Personal Message -->
            <div class="message">
                <p>Dear <strong>{{ $user->name }}</strong>,</p>
                <br>
                <p>We're thrilled to celebrate this special moment with you! You've just completed your <strong>{{ $milestone }}th rental</strong> with CarRental Morocco, and that makes you one of our most valued customers! 🌟</p>
                <br>
                <p>To show our appreciation for your loyalty, we're giving you an <strong>exclusive reward</strong>:</p>
            </div>
            
            <!-- Coupon Box -->
            <div class="coupon-box">
                <div class="coupon-label">Your Exclusive Coupon Code</div>
                <div class="coupon-code">{{ $coupon->code }}</div>
                <div class="coupon-discount">{{ $coupon->discount_display }} OFF!</div>
                <div class="coupon-details">
                    Valid until {{ $coupon->valid_until->format('d M Y') }}
                </div>
            </div>
            
            <!-- Details Grid -->
            <div class="details-grid">
                <div class="detail-item">
                    <div class="detail-label">Discount</div>
                    <div class="detail-value">{{ $coupon->discount_display }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Valid Until</div>
                    <div class="detail-value">{{ $coupon->valid_until->format('d M Y') }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Usage Limit</div>
                    <div class="detail-value">{{ $coupon->max_uses_per_user }}x</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Category</div>
                    <div class="detail-value">{{ $coupon->category_label }}</div>
                </div>
            </div>
            
            <!-- How to Use -->
            <div class="message">
                <h3 style="color: #2d3436; margin-bottom: 15px;">How to Use Your Coupon:</h3>
                <ol style="line-height: 2; color: #636e72;">
                    <li>Browse our amazing fleet of vehicles</li>
                    <li>Select your preferred car and dates</li>
                    <li>Enter code <strong style="color: #00b894;">{{ $coupon->code }}</strong> at checkout</li>
                    <li>Enjoy your {{ $coupon->discount_display }} discount! 🎉</li>
                </ol>
            </div>
            
            <!-- CTA Button -->
            <div style="text-align: center;">
                <a href="{{ url('/cars') }}" class="cta-button">
                    🚗 Book Your Next Trip Now!
                </a>
            </div>
            
            <!-- Thank You Message -->
            <div class="message" style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
                <p style="font-size: 18px; color: #2d3436; margin-bottom: 10px;">
                    <strong>Thank you for choosing CarRental Morocco!</strong>
                </p>
                <p style="color: #636e72;">
                    Your trust and loyalty mean the world to us. We look forward to serving you on many more journeys! 🌍✨
                </p>
            </div>
            
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>CarRental Morocco</strong></p>
            <p>Your trusted car rental partner since 2026</p>
            
            <div class="footer-links">
                <a href="{{ url('/') }}">Home</a> |
                <a href="{{ url('/cars') }}">Browse Cars</a> |
                <a href="{{ url('/my-bookings') }}">My Bookings</a> |
                <a href="{{ url('/contact') }}">Contact Us</a>
            </div>
            
            <div class="social-icons">
                <a href="#" title="Facebook">f</a>
                <a href="#" title="Twitter">𝕏</a>
                <a href="#" title="Instagram">📷</a>
            </div>
            
            <p style="margin-top: 20px; font-size: 12px; color: #95a5a6;">
                This email was sent to {{ $user->email }}<br>
                © {{ date('Y') }} CarRental Morocco. All rights reserved.
            </p>
        </div>
        
    </div>
</body>
</html>
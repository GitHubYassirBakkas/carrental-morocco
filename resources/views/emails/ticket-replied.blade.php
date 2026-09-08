<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header { background: #0a0a0a; padding: 30px; text-align: center; }
        .header h1 { color: #C89D66; margin: 0; font-size: 24px; }
        .body { padding: 30px; }
        .reply-box { background: #fff8ee; border-left: 4px solid #C89D66; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .reply-box p { margin: 0; color: #555; line-height: 1.6; }
        .ticket-info { background: #f8f8f8; border-radius: 8px; padding: 15px; margin: 15px 0; font-size: 13px; color: #777; }
        .badge { display: inline-block; background: #C89D66; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .btn { display: inline-block; background: #C89D66; color: #fff !important; padding: 14px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; margin: 20px 0; }
        .footer { background: #f8f8f8; padding: 20px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">

        <div class="header">
            <h1>🚗 CarRental Morocco</h1>
        </div>

        <div class="body">
            <h2 style="color:#333;">💬 New reply on your ticket</h2>
            <p style="color:#666;">Hello <strong>{{ $ticket->user->name }}</strong>,</p>
            <p style="color:#666;">Our support team has replied to your ticket <span class="badge">{{ $ticket->ticket_number }}</span></p>

            <div class="ticket-info">
                <strong>Subject:</strong> {{ $ticket->subject }}
            </div>

            <p style="color:#555; font-weight:bold;">Reply from Support Team:</p>
            <div class="reply-box">
                <p>{{ $ticketMessage->message }}</p>
            </div>

            <a href="{{ route('tickets.show', $ticket) }}" class="btn">
                View Full Conversation →
            </a>

            <p style="color:#999; font-size:13px;">
                You can reply directly from your account dashboard.
            </p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} CarRental Morocco. All rights reserved.</p>
            <p>Casablanca, Morocco | contact@carrental.ma</p>
        </div>

    </div>
</body>
</html>
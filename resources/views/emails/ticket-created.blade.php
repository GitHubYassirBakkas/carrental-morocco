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
        .ticket-box { background: #f8f8f8; border-left: 4px solid #C89D66; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .ticket-box p { margin: 5px 0; font-size: 14px; color: #555; }
        .ticket-box strong { color: #333; }
        .badge { display: inline-block; background: #C89D66; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .btn { display: inline-block; background: #C89D66; color: #fff !important; padding: 14px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; margin: 20px 0; }
        .footer { background: #f8f8f8; padding: 20px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #eee; }
    </style>
</head>
<body>
@php
    $siteName = setting('site_name', 'Car Rental Morocco');
    $siteEmail = setting('site_email', config('mail.from.address'));
    $siteAddress = setting('site_address', 'Meknes, Morocco');
@endphp
    <div class="container">

        <div class="header">
            <h1>{{ $siteName }}</h1>
            <p>Support Center</p>
        </div>

        <div class="body">
            <h2 style="color:#333;">✅ Your request has been received!</h2>
            <p style="color:#666;">Hello <strong>{{ $ticket->user->name }}</strong>,</p>
            <p style="color:#666;">We've received your support request and our team will get back to you within <strong>24 hours</strong>.</p>

            <div class="ticket-box">
                <p><strong>Ticket Number:</strong> <span class="badge">{{ $ticket->ticket_number }}</span></p>
                <p><strong>Subject:</strong> {{ $ticket->subject }}</p>
                <p><strong>Category:</strong> {{ ucfirst($ticket->category) }}</p>
                <p><strong>Status:</strong> Open</p>
                <p><strong>Date:</strong> {{ $ticket->created_at->format('d M Y, H:i') }}</p>
            </div>

            <p style="color:#666;">You can track your ticket and view replies from your account:</p>

            <a href="{{ route('tickets.show', $ticket) }}" class="btn">
                View My Ticket →
            </a>

            <p style="color:#999; font-size:13px;">If you didn't submit this request, please ignore this email.</p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
            <p>{{ $siteAddress }} | {{ $siteEmail }}</p>
        </div>

    </div>
</body>
</html>

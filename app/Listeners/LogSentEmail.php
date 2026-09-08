<?php

namespace App\Listeners;

use App\Models\EmailLog;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Request;

class LogSentEmail
{
    public function handle(MessageSent $event): void
    {
        $message = $event->sent->getSymfonySentMessage();
        $email = $message->getOriginalMessage();

        // Get recipient
        $to = collect($email->getTo())->keys()->first();

        // Get subject
        $subject = $email->getSubject();

        // Get body (handle both text and html)
        $body = $email->getTextBody() ?? $email->getHtmlBody() ?? '';

        // Get user_id from session if logged in
        $userId = auth()->check() ? auth()->id() : null;

        // Get message ID (from headers if available)
        $messageId = $message->getMessageId() ?? null;

        // Create log
        EmailLog::create([
            'user_id' => $userId,
            'to' => $to,
            'subject' => $subject,
            'content' => $body,
            'status' => 'sent',
            'sent_at' => now(),
            'message_id' => $messageId,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}

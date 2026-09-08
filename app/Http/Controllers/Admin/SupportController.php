<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TicketRepliedMail;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SupportController extends Controller
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    // Dashboard ديال الـ support
    public function index(Request $request)
    {
        $query = Ticket::with(['user', 'latestMessage']);

        // Filter بـ status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter بـ category
        if ($request->category) {
            $query->where('category', $request->category);
        }

        $tickets = $query->latest()->paginate(15);

        $stats = [
            'open' => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'resolved' => Ticket::where('status', 'resolved')->count(),
            'total' => Ticket::count(),
        ];

        return view('admin.support.index', compact('tickets', 'stats'));
    }

    // Admin يشوف ticket ويرد
    public function show(Ticket $ticket)
    {
        // نقراو messages ديال الـ user
        $ticket->messages()->where('is_admin', false)->update(['is_read' => true]);

        $messages = $ticket->messages()->with('user')->get();

        return view('admin.support.show', compact('ticket', 'messages'));
    }

    // Admin يرد
    public function reply(Request $request, Ticket $ticket)
    {
        $request->validate([
            'message' => 'required|string|min:2',
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
            'is_admin' => true,
        ]);

        $ticket->update(['status' => $request->status]);

        // ✅ Send email notification to user
        try {
            Mail::to($ticket->user->email)->send(new TicketRepliedMail($ticket, $message));
        } catch (\Throwable $e) {
            Log::warning('Support ticket reply email failed.', [
                'ticket_id' => $ticket->id,
                'ticket_message_id' => $message->id,
                'user_id' => $ticket->user_id,
                'mailable' => TicketRepliedMail::class,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }

        // ✅ CREATE NOTIFICATION FOR SUPPORT TICKET REPLY
        $notificationExists = Notification::where('user_id', $ticket->user_id)
            ->where('type', 'support_ticket_reply')
            ->whereJsonContains('data->reply_id', $message->id)
            ->exists();

        if (! $notificationExists) {
            $this->notificationService->create(
                $ticket->user_id,
                'support_ticket_reply',
                __('messages.notification_support_reply'),
                __('messages.notification_support_reply_message'),
                [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'reply_id' => $message->id,
                    'replied_by' => Auth::user()->name,
                    'replied_at' => now(),
                    'ticket_url' => route('tickets.show', $ticket),
                ]
            );
        }

        return back()->with('success', 'Reply sent & user notified by email!');
    }
}

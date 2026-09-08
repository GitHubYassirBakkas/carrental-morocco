<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Mail\TicketRepliedMail;
use Illuminate\Support\Facades\Mail;

class SupportController extends Controller
{
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
            'open'        => Ticket::where('status', 'open')->count(),
            'in_progress' => Ticket::where('status', 'in_progress')->count(),
            'resolved'    => Ticket::where('status', 'resolved')->count(),
            'total'       => Ticket::count(),
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
        'status'  => 'required|in:open,in_progress,resolved,closed',
    ]);

    $message = TicketMessage::create([
        'ticket_id' => $ticket->id,
        'user_id'   => Auth::id(),
        'message'   => $request->message,
        'is_admin'  => true,
    ]);

    $ticket->update(['status' => $request->status]);

    // ✅ Send email notification to user
    Mail::to($ticket->user->email)->send(new TicketRepliedMail($ticket, $message));

    return back()->with('success', 'Reply sent & user notified by email!');
}
}
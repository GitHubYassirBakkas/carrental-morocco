<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Mail\TicketCreatedMail;
use App\Mail\TicketRepliedMail;
use Illuminate\Support\Facades\Mail;

class TicketController extends Controller
{
    // ======= USER SIDE =======

    // صفحة Contact Us + قائمة tickets ديال الuser
    public function index()
    {
        $tickets = Ticket::where('user_id', Auth::id())
                         ->with('latestMessage')
                         ->latest()
                         ->get();
        return view('tickets.index', compact('tickets'));
    }

    // إنشاء ticket جديد
   public function store(Request $request)
{
    $request->validate([
        'subject'  => 'required|string|max:200',
        'category' => 'required|in:booking,payment,complaint,other',
        'message'  => 'required|string|min:10',
    ]);

    $ticket = Ticket::create([
        'user_id'       => Auth::id(),
        'ticket_number' => Ticket::generateTicketNumber(),
        'subject'       => $request->subject,
        'category'      => $request->category,
        'status'        => 'open',
        'priority'      => 'medium',
    ]);

    TicketMessage::create([
        'ticket_id' => $ticket->id,
        'user_id'   => Auth::id(),
        'message'   => $request->message,
        'is_admin'  => false,
    ]);

    // ✅ Send confirmation email to user
    Mail::to($ticket->user->email)->send(new TicketCreatedMail($ticket));

    return redirect()->route('tickets.index')
                     ->with('success', __('messages.ticket_created'));
}

    // صفحة conversation ديال ticket واحد
    public function show(Ticket $ticket)
    {
        // تحقق أن الـ ticket ديال هاد الuser
        abort_if($ticket->user_id !== Auth::id(), 403);

        // نقراو كل messages
        $ticket->messages()->where('is_admin', true)->update(['is_read' => true]);

        $messages = $ticket->messages()->with('user')->get();

        return view('tickets.show', compact('ticket', 'messages'));
    }
    // زيد هاد الـ method فـ TicketController.php
public function contact()
{
    return view('contact');
}

    // User يرد على ticket
    public function reply(Request $request, Ticket $ticket)
    {
        abort_if($ticket->user_id !== Auth::id(), 403);
        abort_if(in_array($ticket->status, ['resolved', 'closed']), 403);

        $request->validate([
            'message' => 'required|string|min:2',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => Auth::id(),
            'message'   => $request->message,
            'is_admin'  => false,
        ]);

        // نرجعو status لـ open إذا كان in_progress
        if ($ticket->status === 'in_progress') {
            $ticket->update(['status' => 'open']);
        }

        return back()->with('success', __('messages.reply_sent'));
    }
}
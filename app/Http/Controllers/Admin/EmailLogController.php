<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailLogController extends Controller
{
    /**
     * Display email logs
     */
    public function index(Request $request)
    {
        $query = EmailLog::with('user');

        // Search by recipient or subject
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('to', 'like', "%{$request->search}%")
                  ->orWhere('subject', 'like', "%{$request->search}%");
            });
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $emails = $query->latest()->paginate(20);

        // Statistics
        $stats = [
            'total' => EmailLog::count(),
            'sent' => EmailLog::sent()->count(),
            'failed' => EmailLog::failed()->count(),
            'pending' => EmailLog::pending()->count(),
            'today' => EmailLog::whereDate('created_at', today())->count(),
        ];

        return view('admin.email-logs.index', compact('emails', 'stats'));
    }

    /**
     * Show email details
     */
    public function show(EmailLog $emailLog)
    {
        $emailLog->load('user');
        
        return view('admin.email-logs.show', compact('emailLog'));
    }

    /**
     * Resend failed email
     */
    public function resend(EmailLog $emailLog)
    {
        if ($emailLog->status === 'sent') {
            return back()->with('error', 'This email was already sent successfully!');
        }

        try {
            // Send email
            Mail::raw($emailLog->content, function ($message) use ($emailLog) {
                $message->to($emailLog->to)
                        ->subject($emailLog->subject);
            });

            // Update log
            $emailLog->update([
                'status' => 'sent',
                'error_message' => null,
            ]);

            return back()->with('success', 'Email resent successfully!');
            
        } catch (\Exception $e) {
            // Update with new error
            $emailLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to resend email: ' . $e->getMessage());
        }
    }

    /**
     * Delete email log
     */
    public function destroy(EmailLog $emailLog)
    {
        $emailLog->delete();

        return redirect()->route('admin.email-logs.index')
            ->with('success', 'Email log deleted successfully!');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of the user's notifications.
     */
    public function index()
    {
        $notifications = $this->notificationService->getUserNotifications(15);
        $unreadCount = $this->notificationService->getUnreadCount();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Notification $notification): RedirectResponse
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access');
        }

        $success = $this->notificationService->markAsRead($notification);

        if (! $success) {
            return back()->with('error', __('messages.notification_not_found'));
        }

        return back()->with('success', __('messages.notification_marked_read'));
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $count = $this->notificationService->markAllAsRead();

        return back()->with('success', __('messages.notifications_marked_read', ['count' => $count]));
    }
}

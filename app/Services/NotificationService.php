<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationService
{
    /**
     * Create a new notification for a user.
     */
    public function create(int $userId, string $type, string $title, string $message, ?array $data = null): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
            'data' => $data,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Notification $notification): bool
    {
        if ($notification->user_id !== Auth::id()) {
            return false;
        }

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark all notifications for the authenticated user as read.
     */
    public function markAllAsRead(): int
    {
        return Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Delete a specific notification.
     */
    public function delete(Notification $notification): bool
    {
        if ($notification->user_id !== Auth::id()) {
            return false;
        }

        return $notification->delete();
    }

    /**
     * Get notifications for the authenticated user.
     */
    public function getUserNotifications(int $perPage = 10)
    {
        return Notification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get unread count for the authenticated user.
     */
    public function getUnreadCount(): int
    {
        return Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();
    }
}

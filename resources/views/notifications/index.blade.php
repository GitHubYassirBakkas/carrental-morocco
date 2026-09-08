@extends('layouts.app')

@section('title', __('messages.notifications_title'))

@section('content')

@push('styles')
<style>
.notification-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 24px;
    transition: all 0.3s ease;
}

.notification-card:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(200, 157, 102, 0.3);
    transform: translateY(-2px);
}

.notification-card.unread {
    background: rgba(200, 157, 102, 0.08);
    border-color: rgba(200, 157, 102, 0.2);
}

.notification-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: rgba(200, 157, 102, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #C89D66;
    flex-shrink: 0;
}

.unread-badge {
    background: linear-gradient(135deg, #C89D66 0%, #B8935E 100%);
    color: white;
    font-size: 11px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.mark-read-btn {
    background: rgba(200, 157, 102, 0.1);
    color: #C89D66;
    border: 1px solid rgba(200, 157, 102, 0.3);
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
}

.mark-read-btn:hover {
    background: rgba(200, 157, 102, 0.2);
    border-color: rgba(200, 157, 102, 0.5);
}

.mark-all-btn {
    background: linear-gradient(135deg, #C89D66 0%, #B8935E 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(200, 157, 102, 0.3);
    transition: all 0.2s;
}

.mark-all-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(200, 157, 102, 0.4);
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    background: rgba(200, 157, 102, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #C89D66;
}
</style>
@endpush

<div class="min-h-screen bg-[#0a0a0a] py-12">
    <div class="max-w-4xl mx-auto px-6">

        <!-- Header -->
        <div class="mb-10">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-[#C89D66]/10 rounded-2xl flex items-center justify-center">
                        <svg class="w-9 h-9 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl sm:text-5xl font-bold text-white">{{ __('messages.notifications_title') }}</h1>
                        <p class="text-gray-400 text-lg mt-1">{{ __('messages.notifications_subtitle') }}</p>
                    </div>
                </div>
                @if($unreadCount > 0)
                    <form action="{{ route('notifications.read-all') }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="mark-all-btn">
                            {{ __('messages.mark_all_as_read') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Notifications List -->
        @if($notifications->count() > 0)
            <div class="space-y-4">
                @foreach($notifications as $notification)
                    <div class="notification-card {{ !$notification->is_read ? 'unread' : '' }}">
                        <div class="flex items-start gap-4">
                            <!-- Icon -->
                            <div class="notification-icon">
                                @if($notification->type === 'booking_confirmed')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @elseif($notification->type === 'booking_cancelled')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @elseif($notification->type === 'payment_received')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @elseif($notification->type === 'admin_message')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                    </svg>
                                @else
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                @endif
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                                    <h3 class="text-white font-semibold text-lg">{{ $notification->title }}</h3>
                                    @if(!$notification->is_read)
                                        <span class="unread-badge">{{ __('messages.unread_notifications') }}</span>
                                    @endif
                                </div>
                                <p class="text-gray-400 text-sm mb-3">{{ $notification->message }}</p>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <span class="text-gray-500 text-xs">
                                        @if($notification->created_at->diffInMinutes() < 1)
                                            {{ __('messages.just_now') }}
                                        @elseif($notification->created_at->diffInHours() < 1)
                                            {{ __('messages.minutes_ago', ['count' => (int) $notification->created_at->diffInMinutes()]) }}
                                        @elseif($notification->created_at->diffInDays() < 1)
                                            {{ __('messages.hours_ago', ['count' => (int) $notification->created_at->diffInHours()]) }}
                                        @else
                                            {{ __('messages.days_ago', ['count' => (int) $notification->created_at->diffInDays()]) }}
                                        @endif
                                    </span>
                                    @if(!$notification->is_read)
                                        <form action="{{ route('notifications.read', $notification) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="mark-read-btn">
                                                {{ __('messages.mark_as_read') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($notifications->hasPages())
                <div class="mt-8 flex justify-center">
                    {{ $notifications->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                </div>
                <h3 class="text-white text-xl font-semibold mb-2">{{ __('messages.notifications_empty') }}</h3>
                <p class="text-gray-400">{{ __('messages.notifications_empty_desc') }}</p>
            </div>
        @endif

    </div>
</div>

@endsection

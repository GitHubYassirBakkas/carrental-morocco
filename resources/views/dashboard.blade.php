@extends('layouts.app')

@section('title', __('messages.dashboard_title'))

@section('content')
<div class="container mx-auto px-6 py-12">

    {{-- Welcome Section --}}
    <div class="mb-8">
        <div class="flex items-center gap-4">
            @if($user->profile_photo_path)
                <img src="{{ asset('storage/'.$user->profile_photo_path) }}"
                     class="w-16 h-16 rounded-full object-cover border-2 border-[#C89D66]/30">
            @else
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-[#C89D66] to-[#B8935E] flex items-center justify-center text-white text-2xl font-bold">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
            <div>
                <h1 class="text-3xl font-bold text-white">{{ __('messages.dashboard_welcome', ['name' => $user->name]) }}</h1>
                <p class="text-gray-400">{{ __('messages.dashboard_subtitle') }}</p>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        {{-- Total Bookings --}}
        <div class="bg-[#0f0f0f] border border-white/5 rounded-2xl p-6 hover:border-[#C89D66]/30 transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
            <p class="text-gray-500 text-sm mb-1">{{ __('messages.dashboard_total_bookings') }}</p>
            <p class="text-3xl font-bold text-white">{{ $totalBookings }}</p>
        </div>

        {{-- Active Rentals --}}
        <div class="bg-[#0f0f0f] border border-white/5 rounded-2xl p-6 hover:border-[#C89D66]/30 transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
            <p class="text-gray-500 text-sm mb-1">{{ __('messages.dashboard_active_rentals') }}</p>
            <p class="text-3xl font-bold text-white">{{ $activeRentals }}</p>
        </div>

        {{-- Wishlist Cars --}}
        <div class="bg-[#0f0f0f] border border-white/5 rounded-2xl p-6 hover:border-[#C89D66]/30 transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-red-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-gray-500 text-sm mb-1">{{ __('messages.dashboard_wishlist_cars') }}</p>
            <p class="text-3xl font-bold text-white">{{ $wishlistCars }}</p>
        </div>

        {{-- Total Amount Spent --}}
        <div class="bg-[#0f0f0f] border border-white/5 rounded-2xl p-6 hover:border-[#C89D66]/30 transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-[#C89D66]/10 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-gray-500 text-sm mb-1">{{ __('messages.dashboard_total_spent') }}</p>
            <p class="text-3xl font-bold text-[#C89D66]">{{ number_format($totalSpent, 0) }} <span class="text-lg text-gray-500">MAD</span></p>
        </div>

        {{-- Completed Rentals --}}
        <div class="bg-[#0f0f0f] border border-white/5 rounded-2xl p-6 hover:border-[#C89D66]/30 transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-500/10 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-gray-500 text-sm mb-1">{{ __('messages.dashboard_completed_rentals') }}</p>
            <p class="text-3xl font-bold text-white">{{ $completedRentals }}</p>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-[#0f0f0f] border border-white/5 rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-white mb-4">{{ __('messages.dashboard_quick_actions') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('cars.index') }}" class="flex flex-col items-center justify-center p-4 bg-white/5 rounded-xl hover:bg-white/10 transition-all duration-300 group">
                <div class="w-12 h-12 bg-[#C89D66]/10 rounded-xl flex items-center justify-center mb-3 group-hover:bg-[#C89D66]/20 transition-all">
                    <svg class="w-6 h-6 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <span class="text-white font-medium text-sm">{{ __('messages.dashboard_browse_cars') }}</span>
            </a>

            <a href="{{ route('my_booking.index') }}" class="flex flex-col items-center justify-center p-4 bg-white/5 rounded-xl hover:bg-white/10 transition-all duration-300 group">
                <div class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center mb-3 group-hover:bg-blue-500/20 transition-all">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <span class="text-white font-medium text-sm">{{ __('messages.dashboard_my_bookings') }}</span>
            </a>

            <a href="{{ route('wishlist.index') }}" class="flex flex-col items-center justify-center p-4 bg-white/5 rounded-xl hover:bg-white/10 transition-all duration-300 group">
                <div class="w-12 h-12 bg-red-500/10 rounded-xl flex items-center justify-center mb-3 group-hover:bg-red-500/20 transition-all">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                <span class="text-white font-medium text-sm">{{ __('messages.dashboard_wishlist') }}</span>
            </a>

            <a href="{{ route('profile.edit') }}" class="flex flex-col items-center justify-center p-4 bg-white/5 rounded-xl hover:bg-white/10 transition-all duration-300 group">
                <div class="w-12 h-12 bg-green-500/10 rounded-xl flex items-center justify-center mb-3 group-hover:bg-green-500/20 transition-all">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <span class="text-white font-medium text-sm">{{ __('messages.dashboard_edit_profile') }}</span>
            </a>
        </div>
    </div>

    {{-- Recent Bookings --}}
    <div class="bg-[#0f0f0f] border border-white/5 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-white">{{ __('messages.dashboard_recent_bookings') }}</h2>
            @if($recentBookings->count() > 0)
                <a href="{{ route('my_booking.index') }}" class="text-[#C89D66] text-sm hover:underline">{{ __('messages.dashboard_view_all') }}</a>
            @endif
        </div>

        @if($recentBookings->count() > 0)
            <div class="space-y-4">
                @foreach($recentBookings as $booking)
                    <div class="bg-white/5 border border-white/5 rounded-xl p-4 hover:border-white/10 transition-all duration-300">
                        <div class="flex flex-col md:flex-row gap-4">
                            {{-- Car Image --}}
                            <div class="w-full md:w-32 h-24 rounded-lg overflow-hidden flex-shrink-0">
                                <img src="{{ $booking->car->image_url }}"
                                     alt="{{ $booking->car->full_name }}"
                                     class="w-full h-full object-cover">
                            </div>

                            {{-- Booking Details --}}
                            <div class="flex-1">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h3 class="text-white font-semibold">{{ $booking->car->full_name }}</h3>
                                        <p class="text-gray-500 text-sm">
                                            {{ $booking->start_date->format('M d, Y') }} - {{ $booking->end_date->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[#C89D66] font-bold">{{ number_format($booking->total_amount, 0) }} MAD</p>
                                        <span class="inline-block px-2 py-1 text-xs rounded-full
                                            @if($booking->status === 'completed') bg-green-500/10 text-green-400
                                            @elseif($booking->status === 'active') bg-blue-500/10 text-blue-400
                                            @elseif($booking->status === 'confirmed') bg-yellow-500/10 text-yellow-400
                                            @elseif($booking->status === 'cancelled') bg-red-500/10 text-red-400
                                            @else bg-gray-500/10 text-gray-400 @endif">
                                            {{ __('messages.status_' . $booking->status) }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Review Button for completed bookings without review --}}
                                @if($booking->status === 'completed' && !$booking->review)
                                    <div class="mt-3">
                                        <a href="{{ route('reviews.create', $booking) }}"
                                           class="inline-flex items-center gap-2 text-sm text-[#C89D66] hover:underline">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                            </svg>
                                            {{ __('messages.leave_review') }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Empty State --}}
            <div class="text-center py-12">
                <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">{{ __('messages.dashboard_no_bookings') }}</h3>
                <p class="text-gray-500 mb-4">{{ __('messages.dashboard_no_bookings_desc') }}</p>
                <a href="{{ route('cars.index') }}"
                   class="inline-block bg-gradient-to-r from-[#C89D66] to-[#B8935E] text-white px-6 py-3 rounded-xl text-sm font-semibold hover:opacity-90 transition-opacity">
                    {{ __('messages.dashboard_browse_cars') }}
                </a>
            </div>
        @endif
    </div>

    {{-- Wishlist Empty State --}}
    @if($wishlistCars === 0)
        <div class="mt-8 bg-[#0f0f0f] border border-white/5 rounded-2xl p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-500/10 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold">{{ __('messages.dashboard_wishlist_empty') }}</h3>
                        <p class="text-gray-500 text-sm">{{ __('messages.dashboard_wishlist_empty_desc') }}</p>
                    </div>
                </div>
                <a href="{{ route('cars.index') }}"
                   class="bg-white/10 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-white/20 transition-all">
                    {{ __('messages.dashboard_add_cars') }}
                </a>
            </div>
        </div>
    @endif

</div>
@endsection

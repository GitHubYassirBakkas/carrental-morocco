@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] text-gray-100">
    <div class="max-w-7xl mx-auto p-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm mb-6">
            <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-white">Users</a>
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-white font-medium">{{ $user->name }}</span>
        </nav>

        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
            <div>
                <div class="flex items-center gap-4 mb-2">
                    <h1 class="text-4xl font-bold text-white">{{ $user->name }}</h1>
                    
                    <!-- Role Badge -->
                    <span class="px-3 py-1 rounded-lg text-sm font-bold
                        {{ $user->role == 'admin' 
                            ? 'bg-purple-900/30 text-purple-400 border border-purple-700/50' 
                            : 'bg-blue-900/30 text-blue-400 border border-blue-700/50' }}">
                        {{ ucfirst($user->role) }}
                    </span>

                    <!-- Status Badge -->
                    @if($user->is_banned)
                        <span class="px-3 py-1 bg-red-900/30 text-red-400 border border-red-700/50 rounded-lg text-sm font-bold">
                            Banned
                        </span>
                    @else
                        <span class="px-3 py-1 bg-emerald-900/30 text-emerald-400 border border-emerald-700/50 rounded-lg text-sm font-bold">
                            Active
                        </span>
                    @endif
                </div>
                <p class="text-gray-400">{{ $user->email }}</p>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('admin.users.edit', $user) }}" 
                   class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition">
                    Edit User
                </a>
                <a href="{{ route('admin.users.index') }}" 
                   class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-lg transition">
                    Back
                </a>
            </div>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 mb-6">
                <p class="text-emerald-300">{{ session('success') }}</p>
            </div>
        @endif

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Bookings -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-400 text-sm">Total Bookings</p>
                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-white">{{ $stats['total_bookings'] }}</p>
            </div>

            <!-- Active Bookings -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-400 text-sm">Active Bookings</p>
                    <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-white">{{ $stats['active_bookings'] }}</p>
            </div>

            <!-- Total Spent -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-400 text-sm">Total Spent</p>
                    <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                </div>
                <p class="text-3xl font-bold text-white">{{ number_format($stats['total_spent'], 0) }} MAD</p>
            </div>

            <!-- Reviews -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-400 text-sm">Reviews</p>
                    <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
                <div class="flex items-center gap-2">
                    <p class="text-3xl font-bold text-white">{{ $stats['total_reviews'] }}</p>
                    @if($stats['avg_rating'])
                        <span class="text-yellow-400 text-sm">({{ number_format($stats['avg_rating'], 1) }} ⭐)</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column (2/3) -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Recent Bookings -->
                <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-white mb-4">Recent Bookings</h3>

                    @if($user->bookings->count() > 0)
                        <div class="space-y-3">
                            @foreach($user->bookings as $booking)
                                <div class="bg-[#0f1520] border border-gray-700 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h4 class="text-white font-bold">{{ $booking->car->full_name ?? 'N/A' }}</h4>
                                            <p class="text-gray-400 text-sm">
                                                {{ $booking->start_date ? $booking->start_date->format('M d') : 'N/A' }} - 
                                                {{ $booking->end_date ? $booking->end_date->format('M d, Y') : 'N/A' }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-orange-400 font-bold">{{ number_format($booking->total_amount, 0) }} MAD</p>
                                            <span class="px-2 py-1 text-xs rounded-lg font-semibold
                                                @if($booking->status == 'pending') bg-yellow-900/30 text-yellow-400
                                                @elseif($booking->status == 'confirmed') bg-blue-900/30 text-blue-400
                                                @elseif($booking->status == 'active') bg-emerald-900/30 text-emerald-400
                                                @elseif($booking->status == 'completed') bg-gray-700 text-gray-300
                                                @elseif($booking->status == 'cancelled') bg-red-900/30 text-red-400
                                                @endif">
                                                {{ ucfirst($booking->status) }}
                                            </span>
                                        </div>
                                    </div>
                                    <a href="{{ route('admin.bookings.show', $booking) }}" 
                                       class="text-orange-400 hover:text-orange-300 text-sm">
                                        View Details →
                                    </a>
                                </div>
                            @endforeach
                        </div>

                        @if($stats['total_bookings'] > 10)
                            <div class="text-center mt-4">
                                <p class="text-gray-400 text-sm">Showing 10 of {{ $stats['total_bookings'] }} bookings</p>
                            </div>
                        @endif
                    @else
                        <p class="text-gray-400 text-center py-8">No bookings yet</p>
                    @endif
                </div>

                <!-- Recent Reviews -->
                @if($user->reviews && $user->reviews->count() > 0)
                <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-white mb-4">Recent Reviews</h3>

                    <div class="space-y-3">
                        @foreach($user->reviews as $review)
                            <div class="bg-[#0f1520] border border-gray-700 rounded-lg p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <p class="text-white font-semibold">{{ $review->car->full_name ?? 'N/A' }}</p>
                                        <div class="flex items-center gap-1 mt-1">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endfor
                                        </div>
                                    </div>
                                    <span class="text-gray-400 text-xs">{{ $review->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-gray-300 text-sm">{{ $review->comment }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>

            <!-- Right Column (1/3) -->
            <div class="space-y-6">
                
                <!-- User Info -->
                <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-white mb-4">User Information</h3>

                    <div class="space-y-4">
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Email</p>
                            <p class="text-white">{{ $user->email }}</p>
                        </div>

                        @if($user->phone)
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Phone</p>
                            <p class="text-white">{{ $user->phone }}</p>
                        </div>
                        @endif

                        <div>
                            <p class="text-gray-400 text-sm mb-1">Role</p>
                            <p class="text-white">{{ ucfirst($user->role) }}</p>
                        </div>

                        <div>
                            <p class="text-gray-400 text-sm mb-1">Joined</p>
                            <p class="text-white">{{ $user->created_at->format('M d, Y') }}</p>
                            <p class="text-gray-500 text-xs">{{ $user->created_at->diffForHumans() }}</p>
                        </div>

                        <div>
                            <p class="text-gray-400 text-sm mb-1">Last Updated</p>
                            <p class="text-white">{{ $user->updated_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-white mb-4">Quick Actions</h3>

                    <div class="space-y-3">
                        <a href="{{ route('admin.users.edit', $user) }}" 
                           class="block w-full px-4 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition text-center">
                            Edit User
                        </a>

                        @if($user->role !== 'admin')
                            @if($user->is_banned)
                                <form action="{{ route('admin.users.unban', $user) }}" method="POST">
                                    @csrf
                                    <button type="submit" 
                                            class="w-full px-4 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition">
                                        Unban User
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.users.ban', $user) }}" method="POST" 
                                      onsubmit="return confirm('Ban this user?')">
                                    @csrf
                                    <button type="submit" 
                                            class="w-full px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                                        Ban User
                                    </button>
                                </form>
                            @endif

                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" 
                                  onsubmit="return confirm('Delete this user permanently?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="w-full px-4 py-3 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-lg transition">
                                    Delete User
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Booking Stats -->
                <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-white mb-4">Booking Statistics</h3>

                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Completed</span>
                            <span class="text-emerald-400 font-bold">{{ $stats['completed_bookings'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Active</span>
                            <span class="text-blue-400 font-bold">{{ $stats['active_bookings'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-400">Cancelled</span>
                            <span class="text-red-400 font-bold">{{ $stats['cancelled_bookings'] }}</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>
@endsection
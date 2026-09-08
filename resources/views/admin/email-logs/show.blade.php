@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] text-gray-100">
    <div class="max-w-5xl mx-auto p-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm mb-6">
            <a href="{{ route('admin.email-logs.index') }}" class="text-gray-400 hover:text-white">Email Logs</a>
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-white font-medium">Email Details</span>
        </nav>

        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
            <div>
                <h1 class="text-4xl font-bold text-white mb-2">Email Details</h1>
                <p class="text-gray-400">{{ $emailLog->subject }}</p>
            </div>

            <div class="flex gap-3">
                @if($emailLog->status === 'failed')
                    <form action="{{ route('admin.email-logs.resend', $emailLog) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition"
                                onclick="return confirm('Resend this email?')">
                            🔄 Resend Email
                        </button>
                    </form>
                @endif
                
                <a href="{{ route('admin.email-logs.index') }}" 
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

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 mb-6">
                <p class="text-red-300">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Main Content -->
        <div class="space-y-6">

            <!-- Email Info Card -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-8">
                <h2 class="text-xl font-bold text-white mb-6">Email Information</h2>

                <div class="grid grid-cols-2 gap-6">
                    <!-- Recipient -->
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Recipient</p>
                        <p class="text-white font-semibold">{{ $emailLog->to }}</p>
                        @if($emailLog->user)
                            <p class="text-gray-500 text-xs mt-1">{{ $emailLog->user->name }}</p>
                        @endif
                    </div>

                    <!-- Subject -->
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Subject</p>
                        <p class="text-white font-semibold">{{ $emailLog->subject }}</p>
                    </div>

                    <!-- Status -->
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Status</p>
                        <span class="px-3 py-1 rounded-lg text-sm font-bold
                            @if($emailLog->status == 'sent') bg-emerald-900/30 text-emerald-400 border border-emerald-700/50
                            @elseif($emailLog->status == 'failed') bg-red-900/30 text-red-400 border border-red-700/50
                            @elseif($emailLog->status == 'pending') bg-yellow-900/30 text-yellow-400 border border-yellow-700/50
                            @endif">
                            {{ ucfirst($emailLog->status) }}
                        </span>
                    </div>

                    <!-- Type -->
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Type</p>
                        @if($emailLog->type)
                            <span class="px-3 py-1 rounded text-sm font-semibold
                                @if($emailLog->type == 'booking') bg-blue-900/30 text-blue-400
                                @elseif($emailLog->type == 'payment') bg-emerald-900/30 text-emerald-400
                                @elseif($emailLog->type == 'damage') bg-red-900/30 text-red-400
                                @else bg-gray-700 text-gray-300
                                @endif">
                                {{ ucfirst($emailLog->type) }}
                            </span>
                        @else
                            <span class="text-gray-500 text-sm">N/A</span>
                        @endif
                    </div>

                    <!-- Sent At -->
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Sent At</p>
                        <p class="text-white">{{ $emailLog->created_at->format('M d, Y') }}</p>
                        <p class="text-gray-500 text-xs">{{ $emailLog->created_at->format('H:i A') }}</p>
                    </div>

                    <!-- Message ID -->
                    @if($emailLog->message_id)
                    <div>
                        <p class="text-gray-400 text-sm mb-1">Message ID</p>
                        <p class="text-gray-300 text-xs font-mono">{{ $emailLog->message_id }}</p>
                    </div>
                    @endif
                </div>

                <!-- Error Message (if failed) -->
                @if($emailLog->status === 'failed' && $emailLog->error_message)
                    <div class="mt-6 p-4 bg-red-900/10 border border-red-700/30 rounded-lg">
                        <p class="text-red-400 text-sm font-semibold mb-2">❌ Error Message:</p>
                        <p class="text-red-300 text-sm font-mono">{{ $emailLog->error_message }}</p>
                    </div>
                @endif
            </div>

            <!-- Email Content -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-8">
                <h2 class="text-xl font-bold text-white mb-6">Email Content</h2>

                <div class="bg-white text-gray-900 p-6 rounded-lg">
                    <div class="prose max-w-none">
                        {!! nl2br(e($emailLog->content)) !!}
                    </div>
                </div>
            </div>

            <!-- Related Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- User Info -->
                @if($emailLog->user)
                <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-white mb-4">User Information</h3>
                    
                    <div class="space-y-3">
                        <div>
                            <p class="text-gray-400 text-xs mb-1">Name</p>
                            <p class="text-white">{{ $emailLog->user->name }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs mb-1">Email</p>
                            <p class="text-white">{{ $emailLog->user->email }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs mb-1">Role</p>
                            <p class="text-white">{{ ucfirst($emailLog->user->role) }}</p>
                        </div>
                        <a href="{{ route('admin.users.show', $emailLog->user) }}" 
                           class="inline-block text-orange-400 hover:text-orange-300 text-sm mt-2">
                            View User Details →
                        </a>
                    </div>
                </div>
                @endif

                <!-- Booking Info -->
                @if($emailLog->booking)
                <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-white mb-4">Booking Information</h3>
                    
                    <div class="space-y-3">
                        <div>
                            <p class="text-gray-400 text-xs mb-1">Booking ID</p>
                            <p class="text-white">#{{ $emailLog->booking->id }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs mb-1">Car</p>
                            <p class="text-white">{{ $emailLog->booking->car->full_name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs mb-1">Status</p>
                            <x-admin.booking-status-badge :status="$emailLog->booking->status" />
                        </div>
                        <a href="{{ route('admin.bookings.show', $emailLog->booking) }}" 
                           class="inline-block text-orange-400 hover:text-orange-300 text-sm mt-2">
                            View Booking Details →
                        </a>
                    </div>
                </div>
                @endif

                <!-- Technical Info -->
                <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-white mb-4">Technical Information</h3>
                    
                    <div class="space-y-3">
                        @if($emailLog->ip_address)
                        <div>
                            <p class="text-gray-400 text-xs mb-1">IP Address</p>
                            <p class="text-white font-mono text-sm">{{ $emailLog->ip_address }}</p>
                        </div>
                        @endif
                        
                        @if($emailLog->user_agent)
                        <div>
                            <p class="text-gray-400 text-xs mb-1">User Agent</p>
                            <p class="text-white text-xs">{{ Str::limit($emailLog->user_agent, 50) }}</p>
                        </div>
                        @endif
                        
                        <div>
                            <p class="text-gray-400 text-xs mb-1">Created At</p>
                            <p class="text-white text-sm">{{ $emailLog->created_at->format('M d, Y H:i:s') }}</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Actions -->
            <div class="flex gap-4">
                @if($emailLog->status === 'failed')
                    <form action="{{ route('admin.email-logs.resend', $emailLog) }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" 
                                class="w-full px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-xl transition"
                                onclick="return confirm('Resend this email?')">
                            🔄 Resend Email
                        </button>
                    </form>
                @endif

                <form action="{{ route('admin.email-logs.destroy', $emailLog) }}" method="POST" class="flex-1">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition"
                            onclick="return confirm('Delete this email log?')">
                        🗑️ Delete Log
                    </button>
                </form>
            </div>

        </div>

    </div>
</div>
@endsection

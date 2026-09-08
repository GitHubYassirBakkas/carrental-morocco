@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] text-gray-100">
    <div class="max-w-7xl mx-auto p-8">

        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-bold text-white mb-2">Email Logs</h1>
                <p class="text-gray-400">Track and manage all sent emails</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Emails -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-400 text-sm">Total Emails</p>
                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-white">{{ $stats['total'] }}</p>
            </div>

            <!-- Sent -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-400 text-sm">Sent</p>
                    <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-white">{{ $stats['sent'] }}</p>
            </div>

            <!-- Failed -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-400 text-sm">Failed</p>
                    <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-white">{{ $stats['failed'] }}</p>
            </div>

            <!-- Today -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-400 text-sm">Today</p>
                    <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-3xl font-bold text-white">{{ $stats['today'] }}</p>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="bg-[#1a2332] border border-gray-800 rounded-xl p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Search -->
                <div>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Search recipient or subject..."
                           class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white placeholder-gray-500">
                </div>

                <!-- Status Filter -->
                <div>
                    <select name="status" class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white">
                        <option value="">All Status</option>
                        <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>

                <!-- Date From -->
                <div>
                    <input type="date" 
                           name="date_from" 
                           value="{{ request('date_from') }}"
                           class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white">
                </div>

                <!-- Date To -->
                <div>
                    <input type="date" 
                           name="date_to" 
                           value="{{ request('date_to') }}"
                           class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white">
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-xl transition">
                        Filter
                    </button>
                    <a href="{{ route('admin.email-logs.index') }}" class="px-4 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-xl transition">
                        Reset
                    </a>
                </div>
            </div>
        </form>

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

        <!-- Email Logs Table -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-[#0f1520] border-b border-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Recipient</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Subject</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Type</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Sent At</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @forelse($emails as $email)
                        <tr class="hover:bg-[#0f1520] transition-colors">
                            <!-- Recipient -->
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-white font-semibold">{{ $email->to }}</p>
                                    @if($email->user)
                                        <p class="text-gray-400 text-xs">{{ $email->user->name }}</p>
                                    @endif
                                </div>
                            </td>

                            <!-- Subject -->
                            <td class="px-6 py-4">
                                <p class="text-white">{{ Str::limit($email->subject, 40) }}</p>
                            </td>

                            <!-- Type -->
                            <td class="px-6 py-4">
                                @if($email->type)
                                    <span class="px-2 py-1 rounded text-xs font-semibold
                                        @if($email->type == 'booking') bg-blue-900/30 text-blue-400
                                        @elseif($email->type == 'payment') bg-emerald-900/30 text-emerald-400
                                        @elseif($email->type == 'damage') bg-red-900/30 text-red-400
                                        @else bg-gray-700 text-gray-300
                                        @endif">
                                        {{ ucfirst($email->type) }}
                                    </span>
                                @else
                                    <span class="text-gray-500 text-xs">N/A</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-lg text-xs font-bold
                                    @if($email->status == 'sent') bg-emerald-900/30 text-emerald-400 border border-emerald-700/50
                                    @elseif($email->status == 'failed') bg-red-900/30 text-red-400 border border-red-700/50
                                    @elseif($email->status == 'pending') bg-yellow-900/30 text-yellow-400 border border-yellow-700/50
                                    @endif">
                                    {{ ucfirst($email->status) }}
                                </span>
                            </td>

                            <!-- Sent At -->
                            <td class="px-6 py-4">
                                <p class="text-gray-400 text-sm">{{ $email->created_at->format('M d, Y') }}</p>
                                <p class="text-gray-500 text-xs">{{ $email->created_at->format('H:i A') }}</p>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- View -->
                                    <a href="{{ route('admin.email-logs.show', $email) }}" 
                                       class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition">
                                        View
                                    </a>

                                    <!-- Resend (only for failed) -->
                                    @if($email->status === 'failed')
                                        <form action="{{ route('admin.email-logs.resend', $email) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="px-3 py-2 bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold rounded-lg transition"
                                                    onclick="return confirm('Resend this email?')">
                                                Resend
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Delete -->
                                    <form action="{{ route('admin.email-logs.destroy', $email) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg transition"
                                                onclick="return confirm('Delete this email log?')">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <p class="text-gray-400">No email logs found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $emails->links() }}
        </div>

    </div>
</div>
@endsection
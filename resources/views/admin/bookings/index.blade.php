@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0f1419] p-6 space-y-6">
    
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-white tracking-tight">Bookings</h1>
                <span class="px-3 py-1.5 bg-[#C89D66]/20 border border-[#C89D66]/40 text-[#C89D66] text-xs rounded-lg font-semibold">
                    {{ $bookings->total() }} total
                </span>
            </div>
            <p class="text-gray-400 mt-1.5 text-sm">Manage and monitor all rental reservations</p>
        </div>
        
        <div class="flex items-center gap-3">
            <button class="px-4 py-2.5 bg-[#1a1f2e] hover:bg-[#252b3b] border border-gray-700 text-gray-300 rounded-lg transition-all text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Export
            </button>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 flex items-start gap-3">
            <div class="w-10 h-10 bg-emerald-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="flex-1 font-medium text-emerald-300 text-sm">{{ session('success') }}</p>
            <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 flex items-start gap-3">
            <div class="w-10 h-10 bg-red-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="flex-1 font-medium text-red-300 text-sm">{{ session('error') }}</p>
            <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Pending Card -->
        <div class="bg-[#1a1f2e] border border-gray-800 rounded-lg p-5 hover:border-gray-700 transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-11 h-11 bg-yellow-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-white mb-1">{{ $bookings->where('status', 'pending')->count() }}</div>
            <div class="text-sm text-gray-400">Pending</div>
        </div>

        <!-- Confirmed Card -->
        <div class="bg-[#1a1f2e] border border-gray-800 rounded-lg p-5 hover:border-gray-700 transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-11 h-11 bg-blue-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-white mb-1">{{ $bookings->where('status', 'confirmed')->count() }}</div>
            <div class="text-sm text-gray-400">Confirmed</div>
        </div>

        <!-- Active Card -->
        <div class="bg-[#1a1f2e] border border-gray-800 rounded-lg p-5 hover:border-gray-700 transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-11 h-11 bg-emerald-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-white mb-1">{{ $bookings->where('status', 'active')->count() }}</div>
            <div class="text-sm text-gray-400">Active</div>
        </div>

        <!-- Revenue Card -->
        <div class="bg-[#1a1f2e] border border-gray-800 rounded-lg p-5 hover:border-gray-700 transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-11 h-11 bg-[#C89D66]/10 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-white mb-1">{{ number_format($bookings->sum('total_amount'), 0) }}</div>
            <div class="text-sm text-gray-400">Revenue (MAD)</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-[#1a1f2e] border border-gray-800 rounded-lg p-5">
        <form class="flex flex-wrap gap-3" method="GET">
            <select name="status" class="px-3.5 py-2.5 bg-[#0f1419] border border-gray-800 rounded-lg text-sm text-gray-300 focus:outline-none focus:ring-2 focus:ring-[#C89D66] focus:border-transparent transition-all">
                <option value="">All Status</option>
                @foreach(['pending','confirmed','active','completed','cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status')==$status)>
                        {{ __('messages.statuses.'.$status) }}
                    </option>
                @endforeach
            </select>

            <input type="date" 
                   name="from" 
                   class="px-3.5 py-2.5 bg-[#0f1419] border border-gray-800 rounded-lg text-sm text-gray-300 focus:outline-none focus:ring-2 focus:ring-[#C89D66] focus:border-transparent transition-all" 
                   value="{{ request('from') }}">
            
            <input type="date" 
                   name="to" 
                   class="px-3.5 py-2.5 bg-[#0f1419] border border-gray-800 rounded-lg text-sm text-gray-300 focus:outline-none focus:ring-2 focus:ring-[#C89D66] focus:border-transparent transition-all" 
                   value="{{ request('to') }}">

            <button class="px-5 py-2.5 bg-[#C89D66] hover:bg-[#d4ab76] text-black font-semibold rounded-lg transition-all text-sm">
                Apply
            </button>

            @if(request()->hasAny(['status', 'from', 'to']))
                <a href="{{ route('admin.bookings.index') }}" 
                   class="px-5 py-2.5 bg-[#252b3b] hover:bg-[#2d3444] text-gray-300 font-medium rounded-lg transition-all text-sm">
                    Clear
                </a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="bg-[#1a1f2e] border border-gray-800 rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px]">
                <thead>
                    <tr class="border-b border-gray-800">
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#C89D66] uppercase tracking-wider">ID</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#C89D66] uppercase tracking-wider">Customer</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#C89D66] uppercase tracking-wider">Vehicle</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#C89D66] uppercase tracking-wider">Period</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#C89D66] uppercase tracking-wider">Progress</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#C89D66] uppercase tracking-wider">Remaining</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#C89D66] uppercase tracking-wider">Amount</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-[#C89D66] uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3.5 text-right text-xs font-semibold text-[#C89D66] uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                @forelse($bookings as $booking)
                    <tr class="hover:bg-[#252b3b] transition-colors">
                        
                        <td class="px-5 py-4">
                            <span class="font-mono text-sm font-semibold text-[#C89D66]">#{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </td>

                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-[#C89D66]/10 flex items-center justify-center text-[#C89D66] font-semibold text-xs border border-[#C89D66]/20">
                                    {{ substr($booking->user->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-medium text-white text-sm">{{ $booking->user->name ?? '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ Str::limit($booking->user->email ?? '-', 20) }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-medium text-gray-200 text-sm">{{ $booking->car->full_name }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="text-sm text-gray-300">{{ $booking->start_date->format('M d') }}</div>
                            <div class="text-xs text-gray-500">→ {{ $booking->end_date->format('M d, H:i') }}</div>
                        </td>

                        <td class="px-5 py-4">
                            <div class="w-32">
                                <div class="w-full bg-gray-800 rounded-full h-2 overflow-hidden mb-1">
                                    <div class="h-2 rounded-full transition-all
                                        @class([
                                            'bg-blue-500' => $booking->timeline_status=='upcoming',
                                            'bg-emerald-500' => $booking->timeline_status=='ongoing',
                                            'bg-red-500' => $booking->timeline_status=='late',
                                            'bg-gray-500' => $booking->timeline_status=='completed',
                                        ])"
                                        style="width: {{ $booking->progress_percent ?? 0 }}%">
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500">{{ $booking->progress_percent ?? 0 }}%</div>
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            @if($booking->timeline_status === 'late')
                                <div class="text-red-400 font-semibold text-xs">
                                    LATE +{{ $booking->late_minutes ?? 0 }}m
                                </div>
                                <div class="text-xs text-red-500">
                                    {{ number_format($booking->late_fee ?? 0, 0) }} MAD
                                </div>
                            @else
                                <div class="text-gray-400 text-xs">{{ $booking->remaining_time ?? '-' }}</div>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            <div class="font-semibold text-white text-sm">{{ number_format($booking->total_amount, 0) }}</div>
                            <div class="text-xs text-gray-500">MAD</div>
                        </td>

                        <td class="px-5 py-4">
                            @if($booking->status === 'pending')
    <div class="flex flex-col gap-1.5">
        <x-admin.booking-status-badge :status="$booking->status" />

        {{-- Confirm --}}
        <form method="POST" action="{{ route('admin.bookings.confirm', $booking) }}"
              onsubmit="return confirm('Confirm this booking and send email?')">
            @csrf
            <button type="submit"
                    class="w-full px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-200
                           bg-emerald-500/10 hover:bg-emerald-500 
                           text-emerald-400 hover:text-black
                           border border-emerald-500/40 hover:border-emerald-500
                           flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                Confirm
            </button>
        </form>

        {{-- Cancel --}}
        <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}"
              onsubmit="return confirm('Cancel this booking?')">
            @csrf
            <button type="submit"
                    class="w-full px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-200
                           bg-red-500/10 hover:bg-red-500
                           text-red-400 hover:text-white
                           border border-red-500/40 hover:border-red-500
                           flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Cancel
            </button>
        </form>

    </div>
@else
    <x-admin.booking-status-badge :status="$booking->status" />
@endif
                        </td>

                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('admin.bookings.show', $booking) }}"
                               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#C89D66] hover:bg-[#d4ab76] text-black font-semibold rounded-lg transition-all text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-5 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 mb-3 rounded-lg bg-gray-800 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <p class="text-gray-300 font-medium mb-1 text-sm">No bookings found</p>
                                <p class="text-gray-500 text-xs">Try adjusting your filters</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($bookings->hasPages())
            <div class="px-5 py-4 border-t border-gray-800 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Showing {{ $bookings->firstItem() }} to {{ $bookings->lastItem() }} of {{ $bookings->total() }} results
                </div>
                <div>
                    {{ $bookings->withQueryString()->links() }}
                </div>
            </div>
        @endif
    </div>

</div>
@endsection

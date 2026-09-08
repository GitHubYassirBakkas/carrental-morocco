@extends('admin.layouts.app')
@section('title', 'Support Dashboard')

@section('content')
<div class="p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white">Support Dashboard</h1>
            <p class="text-gray-400 mt-1">Manage customer support tickets</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-2xl border border-blue-500/20 p-5">
            <p class="text-gray-400 text-sm mb-1">Open</p>
            <p class="text-3xl font-bold text-blue-400">{{ $stats['open'] }}</p>
        </div>
        <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-2xl border border-yellow-500/20 p-5">
            <p class="text-gray-400 text-sm mb-1">In Progress</p>
            <p class="text-3xl font-bold text-yellow-400">{{ $stats['in_progress'] }}</p>
        </div>
        <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-2xl border border-emerald-500/20 p-5">
            <p class="text-gray-400 text-sm mb-1">Resolved</p>
            <p class="text-3xl font-bold text-emerald-400">{{ $stats['resolved'] }}</p>
        </div>
        <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-2xl border border-gray-700 p-5">
            <p class="text-gray-400 text-sm mb-1">Total</p>
            <p class="text-3xl font-bold text-white">{{ $stats['total'] }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-2xl border border-gray-800 p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4">
            <select name="status" onchange="this.form.submit()"
                    class="bg-gray-900 text-white rounded-xl px-4 py-2.5 border border-gray-700 focus:border-[#C89D66] outline-none text-sm">
                <option value="">All Status</option>
                <option value="open"        {{ request('status') == 'open'        ? 'selected' : '' }}>Open</option>
                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="resolved"    {{ request('status') == 'resolved'    ? 'selected' : '' }}>Resolved</option>
                <option value="closed"      {{ request('status') == 'closed'      ? 'selected' : '' }}>Closed</option>
            </select>
            <select name="category" onchange="this.form.submit()"
                    class="bg-gray-900 text-white rounded-xl px-4 py-2.5 border border-gray-700 focus:border-[#C89D66] outline-none text-sm">
                <option value="">All Categories</option>
                <option value="booking"   {{ request('category') == 'booking'   ? 'selected' : '' }}>Booking</option>
                <option value="payment"   {{ request('category') == 'payment'   ? 'selected' : '' }}>Payment</option>
                <option value="complaint" {{ request('category') == 'complaint' ? 'selected' : '' }}>Complaint</option>
                <option value="other"     {{ request('category') == 'other'     ? 'selected' : '' }}>Other</option>
            </select>
            @if(request('status') || request('category'))
                <a href="{{ route('admin.support.index') }}"
                   class="px-4 py-2.5 bg-gray-800 hover:bg-gray-700 text-gray-400 rounded-xl text-sm transition">
                    Clear Filters
                </a>
            @endif
        </form>
    </div>

    {{-- Tickets Table --}}
    <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-2xl border border-gray-800 overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-800">
                    <th class="text-left px-6 py-4 text-gray-400 text-sm font-semibold">Ticket</th>
                    <th class="text-left px-6 py-4 text-gray-400 text-sm font-semibold">User</th>
                    <th class="text-left px-6 py-4 text-gray-400 text-sm font-semibold">Category</th>
                    <th class="text-left px-6 py-4 text-gray-400 text-sm font-semibold">Status</th>
                    <th class="text-left px-6 py-4 text-gray-400 text-sm font-semibold">Priority</th>
                    <th class="text-left px-6 py-4 text-gray-400 text-sm font-semibold">Date</th>
                    <th class="text-left px-6 py-4 text-gray-400 text-sm font-semibold">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-800">
                @forelse($tickets as $ticket)
                    <tr class="hover:bg-white/5 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="text-[#C89D66] font-mono text-sm font-bold">{{ $ticket->ticket_number }}</span>
                                @if($ticket->unreadCount() > 0)
                                    <span class="w-5 h-5 bg-[#C89D66] text-black text-xs font-bold rounded-full flex items-center justify-center">
                                        {{ $ticket->unreadCount() }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-white text-sm font-medium mt-0.5">{{ Str::limit($ticket->subject, 40) }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-white text-sm">{{ $ticket->user->name }}</p>
                            <p class="text-gray-500 text-xs">{{ $ticket->user->email }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-gray-800 text-gray-300 rounded-lg text-xs capitalize">
                                {{ $ticket->category }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusColors = [
                                    'open'        => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                                    'in_progress' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/30',
                                    'resolved'    => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                                    'closed'      => 'bg-gray-500/10 text-gray-400 border-gray-500/30',
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-semibold border {{ $statusColors[$ticket->status] ?? '' }}">
                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $priorityColors = [
                                    'low'    => 'text-green-400',
                                    'medium' => 'text-yellow-400',
                                    'high'   => 'text-red-400',
                                ];
                            @endphp
                            <span class="text-sm font-semibold {{ $priorityColors[$ticket->priority] ?? '' }} capitalize">
                                {{ $ticket->priority }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-400 text-sm">
                            {{ $ticket->created_at->diffForHumans() }}
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.support.show', $ticket) }}"
                               class="px-4 py-2 bg-[#C89D66]/10 hover:bg-[#C89D66]/20 text-[#C89D66] rounded-xl text-sm font-semibold transition">
                                View & Reply
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center text-gray-500">
                            No tickets found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($tickets->hasPages())
            <div class="px-6 py-4 border-t border-gray-800">
                {{ $tickets->withQueryString()->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
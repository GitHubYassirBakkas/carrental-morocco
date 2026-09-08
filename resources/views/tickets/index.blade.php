@extends('layouts.app')
@section('title', __('messages.my_tickets'))

@section('content')
<div class="min-h-screen bg-[#0a0a0a] py-12">
    <div class="max-w-5xl mx-auto px-6">

        {{-- Header --}}
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-[#C89D66]/10 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white">{{ __('messages.my_tickets') }}</h1>
                    <p class="text-gray-400 text-sm mt-1">{{ __('messages.support_subtitle') }}</p>
                </div>
            </div>
            <a href="{{ route('contact') }}"
               class="px-6 py-3 bg-gradient-to-r from-[#C89D66] to-[#B8935E] text-white font-bold rounded-xl shadow-lg shadow-[#C89D66]/20 hover:shadow-[#C89D66]/40 transition transform hover:scale-105 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('messages.new_ticket') }}
            </a>
        </div>

        {{-- Stats Row --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
            @php
                $open        = $tickets->where('status', 'open')->count();
                $in_progress = $tickets->where('status', 'in_progress')->count();
                $resolved    = $tickets->where('status', 'resolved')->count();
                $total       = $tickets->count();
            @endphp
            <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-2xl border border-blue-500/20 p-4 text-center">
                <p class="text-2xl font-bold text-blue-400">{{ $open }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ __('messages.status_open') }}</p>
            </div>
            <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-2xl border border-yellow-500/20 p-4 text-center">
                <p class="text-2xl font-bold text-yellow-400">{{ $in_progress }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ __('messages.status_in_progress') }}</p>
            </div>
            <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-2xl border border-emerald-500/20 p-4 text-center">
                <p class="text-2xl font-bold text-emerald-400">{{ $resolved }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ __('messages.status_resolved') }}</p>
            </div>
            <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-2xl border border-gray-700 p-4 text-center">
                <p class="text-2xl font-bold text-white">{{ $total }}</p>
                <p class="text-gray-500 text-xs mt-1">{{ __('messages.tickets_count') }}</p>
            </div>
        </div>

        {{-- Tickets List --}}
        <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl border border-gray-800 overflow-hidden">

            @if(session('success'))
                <div class="mx-6 mt-6 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-4 flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-emerald-400 text-sm font-medium">{{ session('success') }}</p>
                </div>
            @endif

            <div class="divide-y divide-gray-800">
                @forelse($tickets as $ticket)
                    <a href="{{ route('tickets.show', $ticket) }}"
                       class="flex items-start gap-4 px-6 py-5 hover:bg-white/5 transition group">

                        {{-- Status dot --}}
                        <div class="mt-2 flex-shrink-0">
                            @if($ticket->status === 'open')
                                <div class="w-3 h-3 bg-blue-500 rounded-full animate-pulse"></div>
                            @elseif($ticket->status === 'in_progress')
                                <div class="w-3 h-3 bg-yellow-500 rounded-full animate-pulse"></div>
                            @elseif($ticket->status === 'resolved')
                                <div class="w-3 h-3 bg-emerald-500 rounded-full"></div>
                            @else
                                <div class="w-3 h-3 bg-gray-600 rounded-full"></div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-1 flex-wrap">
                                <span class="text-[#C89D66] text-xs font-mono font-bold">{{ $ticket->ticket_number }}</span>
                                @php
                                    $statusColors = [
                                        'open'        => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                                        'in_progress' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/30',
                                        'resolved'    => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                                        'closed'      => 'bg-gray-500/10 text-gray-400 border-gray-500/30',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold border {{ $statusColors[$ticket->status] ?? '' }}">
                                    {{ __('messages.status_' . $ticket->status) }}
                                </span>
                                <span class="px-2 py-0.5 bg-gray-800 text-gray-400 rounded-lg text-xs capitalize">
                                    {{ __('messages.subject_' . $ticket->category) }}
                                </span>
                                @if($ticket->unreadCount() > 0)
                                    <span class="px-2 py-0.5 bg-[#C89D66] text-black text-xs font-bold rounded-full animate-pulse">
                                        {{ $ticket->unreadCount() }} {{ __('messages.new_reply') }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-white font-semibold truncate group-hover:text-[#C89D66] transition text-sm">
                                {{ $ticket->subject }}
                            </p>
                            @if($ticket->latestMessage)
                                <p class="text-gray-500 text-xs truncate mt-1">
                                    {{ $ticket->is_admin ? '💬 Support: ' : '👤 ' }}{{ Str::limit($ticket->latestMessage->message, 70) }}
                                </p>
                            @endif
                        </div>

                        {{-- Date + Arrow --}}
                        <div class="flex-shrink-0 text-right">
                            <p class="text-gray-500 text-xs">{{ $ticket->updated_at->diffForHumans() }}</p>
                            <svg class="w-4 h-4 text-gray-600 mt-2 ml-auto group-hover:text-[#C89D66] transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
                @empty
                    <div class="py-20 text-center">
                        <div class="w-20 h-20 bg-gray-800/50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                        </div>
                        <p class="text-white font-semibold text-lg mb-2">{{ __('messages.no_tickets') }}</p>
                        <p class="text-gray-500 text-sm mb-6">{{ __('messages.no_tickets_desc') }}</p>
                        <a href="{{ route('contact') }}"
                           class="inline-flex items-center gap-2 px-6 py-3 bg-[#C89D66]/10 hover:bg-[#C89D66]/20 text-[#C89D66] rounded-xl font-semibold transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ __('messages.new_ticket') }}
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection

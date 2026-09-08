@extends('layouts.app')
@section('title', $ticket->ticket_number)

@section('content')
<div class="min-h-screen bg-[#0a0a0a] py-12">
    <div class="max-w-4xl mx-auto px-6">

        {{-- Back Button --}}
        <a href="{{ route('tickets.index') }}"
           class="inline-flex items-center gap-2 text-gray-400 hover:text-white transition mb-8 group">
            <svg class="w-5 h-5 group-hover:-translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ __('messages.back_to_tickets') }}
        </a>

        {{-- Ticket Header --}}
        <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl border border-gray-800 p-6 mb-6">
            <div class="flex items-start justify-between flex-wrap gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-[#C89D66] font-mono font-bold text-sm">{{ $ticket->ticket_number }}</span>
                        {{-- Status --}}
                        @php
                            $statusColors = [
                                'open'        => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                                'in_progress' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/30',
                                'resolved'    => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                                'closed'      => 'bg-gray-500/10 text-gray-400 border-gray-500/30',
                            ];
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ $statusColors[$ticket->status] ?? '' }}">
                            {{ __('messages.status_' . $ticket->status) }}
                        </span>
                        {{-- Priority --}}
                        @php
                            $priorityColors = [
                                'low'    => 'bg-green-500/10 text-green-400 border-green-500/30',
                                'medium' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/30',
                                'high'   => 'bg-red-500/10 text-red-400 border-red-500/30',
                            ];
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ $priorityColors[$ticket->priority] ?? '' }}">
                            {{ __('messages.priority_' . $ticket->priority) }}
                        </span>
                    </div>
                    <h1 class="text-2xl font-bold text-white">{{ $ticket->subject }}</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        {{ __('messages.ticket_opened') }} {{ $ticket->created_at->format('d M Y, H:i') }}
                        · {{ __('messages.category') }}: <span class="text-[#C89D66]">{{ __('messages.subject_' . $ticket->category) }}</span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Messages Thread --}}
        <div class="space-y-4 mb-6">
            @foreach($messages as $msg)
                @if($msg->is_admin)
                    {{-- Admin Message --}}
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-[#C89D66] rounded-full flex items-center justify-center flex-shrink-0 shadow-lg shadow-[#C89D66]/20">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="bg-gradient-to-br from-[#C89D66]/10 to-[#C89D66]/5 rounded-2xl rounded-tl-none border border-[#C89D66]/20 p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-[#C89D66] font-bold text-sm">{{ __('messages.support_team') }}</span>
                                    <span class="px-2 py-0.5 bg-[#C89D66]/20 text-[#C89D66] text-xs rounded-full font-semibold">Admin</span>
                                </div>
                                <p class="text-gray-300 text-sm leading-relaxed">{{ $msg->message }}</p>
                            </div>
                            <p class="text-gray-600 text-xs mt-1 ml-1">{{ $msg->created_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>
                @else
                    {{-- User Message --}}
                    <div class="flex items-start gap-4 flex-row-reverse">
                        <div class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center flex-shrink-0 text-white font-bold text-sm">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="flex-1">
                            <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-2xl rounded-tr-none border border-gray-700 p-4">
                                <div class="flex items-center gap-2 mb-2 justify-end">
                                    <span class="text-white font-bold text-sm">{{ auth()->user()->name }}</span>
                                </div>
                                <p class="text-gray-300 text-sm leading-relaxed text-right">{{ $msg->message }}</p>
                            </div>
                            <p class="text-gray-600 text-xs mt-1 text-right mr-1">{{ $msg->created_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Reply Form --}}
        @if(!in_array($ticket->status, ['resolved', 'closed']))
            <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl border border-gray-800 p-6">

                @if(session('success'))
                    <div class="mb-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-emerald-400 text-sm">{{ session('success') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('tickets.reply', $ticket) }}">
                    @csrf
                    <label class="block text-sm font-semibold text-gray-400 mb-3">
                        {{ __('messages.your_reply') }}
                    </label>
                    <textarea name="message"
                              rows="4"
                              required
                              placeholder="{{ __('messages.reply_placeholder') }}"
                              class="w-full bg-gray-900 text-white rounded-xl px-4 py-3 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition text-sm resize-none mb-4"></textarea>
                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-6 py-3 bg-gradient-to-r from-[#C89D66] to-[#B8935E] hover:from-[#B8935E] hover:to-[#A8835E] text-white font-bold rounded-xl transition shadow-lg shadow-[#C89D66]/20 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            {{ __('messages.send_reply') }}
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl border border-gray-800 p-6 text-center">
                <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-400 font-semibold">{{ __('messages.ticket_closed_msg') }}</p>
                <a href="{{ route('tickets.index') }}"
                   class="inline-block mt-4 px-6 py-2 bg-[#C89D66]/10 text-[#C89D66] rounded-xl hover:bg-[#C89D66]/20 transition text-sm font-semibold">
                    {{ __('messages.open_new_ticket') }}
                </a>
            </div>
        @endif

    </div>
</div>
@endsection
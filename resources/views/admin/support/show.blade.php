@extends('admin.layouts.app')
@section('title', $ticket->ticket_number)

@section('content')
<div class="p-6 max-w-4xl">

    {{-- Back --}}
    <a href="{{ route('admin.support.index') }}"
       class="inline-flex items-center gap-2 text-gray-400 hover:text-white transition mb-6 group">
        <svg class="w-5 h-5 group-hover:-translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Support
    </a>

    {{-- Ticket Info --}}
    <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-2xl border border-gray-800 p-6 mb-6">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-[#C89D66] font-mono font-bold">{{ $ticket->ticket_number }}</span>
                    @php
                        $statusColors = [
                            'open'        => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                            'in_progress' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/30',
                            'resolved'    => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                            'closed'      => 'bg-gray-500/10 text-gray-400 border-gray-500/30',
                        ];
                    @endphp
                    <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ $statusColors[$ticket->status] ?? '' }}">
                        {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                    </span>
                </div>
                <h1 class="text-2xl font-bold text-white">{{ $ticket->subject }}</h1>
                <p class="text-gray-500 text-sm mt-1">
                    From: <span class="text-white">{{ $ticket->user->name }}</span>
                    ({{ $ticket->user->email }})
                    · {{ $ticket->created_at->format('d M Y, H:i') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Messages --}}
    <div class="space-y-4 mb-6">
        @foreach($messages as $msg)
            @if($msg->is_admin)
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-[#C89D66] rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="bg-[#C89D66]/10 border border-[#C89D66]/20 rounded-2xl rounded-tl-none p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-[#C89D66] font-bold text-sm">Support Team</span>
                                <span class="px-2 py-0.5 bg-[#C89D66]/20 text-[#C89D66] text-xs rounded-full">Admin</span>
                            </div>
                            <p class="text-gray-300 text-sm">{{ $msg->message }}</p>
                        </div>
                        <p class="text-gray-600 text-xs mt-1">{{ $msg->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>
            @else
                <div class="flex items-start gap-4 flex-row-reverse">
                    <div class="w-10 h-10 bg-gray-700 rounded-full flex items-center justify-center flex-shrink-0 text-white font-bold text-sm">
                        {{ strtoupper(substr($ticket->user->name, 0, 1)) }}
                    </div>
                    <div class="flex-1">
                        <div class="bg-[#1a1a1a] border border-gray-700 rounded-2xl rounded-tr-none p-4">
                            <div class="flex items-center gap-2 mb-2 justify-end">
                                <span class="text-white font-bold text-sm">{{ $ticket->user->name }}</span>
                            </div>
                            <p class="text-gray-300 text-sm text-right">{{ $msg->message }}</p>
                        </div>
                        <p class="text-gray-600 text-xs mt-1 text-right">{{ $msg->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Admin Reply Form --}}
    <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-2xl border border-gray-800 p-6">

        @if(session('success'))
            <div class="mb-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-3 text-emerald-400 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.support.reply', $ticket) }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">Reply to Customer</label>
                <textarea name="message"
                          rows="4"
                          required
                          placeholder="Write your reply here..."
                          class="w-full bg-gray-900 text-white rounded-xl px-4 py-3 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition text-sm resize-none"></textarea>
            </div>

            {{-- Status Selector --}}
            <div>
                <label class="block text-sm font-semibold text-gray-400 mb-2">Update Status</label>
                <div class="flex flex-wrap gap-3">
                    @foreach(['open' => ['blue', 'Open'], 'in_progress' => ['yellow', 'In Progress'], 'resolved' => ['emerald', 'Resolved'], 'closed' => ['gray', 'Closed']] as $val => [$color, $label])
                        <label class="cursor-pointer">
                            <input type="radio" name="status" value="{{ $val }}"
                                   {{ $ticket->status === $val ? 'checked' : '' }}
                                   class="sr-only peer">
                            <span class="px-4 py-2 rounded-xl text-sm font-semibold border border-{{ $color }}-500/30 text-{{ $color }}-400 peer-checked:bg-{{ $color }}-500/20 peer-checked:border-{{ $color }}-500 transition">
                                {{ $label }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-[#C89D66] to-[#B8935E] hover:from-[#B8935E] hover:to-[#A8835E] text-white font-bold rounded-xl transition shadow-lg shadow-[#C89D66]/20 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Send Reply & Update Status
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
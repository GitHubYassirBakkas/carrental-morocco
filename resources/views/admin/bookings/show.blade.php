@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] text-gray-100">
    <div class="max-w-[1600px] mx-auto p-8 space-y-6">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm mb-4">
            <a href="{{ route('admin.bookings.index') }}" class="text-gray-400 hover:text-white transition-colors">Bookings</a>
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-white font-medium">Booking #{{ $booking->id }}</span>
        </nav>

        <!-- Header -->
        <div class="flex items-start justify-between mb-6">
            <div>
                <div class="flex items-center gap-4 mb-2">
                    <h1 class="text-4xl font-bold text-white">Booking #{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}</h1>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold
                        @class([
                            'bg-yellow-900/30 text-yellow-400 border border-yellow-700/50' => $booking->status=='pending',
                            'bg-blue-900/30 text-blue-400 border border-blue-700/50' => $booking->status=='confirmed',
                            'bg-emerald-900/30 text-emerald-400 border border-emerald-700/50' => $booking->status=='active',
                            'bg-gray-700 text-gray-300 border border-gray-600' => $booking->status=='completed',
                            'bg-red-900/30 text-red-400 border border-red-700/50' => $booking->status=='cancelled',
                        ])">
                        {{ ucfirst($booking->status) }}
                    </span>
                </div>
                <p class="text-gray-400 text-sm">{{ $booking->car->full_name ?? 'Vehicle not assigned' }} • Created {{ $booking->created_at->diffForHumans() }}</p>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.bookings.invoice', $booking) }}" 
                   class="px-4 py-2.5 bg-[#1f2937] hover:bg-[#374151] border border-gray-700 text-gray-300 rounded-lg transition-all text-sm font-medium flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Invoice
                </a>
                <a href="{{ route('admin.bookings.index') }}"
                   class="px-4 py-2.5 bg-[#1f2937] hover:bg-[#374151] border border-gray-700 text-gray-300 rounded-lg transition-all text-sm font-medium flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back
                </a>
            </div>
        </div>

        <!-- Alerts -->
        @if(session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-emerald-300 text-sm">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-red-300 text-sm">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Progress Tracker -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            @php
                $stages = ['pending','confirmed','active','completed'];
                $currentIndex = array_search($booking->status, $stages);
                $currentIndex = $currentIndex === false ? 0 : $currentIndex;
            @endphp

            <div class="flex items-center justify-between relative mb-6">
                @foreach($stages as $index => $stage)
                    @php
                        $isCompleted = $index < $currentIndex;
                        $isCurrent = $index === $currentIndex;
                    @endphp

                    <div class="flex flex-col items-center w-full relative">
                        @if(!$loop->first)
                            <div class="absolute top-6 -left-1/2 w-full h-1 {{ $isCompleted ? 'bg-orange-500' : 'bg-gray-700' }} z-0"></div>
                        @endif

                        <div class="w-12 h-12 flex items-center justify-center rounded-xl text-sm font-bold transition-all z-10
                            @class([
                                'bg-orange-500 text-black' => $isCompleted,
                                'bg-orange-500 text-black animate-pulse' => $isCurrent,
                                'bg-gray-700 text-gray-400' => !$isCompleted && !$isCurrent,
                            ])">
                            @if($isCompleted)
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </div>

                        <span class="text-xs mt-2 font-medium {{ $isCurrent ? 'text-orange-400' : 'text-gray-400' }}">
                            {{ ucfirst($stage) }}
                        </span>
                    </div>
                @endforeach
            </div>

            <div class="w-full bg-gray-700 h-2 rounded-full overflow-hidden">
                <div class="bg-orange-500 h-2 rounded-full transition-all duration-500"
                     style="width: {{ $booking->progress_percent ?? 0 }}%">
                </div>
            </div>
            <p class="text-xs text-right mt-2 text-gray-400">{{ $booking->progress_percent ?? 0 }}% Complete</p>
        </div>
        

        <!-- Quick Actions -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <h2 class="text-lg font-bold text-white mb-4">Quick Actions</h2>

            <div class="flex flex-wrap gap-3">
                @if($booking->status == 'pending')
                    <form method="POST" action="{{ route('admin.bookings.confirm', $booking) }}">
                        @csrf
                        <button class="px-6 py-3 bg-[#d97706] hover:bg-[#f59e0b] text-black font-semibold rounded-lg transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Confirm Booking
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.bookings.cancel', $booking) }}" onsubmit="return confirm('Cancel this booking?')">
                        @csrf
                        <button class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cancel Booking
                        </button>
                    </form>
                @endif

              @if($booking->status == 'confirmed')
                @if($booking->checkinInspection)
                    <form method="POST" action="{{ route('admin.bookings.start', $booking) }}">
                        @csrf
                        <button class="px-6 py-3 bg-gradient-to-r from-[#C89D66] to-[#d4ab76] text-black font-semibold rounded-xl">
                            Start Rental
                        </button>
                    </form>
                @else
                    <div class="px-6 py-3 bg-yellow-500/20 border border-yellow-500/30 text-yellow-400 rounded-xl text-sm font-semibold">
                        ⚠️ Check-in inspection required
                    </div>
                @endif
            @endif


             @if($booking->status == 'active')
                @if($booking->checkoutInspection)
                    <form method="POST" action="{{ route('admin.bookings.complete', $booking) }}">
                        @csrf
                        <button class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl">
                            Complete Rental
                        </button>
                    </form>
                @else
                    <div class="px-6 py-3 bg-red-500/20 border border-red-500/30 text-red-400 rounded-xl text-sm font-semibold">
                        ⚠️ Check-out inspection required
                    </div>
                @endif
            @endif

            </div>
        </div>
                              

{{-- ✅ INVOICE SECTION WITH NULL CHECKS --}}
@if($booking->invoice)
<div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold text-white">Invoice</h2>
        <span class="px-3 py-1 text-xs rounded-lg font-semibold
            @if($booking->invoice->status == 'paid') bg-emerald-900/30 text-emerald-400 border border-emerald-700/50
            @elseif($booking->invoice->status == 'unpaid') bg-red-900/30 text-red-400 border border-red-700/50
            @elseif($booking->invoice->status == 'pending') bg-yellow-900/30 text-yellow-400 border border-yellow-700/50
            @else bg-gray-700 text-gray-300 border border-gray-600
            @endif">
            {{ ucfirst($booking->invoice->status) }}
        </span>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
            <p class="text-xs text-gray-500 mb-1">Invoice #</p>
            <p class="text-white font-bold">#{{ str_pad($booking->invoice->id, 4, '0', STR_PAD_LEFT) }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 mb-1">Total Amount</p>
            <p class="text-orange-400 font-bold text-lg">{{ number_format($booking->invoice->total_amount ?? 0, 2) }} MAD</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 mb-1">Amount Paid</p>
            <p class="text-emerald-400 font-bold">{{ number_format($booking->invoice->paid_amount ?? 0, 2) }} MAD</p>
        </div>
        <div>
            <p class="text-xs text-gray-500 mb-1">Balance</p>
            <p class="text-white font-bold">{{ number_format($booking->invoice->balance ?? 0, 2) }} MAD</p>
        </div>
    </div>

    <div class="flex gap-3">
        <a href="{{ route('admin.invoices.show', $booking->invoice) }}"
           class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-lg transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            View Invoice
        </a>

        <a href="{{ route('admin.bookings.invoice', $booking) }}"
           class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-lg transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Download PDF
        </a>
    </div>
</div>
@else
<!-- No Invoice Yet -->
<div class="bg-yellow-900/20 border border-yellow-700/50 rounded-xl p-6">
    <div class="flex items-center gap-3 mb-4">
        <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <h4 class="text-yellow-300 font-bold">No Invoice Created Yet</h4>
            <p class="text-yellow-200/70 text-sm">Invoice will be created when booking is confirmed.</p>
        </div>
    </div>
    
    @if($booking->status === 'pending')
        <form action="{{ route('admin.bookings.confirm', $booking) }}" method="POST">
            @csrf
            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                Confirm Booking & Create Invoice
            </button>
        </form>
    @endif
</div>
@endif



{{-- Deposit Status Section --}}
@if($booking->status == 'pending' && $booking->invoice)
    @php
        $minimumDeposit = $booking->minimum_deposit ?? 0;
        $currentPaid = $booking->invoice->paid_amount ?? 0;
        $remaining = max(0, $minimumDeposit - $currentPaid);
        $progress = $minimumDeposit > 0 ? min(100, ($currentPaid / $minimumDeposit) * 100) : 0;
    @endphp

    <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
        <div class="flex justify-between items-start mb-4">
            <h3 class="text-lg font-bold text-white">💰 Deposit Status</h3>
            @if($booking->isDepositOverdue())
                <span class="px-3 py-1 text-xs rounded-lg bg-red-900/30 text-red-400 border border-red-700/50 font-semibold animate-pulse">
                    ⏰ OVERDUE
                </span>
            @else
                <span class="px-3 py-1 text-xs rounded-lg bg-yellow-900/30 text-yellow-400 border border-yellow-700/50 font-semibold">
                    ⏳ Pending
                </span>
            @endif
        </div>

        {{-- Progress Bar --}}
        <div class="mb-4">
            <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-400">Minimum Required: {{ number_format($minimumDeposit, 2) }} MAD</span>
                <span class="text-white font-bold">{{ number_format($progress, 0) }}%</span>
            </div>
            <div class="w-full bg-gray-700 h-3 rounded-full overflow-hidden">
                <div class="h-3 rounded-full transition-all duration-500 
                    {{ $progress >= 100 ? 'bg-emerald-500' : 'bg-yellow-500' }}"
                     style="width: {{ min(100, $progress) }}%">
                </div>
            </div>
        </div>

        {{-- Payment Info --}}
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="bg-[#0f1520] border border-gray-700 rounded-lg p-3">
                <p class="text-xs text-gray-500 mb-1">Paid So Far</p>
                <p class="text-emerald-400 font-bold">{{ number_format($currentPaid, 2) }} MAD</p>
            </div>
            <div class="bg-[#0f1520] border border-gray-700 rounded-lg p-3">
                <p class="text-xs text-gray-500 mb-1">Still Needed</p>
                <p class="text-yellow-400 font-bold">{{ number_format($remaining, 2) }} MAD</p>
            </div>
            <div class="bg-[#0f1520] border border-gray-700 rounded-lg p-3">
                <p class="text-xs text-gray-500 mb-1">Total Amount</p>
                <p class="text-white font-bold">{{ number_format($booking->total_amount, 2) }} MAD</p>
            </div>
        </div>

        {{-- Deadline Warning --}}
        @if($booking->deposit_due_at)
        <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-yellow-300 text-sm font-semibold mb-1">
                        Customer must pay minimum {{ number_format($minimumDeposit, 2) }} MAD
                    </p>
                    <p class="text-yellow-200 text-xs">
                        Deadline: <strong>{{ $booking->deposit_due_at->format('M d, Y H:i') }}</strong>
                        ({{ $booking->deposit_due_at->diffForHumans() }})
                    </p>
                    @if($booking->isDepositOverdue())
                        <p class="text-red-400 text-xs font-bold mt-2">
                            🚨 Payment deadline passed! Booking will be auto-cancelled soon.
                        </p>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Quick Action --}}
        <div class="mt-4">
            <a href="{{ route('admin.invoices.show', $booking->invoice) }}"
               class="block w-full px-4 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition-all text-center">
                💳 Record Payment to Confirm Booking
            </a>
        </div>
    </div>

@elseif($booking->status == 'pending' && !$booking->invoice)
    {{-- No Invoice Created Yet --}}
    <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4">
        <p class="text-yellow-300 text-sm mb-3">
            ⚠️ Invoice must be created before tracking deposit status.
        </p>
        <form action="{{ route('admin.bookings.confirm', $booking) }}" method="POST">
            @csrf
            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                Confirm Booking & Create Invoice
            </button>
        </form>
    </div>

@elseif($booking->deposit_paid && $booking->invoice)
    {{-- Deposit Confirmed --}}
    <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-emerald-300 font-bold">✅ Booking Confirmed!</p>
                <p class="text-emerald-200 text-sm">
                    Deposit paid: {{ number_format($booking->car->deposit_amount ?? 0, 2) }} MAD
                    @if($booking->deposit_paid_at)
                        on {{ $booking->deposit_paid_at->format('M d, Y H:i') }}
                    @endif
                </p>
            </div>
        </div>
    </div>
@endif

{{-- ═══ DEPOSIT MANAGEMENT ═══ --}}
@if($booking->deposit_payment_intent_id)
<div id="deposit-management" class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-2xl border border-gray-800 p-6 mt-6">

    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        Security Deposit Management
    </h3>

    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-900 rounded-xl p-4 text-center">
            <p class="text-gray-500 text-xs mb-1">Deposit Amount</p>
            <p class="text-white font-bold text-xl">{{ number_format($booking->car->deposit_amount, 0) }} MAD</p>
        </div>
        <div class="bg-gray-900 rounded-xl p-4 text-center">
            <p class="text-gray-500 text-xs mb-1">Status</p>
            @php
                $depColors = [
                    'held'     => 'text-purple-400',
                    'released' => 'text-emerald-400',
                    'charged'  => 'text-red-400',
                    'pending'  => 'text-yellow-400',
                ];
            @endphp
            <p id="deposit-status-text" class="font-bold text-xl {{ $depColors[$booking->deposit_status] ?? 'text-gray-400' }}">
                {{ ucfirst($booking->deposit_status) }}
            </p>
        </div>
        <div class="bg-gray-900 rounded-xl p-4 text-center">
            <p class="text-gray-500 text-xs mb-1">Charged</p>
            <p class="text-red-400 font-bold text-xl">{{ number_format($booking->deposit_charged_amount, 0) }} MAD</p>
        </div>
    </div>

    <div id="deposit-feedback" class="hidden mb-4"></div>

    <div id="deposit-status-block" class="mb-4">
        @if($booking->deposit_status === 'held')
            <div class="bg-purple-500/10 border border-purple-500/30 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-purple-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <p class="text-purple-300 text-sm font-semibold">Deposit held</p>
            </div>
        @elseif($booking->deposit_status === 'released')
            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-emerald-300 text-sm font-semibold">Deposit released</p>
            </div>
        @endif
    </div>

    @if($booking->deposit_status === 'held')
        <div id="deposit-actions" class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Release Deposit --}}
            <button type="button"
                    id="release-deposit-button"
                    data-url="{{ route('admin.deposit.release', $booking) }}"
                    data-csrf="{{ csrf_token() }}"
                    class="w-full py-3 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 font-bold rounded-xl border border-emerald-500/30 transition flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                    <svg data-release-icon class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <svg data-release-spinner class="hidden w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span data-release-label>Release Deposit</span>
                </button>

            <template id="deposit-released-template">
                <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 flex items-start gap-3 transition-all duration-300">
                    <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-emerald-300 text-sm font-semibold">Deposit released</p>
                </div>
            </template>

            {{-- Charge Deposit --}}
            <div x-data="{ open: false }">
                <button @click="open = !open"
                        class="w-full py-3 bg-red-500/10 hover:bg-red-500/20 text-red-400 font-bold rounded-xl border border-red-500/30 transition flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ⚠️ Charge Deposit
                </button>

                <div x-show="open" class="mt-4 bg-gray-900 rounded-xl p-4 border border-red-500/20">
                    <form method="POST" action="{{ route('admin.deposit.charge', $booking) }}">
                        @csrf
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs text-gray-400 mb-1">Amount to Charge (MAD)</label>
                                <input type="number"
                                       name="charge_amount"
                                       min="1"
                                       max="{{ $booking->car->deposit_amount }}"
                                       placeholder="e.g. 1500"
                                       required
                                       class="w-full bg-gray-800 text-white rounded-lg px-3 py-2 border border-gray-700 text-sm outline-none focus:border-red-400">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 mb-1">Reason</label>
                                <input type="text"
                                       name="reason"
                                       placeholder="e.g. Late return 3 hours, damage on bumper"
                                       required
                                       class="w-full bg-gray-800 text-white rounded-lg px-3 py-2 border border-gray-700 text-sm outline-none focus:border-red-400">
                            </div>
                            <button type="submit"
                                    class="w-full py-2 bg-red-500 hover:bg-red-600 text-white font-bold rounded-lg text-sm transition">
                                Confirm Charge
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    @else
        <div class="text-center py-4 text-gray-500 text-sm">
            Deposit {{ $booking->deposit_status }} — no actions available.
        </div>
    @endif

</div>
@endif

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Customer & Car -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Customer -->
                    <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Customer</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Name</p>
                                <p class="text-white font-medium">{{ $booking->user->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Email</p>
                                <p class="text-orange-400 text-sm">{{ $booking->user->email ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Car -->
                    <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Vehicle</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Model</p>
                                <p class="text-white font-medium">{{ $booking->car->full_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Daily Rate</p>
                                <p class="text-orange-400 font-bold text-lg">{{ number_format($booking->daily_rate, 2) }} <span class="text-sm text-gray-400">MAD</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Period & Locations -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Period -->
                    <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Period</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Pickup</p>
                                <p class="text-white font-medium">{{ optional($booking->start_date)->format('M d, Y - H:i') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Return</p>
                                <p class="text-white font-medium">{{ optional($booking->end_date)->format('M d, Y - H:i') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Locations -->
                    <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Locations</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Pickup</p>
                                <p class="text-white font-medium">{{ $booking->pickupLocation->name ?? 'Location #'.$booking->pickup_location_id }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Dropoff</p>
                                <p class="text-white font-medium">{{ $booking->dropoffLocation->name ?? 'Location #'.$booking->dropoff_location_id }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Damages -->
                @if($booking->damages->count() > 0)
                <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-white mb-4">Vehicle Damages ({{ $booking->damages->count() }})</h3>

                    <div class="relative w-full max-w-2xl mx-auto mb-4">
                        <img src="/images/car-top.png" class="w-full opacity-70 rounded-xl">
                        
                        @foreach($booking->damages as $damage)
                            <span class="absolute bg-red-600 text-white text-xs px-2 py-1 rounded shadow-lg font-bold"
                                  style="top: {{ $damage->pos_y ?? 50 }}%; left: {{ $damage->pos_x ?? 50 }}%">
                                {{ strtoupper($damage->part) }}
                            </span>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($booking->damages as $damage)
                            <div class="p-3 bg-red-500/10 border border-red-500/20 rounded-lg">
                                <div class="flex justify-between mb-2">
                                    <span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs font-bold rounded">
                                        {{ strtoupper($damage->stage) }}
                                    </span>
                                    <span class="text-red-400 font-bold text-sm">{{ number_format($damage->estimated_cost ?? 0, 0) }} MAD</span>
                                </div>
                                <p class="text-white font-medium text-sm">{{ ucfirst($damage->part) }} - {{ ucfirst($damage->type) }}</p>
                                <p class="text-gray-400 text-xs mt-1">{{ $damage->description ?? 'No description' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                
              <!-- Financial Summary -->
<div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
    <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Financial Summary</h3>
    <div class="space-y-3">

        {{-- Base Rental --}}
        <div class="flex justify-between pb-3 border-b border-gray-800">
            <div>
                <span class="text-gray-400 text-sm">Base Rental</span>
                <p class="text-xs text-gray-500">{{ $booking->total_days }} days × {{ number_format($booking->daily_rate, 2) }} MAD</p>
            </div>
            <span class="text-white font-medium">
                {{ number_format($booking->total_amount, 2) }} MAD
            </span>
        </div>

        {{-- Damage Charges --}}
        @if($booking->total_checkout_damage > 0)
        <div class="flex justify-between pb-3 border-b border-gray-800">
            <div>
                <span class="text-red-400 text-sm font-semibold">Damage Charges</span>
                <p class="text-xs text-red-300">{{ $booking->checkoutDamages->where('is_chargeable', true)->count() }} damage(s)</p>
            </div>
            <span class="text-red-400 font-semibold">
                {{ number_format($booking->total_checkout_damage, 2) }} MAD
            </span>
        </div>
        @endif

        {{-- Fuel Charge --}}
        @if($booking->fuel_charge > 0)
        <div class="flex justify-between pb-3 border-b border-gray-800">
            <div>
                <span class="text-red-400 text-sm font-semibold">Fuel Charge</span>
                <p class="text-xs text-red-300">{{ $booking->fuel_used }}% fuel used</p>
            </div>
            <span class="text-red-400 font-semibold">
                {{ number_format($booking->fuel_charge, 2) }} MAD
            </span>
        </div>
        @endif

        {{-- Late Fee --}}
        @if($booking->late_fee > 0)
        <div class="flex justify-between pb-3 border-b border-gray-800">
            <div>
                <span class="text-red-400 text-sm font-semibold">Late Return Fee</span>
                <p class="text-xs text-red-300">{{ floor($booking->late_minutes / 60) }} hours late</p>
            </div>
            <span class="text-red-400 font-semibold">
                {{ number_format($booking->late_fee, 2) }} MAD
            </span>
        </div>
        @endif

        {{-- Subtotal (before tax) --}}
        @php
            $subtotalBeforeTax = $booking->total_amount 
                + $booking->total_checkout_damage 
                + ($booking->fuel_charge ?? 0)
                + ($booking->late_fee ?? 0);
        @endphp
        
        @if($booking->total_checkout_damage > 0 || $booking->fuel_charge > 0 || $booking->late_fee > 0)
        <div class="flex justify-between pb-3 border-b border-gray-800">
            <span class="text-gray-400 text-sm">Subtotal</span>
            <span class="text-white font-medium">
                {{ number_format($subtotalBeforeTax, 2) }} MAD
            </span>
        </div>
        @endif

        {{-- Tax (if enabled) --}}
        @php
            $taxPercent = setting('tax_percentage', 0);
            $taxAmount = ($subtotalBeforeTax * $taxPercent) / 100;
        @endphp
        
        @if($taxPercent > 0)
        <div class="flex justify-between pb-3 border-b border-gray-800">
            <span class="text-gray-400 text-sm">Tax ({{ $taxPercent }}%)</span>
            <span class="text-white font-medium">
                {{ number_format($taxAmount, 2) }} MAD
            </span>
        </div>
        @endif

        {{-- Grand Total --}}
        <div class="flex justify-between pt-3">
            <span class="text-orange-400 font-bold text-lg">Grand Total</span>
            <span class="text-orange-400 font-bold text-2xl">
                {{ number_format($subtotalBeforeTax + $taxAmount, 2) }} MAD
            </span>
        </div>

        {{-- Invoice Status --}}
        @if($booking->invoice)
            <div class="flex justify-between">
            <span class="text-gray-400 text-sm">Amount Paid</span>
            <span class="text-emerald-400 font-semibold">
                {{ number_format($booking->invoice->paid_amount ?? 0, 2) }} MAD
            </span>
        </div>

        @if(($booking->invoice->balance ?? 0) > 0)
        <div class="flex justify-between">
            <span class="text-yellow-400 text-sm font-semibold">Balance Due</span>
            <span class="text-yellow-400 font-bold text-lg">
                {{ number_format($booking->invoice->balance ?? 0, 2) }} MAD
            </span>
        </div>
        @else
        <div class="flex justify-between">
            <span class="text-emerald-400 text-sm font-semibold">Status</span>
            <span class="px-3 py-1 bg-emerald-900/30 text-emerald-400 border border-emerald-700/50 rounded-lg text-xs font-bold">
                PAID IN FULL ✓
            </span>
        </div>
        @endif
        @endif

    </div>
</div>

                <!-- Timeline -->
                <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                    <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Timeline</h3>
                    
                    <div class="space-y-4">
                        <div class="flex gap-3">
                            <div class="text-orange-400 text-sm">📅</div>
                            <div>
                                <p class="text-white text-sm font-medium">Booking Created</p>
                                <p class="text-xs text-gray-400">{{ $booking->created_at->diffForHumans() }}</p>
                            </div>
                        </div>

                        @if($booking->checkinInspection)
                        <div class="flex gap-3">
                            <div class="text-emerald-400 text-sm">✓</div>
                            <div>
                                <p class="text-white text-sm font-medium">Check-in Done</p>
                                <p class="text-xs text-gray-400">{{ $booking->checkinInspection->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        @endif

                        @if($booking->checkoutInspection)
                        <div class="flex gap-3">
                            <div class="text-emerald-400 text-sm">✓</div>
                            <div>
                                <p class="text-white text-sm font-medium">Check-out Done</p>
                                <p class="text-xs text-gray-400">{{ $booking->checkoutInspection->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        <!-- Inspections -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Check-in -->
            @if ($errors->any())
                <div class="bg-red-500/20 border border-red-500 p-3 rounded mb-4">
                    @foreach ($errors->all() as $error)
                        <p class="text-red-400 text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-white">Check-in Inspection</h3>
                    @if($booking->checkinInspection)
                        <span class="px-3 py-1 text-xs rounded-lg bg-emerald-900/30 text-emerald-400 border border-emerald-700/50 font-semibold">Completed</span>
                    @else
                        <span class="px-3 py-1 text-xs rounded-lg bg-yellow-900/30 text-yellow-400 border border-yellow-700/50 font-semibold">Pending</span>
                    @endif
                </div>

                @if($booking->checkinInspection)
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Mileage</p>
                            <p class="text-white font-bold">{{ number_format($booking->checkinInspection->mileage) }} km</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Fuel</p>
                            <p class="text-white font-bold">{{ $booking->checkinInspection->fuel_level }}%</p>
                            
                            {{-- FUEL BARS CHECK-IN --}}
                            @php
                                $fuelPercent = $booking->checkinInspection->fuel_level ?? 0;
                                $bars = ceil($fuelPercent / 10);
                            @endphp
                            <div class="mt-3">
                                <div class="flex items-center gap-1">
                                    @for($i = 1; $i <= 10; $i++)
                                        <div class="h-4 w-6 rounded-sm {{ $i <= $bars ? 'bg-green-500' : 'bg-gray-700' }}"></div>
                                    @endfor
                                    <span class="ml-3 text-sm font-bold text-white">
                                        {{ $fuelPercent }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Photos</p>
                            <p class="text-orange-400 font-bold">{{ $booking->checkinInspection->photos->count() }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Damages</p>
                            <p class="text-red-400 font-bold">{{ $booking->damages->where('stage','checkin')->count() }}</p>
                        </div>
                    </div>

                    @if($booking->checkinInspection->photos->count() > 0)
                    <div class="grid grid-cols-3 gap-2">
                        @foreach($booking->checkinInspection->photos as $photo)
                            <img src="{{ asset('storage/'.$photo->path) }}" class="rounded-lg h-20 object-cover border border-gray-700">
                        @endforeach
                    </div>
                    @endif

                    {{-- CODE JDID LI ZEDTIH --}}
                    <div class="bg-[#0f172a] p-5 rounded-xl border border-gray-700 mt-6">
                        <h4 class="text-sm font-bold text-orange-400 mb-4">📸 Inspection Photos</h4>
                        {{-- Upload form --}}
                        <form method="POST"
                              action="{{ route('admin.bookings.inspection.photos.store', $booking->checkinInspection) }}"
                              enctype="multipart/form-data"
                              class="space-y-3">
                            @csrf
                            <input type="file"
                                   name="photos[]"
                                   multiple
                                   required
                                   class="w-full text-sm bg-black/40 border border-gray-700 rounded-lg p-2 text-white">
                            <input type="text"
                                   name="type"
                                   placeholder="Type (front, scratch, tire...)"
                                   class="w-full text-sm bg-black/40 border border-gray-700 rounded-lg p-2 text-white">
                            <textarea name="notes"
                                      rows="2"
                                      placeholder="Notes (optional)"
                                      class="w-full text-sm bg-black/40 border border-gray-700 rounded-lg p-2 text-white"></textarea>
                            <button
                                class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-black font-semibold rounded-lg transition">
                                Upload Photos
                            </button>
                        </form>
                        {{-- Photos grid --}}
                        @if($booking->checkinInspection->photos->count())
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-5">
                                @foreach($booking->checkinInspection->photos as $photo)
                                    <div>
                                        <img src="{{ asset('storage/'.$photo->path) }}"
                                             class="rounded-lg h-28 w-full object-cover border border-gray-700">
                                        <p class="text-xs text-gray-400 mt-1">
                                            {{ $photo->type ?? '—' }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    {{-- END CODE JDID --}}

                    
              @else
            <form method="POST"
                action="{{ route('admin.inspection.store', $booking) }}"
                class="space-y-4">
                @csrf

                <input type="hidden" name="type" value="checkin">

                <!-- Mileage -->
                <div>
                    <label class="block text-xs text-gray-400 mb-1">
                        Mileage (km)
                    </label>
                    <input type="number"
                        name="mileage"
                        required
                        min="0"
                        class="w-full px-4 py-3 bg-black/40 border border-[#C89D66]/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[#C89D66]">
                </div>

                <!-- Fuel -->
                <div>
                    <label class="block text-xs text-gray-400 mb-1">
                        Fuel Level (%)
                    </label>
                    <input type="number"
                        name="fuel_level"
                        required
                        min="0"
                        max="100"
                        class="w-full px-4 py-3 bg-black/40 border border-[#C89D66]/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[#C89D66]">
                </div>

                <!-- Damage notes -->
                <div>
                    <label class="block text-xs text-gray-400 mb-1">
                        Damage Notes (optional)
                    </label>
                    <textarea name="damage_notes"
                            rows="3"
                            placeholder="Scratches, dents, cracks..."
                            class="w-full px-4 py-3 bg-black/40 border border-[#C89D66]/30 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#C89D66]"></textarea>
                </div>

                <!-- Submit -->
                <button
                    class="w-full px-6 py-3 bg-gradient-to-r from-[#C89D66] to-[#d4ab76] hover:from-[#d4ab76] hover:to-[#C89D66] text-black font-semibold rounded-xl transition-all shadow-lg shadow-[#C89D66]/20">
                    Save Check-in Inspection
                </button>
            </form>
            @endif

            </div>
                                {{-- 🔧 CHECK-IN DAMAGES --}}
@if($booking->checkinInspection)
<div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-white">🔧 Check-in Damages</h3>
    </div>

    {{-- Existing Damages List --}}
    @if($booking->checkinDamages->count() > 0)
        <div class="space-y-3 mb-4">
            @foreach($booking->checkinDamages as $damage)
                <div class="bg-[#0f1520] border border-gray-700 rounded-lg p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-white font-bold">{{ $damage->part }} - {{ $damage->type }}</p>
                            <p class="text-gray-400 text-sm">{{ $damage->description }}</p>
                            <p class="text-yellow-400 text-sm mt-1">Cost: {{ number_format($damage->estimated_cost, 2) }} MAD</p>
                        </div>
                        <span class="px-2 py-1 text-xs rounded {{ $damage->is_chargeable ? 'bg-red-900/30 text-red-400' : 'bg-green-900/30 text-green-400' }}">
                            {{ $damage->is_chargeable ? 'Chargeable' : 'Not Chargeable' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-500 text-sm mb-4">No damages recorded at check-in</p>
    @endif

    {{-- Add Damage Form --}}
    <form action="{{ route('admin.bookings.damages.store', $booking) }}" method="POST" class="space-y-4 bg-[#0f1520] border border-emerald-700 rounded-lg p-4">
        @csrf
        <input type="hidden" name="stage" value="checkin">
        
        <h4 class="text-emerald-400 font-bold mb-3">➕ Add Check-in Damage</h4>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-gray-400 mb-2">Part</label>
                <select name="part" required class="w-full px-4 py-3 bg-black/40 border border-emerald-500/30 rounded-xl text-white">
                    <option value="">Select part...</option>
                    <option value="Front Bumper">Front Bumper</option>
                    <option value="Rear Bumper">Rear Bumper</option>
                    <option value="Left Door">Left Door</option>
                    <option value="Right Door">Right Door</option>
                    <option value="Hood">Hood</option>
                    <option value="Trunk">Trunk</option>
                    <option value="Left Mirror">Left Mirror</option>
                    <option value="Right Mirror">Right Mirror</option>
                    <option value="Windshield">Windshield</option>
                    <option value="Rear Window">Rear Window</option>
                    <option value="Left Headlight">Left Headlight</option>
                    <option value="Right Headlight">Right Headlight</option>
                    <option value="Wheel">Wheel</option>
                    <option value="Tire">Tire</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-400 mb-2">Type</label>
                <select name="type" required class="w-full px-4 py-3 bg-black/40 border border-emerald-500/30 rounded-xl text-white">
                    <option value="">Select type...</option>
                    <option value="Scratch">Scratch</option>
                    <option value="Dent">Dent</option>
                    <option value="Crack">Crack</option>
                    <option value="Broken">Broken</option>
                    <option value="Missing">Missing</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs text-gray-400 mb-2">Description</label>
            <textarea name="description" rows="2" class="w-full px-4 py-3 bg-black/40 border border-emerald-500/30 rounded-xl text-white"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-gray-400 mb-2">Estimated Cost (MAD)</label>
                <input type="number" step="0.01" name="estimated_cost" value="0" class="w-full px-4 py-3 bg-black/40 border border-emerald-500/30 rounded-xl text-white">
            </div>

            <div class="flex items-end">
                <label class="flex items-center gap-2 text-white">
                    <input type="checkbox" name="is_chargeable" value="0" class="w-4 h-4">
                    <span class="text-sm">Not Chargeable (Pre-existing)</span>
                </label>
            </div>
        </div>

        <button type="submit" class="w-full px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl">
            Add Damage
        </button>
    </form>
</div>
@endif

{{-- 🔧 CHECK-OUT DAMAGES --}}
@if($booking->checkoutInspection)
<div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-white">🔧 Check-out Damages</h3>
        @if($booking->total_checkout_damage > 0)
            <span class="px-3 py-1 bg-red-900/30 text-red-400 border border-red-700 rounded-lg font-bold">
                Total: {{ number_format($booking->total_checkout_damage, 2) }} MAD
            </span>
        @endif
    </div>

    {{-- Existing Damages List --}}
    @if($booking->checkoutDamages->count() > 0)
        <div class="space-y-3 mb-4">
            @foreach($booking->checkoutDamages as $damage)
                <div class="bg-[#0f1520] border border-red-700 rounded-lg p-4">
                    {{-- Damage Info --}}
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs font-bold rounded">
                                    {{ strtoupper($damage->part) }}
                                </span>
                                <span class="text-gray-500">•</span>
                                <span class="text-gray-300 text-sm">{{ ucfirst($damage->type) }}</span>
                            </div>
                            <p class="text-gray-400 text-sm">{{ $damage->description }}</p>
                        </div>
                        <div class="text-right ml-4">
                            <p class="text-red-400 font-bold text-lg">{{ number_format($damage->estimated_cost, 2) }} MAD</p>
                            <span class="px-2 py-1 text-xs rounded {{ $damage->is_chargeable ? 'bg-red-900/30 text-red-400' : 'bg-green-900/30 text-green-400' }}">
                                {{ $damage->is_chargeable ? 'Customer Pays' : 'Waived' }}
                            </span>
                        </div>
                    </div>
                    
                    {{-- ✅ DISPLAY PHOTOS (INSIDE CARD) --}}
                    @if($damage->photos && count($damage->photos) > 0)
                        <div class="flex gap-2 mt-3 flex-wrap">
                            @foreach($damage->photos as $photo)
                                <div class="relative group">
                                    <img src="{{ asset('storage/' . $photo) }}" 
                                         class="w-20 h-20 rounded-lg object-cover border-2 border-red-700 cursor-pointer hover:scale-110 transition-transform"
                                         onclick="window.open('{{ asset('storage/' . $photo) }}', '_blank')">
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                        </svg>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-500 text-sm mb-4">No damages recorded at check-out</p>
    @endif

    {{-- ✅ ADD DAMAGE FORM (WITH ENCTYPE) --}}
    <form action="{{ route('admin.bookings.damages.store', $booking) }}" 
          method="POST" 
          enctype="multipart/form-data"
          class="space-y-4 bg-[#0f1520] border border-red-700 rounded-lg p-4">
        @csrf
        <input type="hidden" name="stage" value="checkout">
        
        <h4 class="text-red-400 font-bold mb-3">➕ Add Check-out Damage</h4>

        {{-- Part & Type --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-gray-400 mb-2">Part *</label>
                <select name="part" required class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white">
                    <option value="">Select part...</option>
                    <option value="Front Bumper">Front Bumper</option>
                    <option value="Rear Bumper">Rear Bumper</option>
                    <option value="Left Door">Left Door</option>
                    <option value="Right Door">Right Door</option>
                    <option value="Hood">Hood</option>
                    <option value="Trunk">Trunk</option>
                    <option value="Left Mirror">Left Mirror</option>
                    <option value="Right Mirror">Right Mirror</option>
                    <option value="Windshield">Windshield</option>
                    <option value="Left Headlight">Left Headlight</option>
                    <option value="Right Headlight">Right Headlight</option>
                    <option value="Wheel">Wheel</option>
                    <option value="Interior">Interior</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-400 mb-2">Type *</label>
                <select name="type" required class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white">
                    <option value="">Select type...</option>
                    <option value="Scratch">Scratch</option>
                    <option value="Dent">Dent</option>
                    <option value="Crack">Crack</option>
                    <option value="Broken">Broken</option>
                    <option value="Missing">Missing</option>
                    <option value="Stain">Stain</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>

        {{-- Description --}}
        <div>
            <label class="block text-xs text-gray-400 mb-2">Description *</label>
            <textarea name="description" rows="2" required 
                      placeholder="Describe the damage..."
                      class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white placeholder-gray-500"></textarea>
        </div>

        {{-- Photos Upload --}}
        <div>
            <label class="block text-xs text-gray-400 mb-2">📸 Damage Photos</label>
            <input type="file" 
                   name="photos[]" 
                   multiple 
                   accept="image/jpeg,image/png,image/jpg,image/webp"
                   class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white 
                          file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 
                          file:bg-red-600 file:text-white file:font-semibold 
                          hover:file:bg-red-700 file:cursor-pointer">
            <p class="text-xs text-gray-500 mt-1">
                Upload photos (JPG, PNG) - Max 5MB each
            </p>
        </div>

        {{-- Cost & Chargeable --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-gray-400 mb-2">Repair Cost (MAD) *</label>
                <input type="number" step="0.01" name="estimated_cost" required min="0" value="0"
                       class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white">
            </div>

            <div class="flex items-end">
                <label class="flex items-center gap-2 text-white cursor-pointer">
                    <input type="checkbox" name="is_chargeable" value="1" checked class="w-4 h-4 rounded">
                    <span class="text-sm font-bold text-red-400">Charge Customer</span>
                </label>
            </div>
        </div>

        {{-- Submit --}}
        <button type="submit" class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition">
            💾 Add Damage & Photos
        </button>
    </form>
</div>
@endif
            {{-- ================= CHECK-OUT ================= --}}
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-white">Check-out Inspection</h3>
                    @if($booking->checkoutInspection)
                        <span class="px-3 py-1 text-xs rounded-lg bg-emerald-900/30 text-emerald-400 border border-emerald-700/50 font-semibold">Completed</span>
                    @else
                        <span class="px-3 py-1 text-xs rounded-lg bg-gray-700 text-gray-400 border border-gray-600 font-semibold">Not Done</span>
                    @endif
                </div>

                {{-- ===== IF CHECKOUT EXISTS ===== --}}
                @if($booking->checkoutInspection)
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Mileage</p>
                            <p class="text-white font-bold">
                                {{ number_format($booking->checkoutInspection->mileage) }} km
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Fuel</p>
                            <p class="text-white font-bold">
                                {{ $booking->checkoutInspection->fuel_level }}%
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Photos</p>
                            <p class="text-orange-400 font-bold">
                                {{ $booking->checkoutInspection->photos->count() }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">New Damages</p>
                            <p class="text-red-400 font-bold">
                                {{ $booking->damages->where('stage','checkout')->count() }}
                            </p>
                        </div>
                    </div>

                    {{-- FUEL LEVEL BARS --}}
                    @php
                        $fuelPercent = $booking->checkoutInspection->fuel_level ?? 0;
                        $bars = ceil($fuelPercent / 10);
                    @endphp

                    <div class="mt-4">
                        <p class="text-xs text-gray-400 mb-2">Fuel Level</p>

                        <div class="flex items-center gap-1">
                            @php
                                $color = $fuelPercent > 60 ? 'bg-green-500' :
                                        ($fuelPercent > 30 ? 'bg-yellow-500' : 'bg-red-500');
                            @endphp
                            @for($i = 1; $i <= 10; $i++)
                                <div class="h-4 w-6 rounded-sm {{ $i <= $bars ? $color : 'bg-gray-700' }}"></div>
                            @endfor

                            <span class="ml-3 text-sm font-bold text-white">
                                {{ $fuelPercent }}%
                            </span>
                        </div>
                    </div>

                    {{-- BONUS: MISSING FUEL --}}
                    @php
                        $missingPercent = max(
                            0,
                            ($booking->fuel_at_pickup_percent ?? 0)
                            - ($booking->fuel_at_return_percent ?? 0)
                        );

                        $missingLiters = ($booking->car->fuel_tank_capacity * $missingPercent) / 100;
                    @endphp

                    @if($missingPercent > 0)
                        <p class="text-xs text-red-400 mt-2">
                            ⛽ Missing fuel: {{ number_format($missingLiters, 1) }} L
                        </p>
                    @endif

                    {{-- LATE RETURN BARS --}}
                    @php
                        $lateMinutes = $booking->late_minutes ?? 0;
                        $lateBars = min(10, ceil($lateMinutes / 30));
                        $lateHours = floor($lateMinutes / 60);
                        $lateRemainMinutes = $lateMinutes % 60;
                    @endphp

                    @if($lateMinutes > 0)
                        <div class="mt-4 p-4 bg-red-500/10 border border-red-500/30 rounded-xl">
                            <p class="text-xs text-gray-400 mb-2">⏱️ Late Return</p>
                            {{-- Bars --}}
                            <div class="flex gap-1 mb-2">
                                @for($i = 1; $i <= 10; $i++)
                                    <div class="h-4 w-6 rounded-sm {{ $i <= $lateBars ? 'bg-red-500' : 'bg-gray-700' }}"></div>
                                @endfor
                            </div>
                            {{-- Text --}}
                            <p class="text-sm text-red-400 font-semibold">
                                Late by {{ $lateHours }}h {{ $lateRemainMinutes }}min
                            </p>
                            {{-- Fee --}}
                            <p class="text-sm text-red-300 mt-1">
                                Late Fee: <strong>{{ number_format($booking->late_fee, 2) }} MAD</strong>
                            </p>
                        </div>
                    @endif

                    {{-- PHOTOS GRID --}}
                    @if($booking->checkoutInspection->photos->count())
                        <div class="grid grid-cols-3 gap-2 mb-4 mt-4">
                            @foreach($booking->checkoutInspection->photos as $photo)
                                <img src="{{ asset('storage/'.$photo->path) }}"
                                     class="rounded-lg h-20 object-cover border border-gray-700">
                            @endforeach
                        </div>
                    @endif

                    {{-- USAGE SUMMARY --}}
                    @if($booking->checkinInspection)
                        <div class="mt-4 p-4 bg-black/30 border border-gray-700 rounded-xl">
                            <h4 class="text-sm font-bold text-orange-400 mb-3">
                                📊 Usage Summary
                            </h4>

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-400">Distance Driven</p>
                                    <p class="text-white font-bold">
                                        {{ number_format($booking->mileage_difference) }} km
                                    </p>
                                </div>

                                <div>
                                    <p class="text-gray-400">Fuel Used</p>
                                    <p class="text-white font-bold">
                                        {{ $booking->fuel_difference }} %
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- FUEL CHARGE --}}
                    @if($booking->fuel_used > 0)
                        <div class="mt-4 p-4 bg-red-500/10 border border-red-500/30 rounded-xl">
                            <h4 class="text-sm font-bold text-red-400 mb-3">
                                ⛽ Fuel Charge
                            </h4>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-300">
                                    Fuel used ({{ $booking->fuel_used }}%)
                                </span>
                                <span class="text-red-400 font-bold">
                                    {{ number_format($booking->fuel_charge, 2) }} MAD
                                </span>
                            </div>
                        </div>
                    @endif

                {{-- ===== IF CHECKOUT NOT EXISTS → FORM ===== --}}
                @elseif(!$booking->checkoutInspection && $booking->status === 'active')

                    <form method="POST"
                          action="{{ route('admin.inspection.store', $booking) }}"
                          class="space-y-4">
                        @csrf
                        <input type="hidden" name="type" value="checkout">

                        {{-- Mileage --}}
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">
                                Mileage (km)
                            </label>
                            <input type="number"
                                   name="mileage"
                                   required
                                   min="{{ optional($booking->checkinInspection)->mileage ?? 0 }}"
                                   class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white focus:ring-2 focus:ring-red-500">
                        </div>

                        {{-- Fuel --}}
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">
                                Fuel at Return (%)
                            </label>
                            <input type="number"
                                   name="fuel_level"
                                   required
                                   min="0"
                                   max="100"
                                   class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white focus:ring-2 focus:ring-red-500">
                        </div>

                        {{-- Damage notes --}}
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">
                                Damage Notes (optional)
                            </label>
                            <textarea name="damage_notes"
                                      rows="3"
                                      placeholder="New scratches, dents…"
                                      class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white"></textarea>
                        </div>

                        <button
                            class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition">
                            Save Check-out Inspection
                            
                        </button>
                    </form>
                        @if($booking->checkoutInspection)

<div class="mt-6 bg-black/30 p-4 rounded-xl border border-gray-700">
    <h4 class="text-sm font-bold text-red-400 mb-3">
        ➕ Add New Damage
    </h4>

    <form method="POST"
          action="{{ route('admin.booking-damages.store', $booking) }}"
          class="space-y-3">
        @csrf

        <input type="hidden" name="stage" value="checkout">

        <input type="text"
               name="part"
               placeholder="Part (door, bumper...)"
               required
               class="w-full bg-black/40 border border-gray-700 rounded-lg p-2 text-white">

        <input type="text"
               name="type"
               placeholder="Type (scratch, dent...)"
               required
               class="w-full bg-black/40 border border-gray-700 rounded-lg p-2 text-white">

        <input type="number"
               name="estimated_cost"
               placeholder="Estimated Cost"
               min="0"
               class="w-full bg-black/40 border border-gray-700 rounded-lg p-2 text-white">

        <textarea name="description"
                  rows="2"
                  placeholder="Description (optional)"
                  class="w-full bg-black/40 border border-gray-700 rounded-lg p-2 text-white"></textarea>

        <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
            Save Damage
        </button>
    </form>
</div>

@endif
                @else
                    <p class="text-sm text-gray-400 text-center py-8">
                        Available after rental start
                    </p>
                @endif

                {{-- ===== UPLOAD PHOTOS (CHECKOUT) ===== --}}
                @if($booking->checkoutInspection)
                    <div class="bg-[#0f172a] p-5 rounded-xl border border-gray-700 mt-6">
                        <h4 class="text-sm font-bold text-red-400 mb-4">📸 Check-out Photos</h4>

                        <form method="POST"
                              action="{{ route('admin.bookings.inspection.photos.store', $booking->checkoutInspection) }}"
                              enctype="multipart/form-data"
                              class="space-y-3">
                            @csrf

                            <input type="file"
                                   name="photos[]"
                                   multiple
                                   required
                                   class="w-full text-sm bg-black/40 border border-gray-700 rounded-lg p-2 text-white">

                            <input type="text"
                                   name="type"
                                   placeholder="Type (scratch, bumper, interior...)"
                                   class="w-full text-sm bg-black/40 border border-gray-700 rounded-lg p-2 text-white">

                            <textarea name="notes"
                                      rows="2"
                                      placeholder="Notes (optional)"
                                      class="w-full text-sm bg-black/40 border border-gray-700 rounded-lg p-2 text-white"></textarea>

                            <button
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg">
                                Upload Photos
                            </button>
                        </form>
                    </div>
                @endif

            </div>

        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const button = document.getElementById('release-deposit-button');
    if (!button) {
        return;
    }

    const feedback = document.getElementById('deposit-feedback');
    const statusText = document.getElementById('deposit-status-text');
    const statusBlock = document.getElementById('deposit-status-block');
    const actions = document.getElementById('deposit-actions');
    const releasedTemplate = document.getElementById('deposit-released-template');

    const icon = button.querySelector('[data-release-icon]');
    const spinner = button.querySelector('[data-release-spinner]');
    const label = button.querySelector('[data-release-label]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || button.dataset.csrf
        || '';

    const showFeedback = (type, message) => {
        if (!feedback) {
            window.alert(message);
            return;
        }

        const isSuccess = type === 'success';
        feedback.className = isSuccess
            ? 'mb-4 bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 flex items-start gap-3'
            : 'mb-4 bg-red-500/10 border border-red-500/30 rounded-lg p-4 flex items-start gap-3';
        feedback.innerHTML = `
            <svg class="w-5 h-5 ${isSuccess ? 'text-emerald-400' : 'text-red-400'} flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${isSuccess ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' : 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'}"/>
            </svg>
            <p class="${isSuccess ? 'text-emerald-300' : 'text-red-300'} text-sm">${message}</p>
        `;
    };

    const setLoading = (isLoading) => {
        button.disabled = isLoading;

        if (icon) {
            icon.classList.toggle('hidden', isLoading);
        }

        if (spinner) {
            spinner.classList.toggle('hidden', !isLoading);
        }

        if (label) {
            label.textContent = isLoading ? 'Releasing...' : 'Release Deposit';
        }
    };

    button.addEventListener('click', async () => {
        if (!button.dataset.url) {
            showFeedback('error', 'Release URL is missing.');
            return;
        }

        if (!csrfToken) {
            showFeedback('error', 'CSRF token is missing. Add <meta name="csrf-token" content="@{{ csrf_token() }}"> inside the layout head.');
            return;
        }

        setLoading(true);

        try {
            const response = await fetch(button.dataset.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json')
                ? await response.json()
                : {};
            
            if (!response.ok || !data.success) {
                throw new Error(data.error || data.message || 'Release failed');
            }

            showFeedback('success', data.message || 'Deposit released successfully');

            if (statusText) {
                statusText.textContent = 'Released';
                statusText.className = 'font-bold text-xl text-emerald-400';
            }

            if (releasedTemplate && statusBlock) {
                statusBlock.innerHTML = releasedTemplate.innerHTML;
                statusBlock.classList.add('opacity-0');
                requestAnimationFrame(() => statusBlock.classList.remove('opacity-0'));
            }

            button.remove();

            if (actions) {
                actions.classList.remove('md:grid-cols-2');
            }
        } catch (error) {
            showFeedback('error', error.message || 'Failed to release deposit');
            setLoading(false);
        }
    });
});
</script>
@endpush

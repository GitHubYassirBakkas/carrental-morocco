{{-- Advance Payment Status Section --}}
@if($booking->status == 'pending' && $booking->invoice)
    @php
        $minimumAdvancePayment = $minimumAdvancePayment ?? 0;
        $currentPaid = $booking->invoice->paid_amount ?? 0;
        $remaining = max(0, $minimumAdvancePayment - $currentPaid);
        $progress = $minimumAdvancePayment > 0 ? min(100, ($currentPaid / $minimumAdvancePayment) * 100) : 0;
    @endphp

    <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
        <div class="flex justify-between items-start mb-4">
            <h3 class="text-lg font-bold text-white">💰 Advance Payment Status</h3>
            @if($booking->isAdvancePaymentOverdue())
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
                <span class="text-gray-400">Minimum Required: {{ number_format($minimumAdvancePayment, 2) }} MAD</span>
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
        @if($booking->advance_payment_due_at)
        <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-yellow-300 text-sm font-semibold mb-1">
                        Customer must pay minimum {{ number_format($minimumAdvancePayment, 2) }} MAD
                    </p>
                    <p class="text-yellow-200 text-xs">
                        Deadline: <strong>{{ $booking->advance_payment_due_at->format('M d, Y H:i') }}</strong>
                        ({{ $booking->advance_payment_due_at->diffForHumans() }})
                    </p>
                    @if($booking->isAdvancePaymentOverdue())
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
            ⚠️ Invoice must be created before tracking advance payment status.
        </p>
        <form action="{{ route('admin.bookings.confirm', $booking) }}" method="POST">
            @csrf
            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                Confirm Booking & Create Invoice
            </button>
        </form>
    </div>

@elseif($booking->advance_payment_status === 'paid' && $booking->invoice)
    {{-- Advance Payment Confirmed --}}
    <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-emerald-300 font-bold">✅ Booking Confirmed!</p>
                <p class="text-emerald-200 text-sm">
                    Advance payment paid: {{ number_format($booking->advance_payment_amount ?? 0, 2) }} MAD
                    @if($booking->advance_payment_paid_at)
                        on {{ $booking->advance_payment_paid_at->format('M d, Y H:i') }}
                    @endif
                </p>
            </div>
        </div>
    </div>
@endif

{{-- ✅ INVOICE SECTION WITH NULL CHECKS --}}
@if($booking->invoice)
<div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold text-white">Invoice</h2>
        <span class="px-3 py-1 text-xs rounded-lg font-semibold
            @if($booking->invoice->status == 'paid') bg-emerald-900/30 text-emerald-400 border border-emerald-700/50
            @elseif($booking->invoice->status == 'pending') bg-red-900/30 text-red-400 border border-red-700/50
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

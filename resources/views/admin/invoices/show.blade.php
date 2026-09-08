@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] text-gray-100 p-8">
    <div class="max-w-5xl mx-auto space-y-6">
        
        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-sm mb-4">
            <a href="{{ route('admin.invoices.index') }}" class="text-gray-400 hover:text-white transition-colors">Invoices</a>
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-white font-medium">Invoice #{{ $invoice->id }}</span>
        </nav>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-emerald-300 text-sm">{{ session('success') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    @foreach($errors->all() as $error)
                        <p class="text-red-300 text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Header --}}
        <div class="flex justify-between items-start mb-6">
            <div>
                <div class="flex items-center gap-4 mb-2">
                    <h1 class="text-4xl font-bold text-white">Invoice #{{ str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}</h1>
                    <span class="px-4 py-1.5 text-sm rounded-lg font-semibold
                        @if($invoice->status == 'paid') bg-emerald-900/30 text-emerald-400 border border-emerald-700/50
                        @elseif($invoice->status == 'pending') bg-red-900/30 text-red-400 border border-red-700/50
                        @elseif($invoice->status == 'partial') bg-yellow-900/30 text-yellow-400 border border-yellow-700/50
                        @elseif($invoice->status == 'refunded') bg-purple-900/30 text-purple-400 border border-purple-700/50
                        @elseif($invoice->status == 'cancelled') bg-gray-700 text-gray-300 border border-gray-600
                        @else bg-blue-900/30 text-blue-400 border border-blue-700/50
                        @endif">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </div>
                <p class="text-gray-400 text-sm">
                    Booking #{{ $invoice->booking->id }} • Created {{ $invoice->created_at->diffForHumans() }}
                </p>
            </div>
            
            <a href="{{ route('admin.invoices.index') }}"
               class="px-4 py-2.5 bg-[#1f2937] hover:bg-[#374151] border border-gray-700 text-gray-300 rounded-lg transition-all text-sm font-medium flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </a>
        </div>

        {{-- Customer & Booking Info --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Customer --}}
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Customer</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Name</p>
                        <p class="text-white font-medium">{{ $invoice->booking->user->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Email</p>
                        <p class="text-orange-400 text-sm">{{ $invoice->booking->user->email }}</p>
                    </div>
                </div>
            </div>

            {{-- Booking Details --}}
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Booking Details</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Vehicle</p>
                        <p class="text-white font-medium">{{ $invoice->booking->car->full_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Period</p>
                        <p class="text-gray-300 text-sm">
                            {{ optional($invoice->booking->start_date)->format('M d') }} - 
                            {{ optional($invoice->booking->end_date)->format('M d, Y') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Invoice Details --}}
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Invoice Details</h3>
            @php
                $pricingBreakdown = $invoice->pricing_breakdown;
            @endphp
            
            <div class="space-y-3">
                {{-- Rental --}}
                <div class="flex justify-between pb-3 border-b border-gray-800">
                    <span class="text-gray-400 text-sm">Rental</span>
                    <span class="text-white font-medium">{{ number_format($pricingBreakdown['rental_amount'], 2) }} MAD</span>
                </div>

                {{-- Protection Plan --}}
                <div class="flex justify-between pb-3 border-b border-gray-800">
                    <span class="text-gray-400 text-sm">Protection Plan</span>
                    <span class="text-white font-medium">{{ number_format($pricingBreakdown['protection_plan_amount'], 2) }} MAD</span>
                </div>

                @if($pricingBreakdown['extras_amount'] > 0)
                <div class="flex justify-between pb-3 border-b border-gray-800">
                    <span class="text-gray-400 text-sm">Extras</span>
                    <span class="text-white font-medium">{{ number_format($pricingBreakdown['extras_amount'], 2) }} MAD</span>
                </div>
                @endif

                @if($pricingBreakdown['discount_amount'] > 0)
                <div class="flex justify-between pb-3 border-b border-gray-800">
                    <span class="text-emerald-400 text-sm font-semibold">Discount</span>
                    <span class="text-emerald-400 font-semibold">-{{ number_format($pricingBreakdown['discount_amount'], 2) }} MAD</span>
                </div>
                @endif
                
                {{-- Tax --}}
                <div class="flex justify-between pb-3 border-b border-gray-800">
                    <span class="text-gray-400 text-sm">Tax (VAT)</span>
                    <span class="text-white font-medium">{{ number_format($pricingBreakdown['tax_amount'], 2) }} MAD</span>
                </div>
                
                {{-- Total --}}
                <div class="flex justify-between pt-2 pb-3 border-b border-gray-800">
                    <span class="text-orange-400 font-bold text-lg">Total Amount</span>
                    <span class="text-orange-400 font-bold text-xl">{{ number_format($invoice->total_amount, 2) }} MAD</span>
                </div>

                {{-- Amount Paid --}}
                <div class="flex justify-between pb-3 border-b border-gray-800">
                    <span class="text-emerald-400 text-sm font-semibold">Amount Paid</span>
                    <span class="text-emerald-400 font-semibold">{{ number_format($invoice->paid_amount, 2) }} MAD</span>
                </div>

                {{-- Balance --}}
                <div class="flex justify-between pt-2">
                    <span class="text-gray-300 font-bold">Balance Due</span>
                    <span class="text-white font-bold text-lg">{{ number_format($invoice->balance, 2) }} MAD</span>
                </div>
            </div>
        </div>

        {{-- ✅ DAMAGE CHARGES SECTION --}}
@if($invoice->booking && $invoice->booking->checkoutDamages->where('is_chargeable', true)->count() > 0)
<div class="bg-red-500/10 border border-red-500/30 rounded-xl p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-red-400">🔧 Damage Charges</h3>
        <span class="px-3 py-1 text-xs rounded-lg bg-red-900/30 text-red-400 border border-red-700/50 font-semibold">
            {{ $invoice->booking->checkoutDamages->where('is_chargeable', true)->count() }} 
            {{ Str::plural('Damage', $invoice->booking->checkoutDamages->where('is_chargeable', true)->count()) }}
        </span>
    </div>
    
    {{-- Damages List --}}
    <div class="space-y-3 mb-4">
        @foreach($invoice->booking->checkoutDamages->where('is_chargeable', true) as $damage)
            <div class="flex justify-between items-start p-4 bg-black/30 border border-red-700/30 rounded-lg">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2 py-0.5 bg-red-500/20 text-red-300 text-xs font-bold rounded">
                            {{ strtoupper($damage->part) }}
                        </span>
                        <span class="text-gray-400 text-xs">•</span>
                        <span class="text-gray-300 text-sm">{{ ucfirst($damage->type) }}</span>
                    </div>
                    <p class="text-gray-400 text-sm">{{ $damage->description ?: 'No description provided' }}</p>
                    
                    {{-- Damage Photos (if exist) --}}
                    @if($damage->photos && count($damage->photos) > 0)
                        <div class="flex gap-2 mt-2">
                            @foreach(array_slice($damage->photos, 0, 3, true) as $photoIndex => $photo)
                                <img src="{{ route('admin.bookings.damages.photos.show', [$damage, $photoIndex]) }}"
                                     class="w-12 h-12 rounded object-cover border border-red-700 cursor-pointer hover:scale-110 transition"
                                     onclick="window.open('{{ route('admin.bookings.damages.photos.show', [$damage, $photoIndex]) }}', '_blank')">
                            @endforeach
                            @if(count($damage->photos) > 3)
                                <div class="w-12 h-12 rounded bg-red-900/30 border border-red-700 flex items-center justify-center text-red-400 text-xs font-bold">
                                    +{{ count($damage->photos) - 3 }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
                
                <div class="text-right ml-4">
                    <p class="text-red-400 font-bold text-lg">{{ number_format($damage->estimated_cost, 2) }} MAD</p>
                    <p class="text-xs text-gray-500">Repair cost</p>
                </div>
            </div>
        {{-- ✅ DAMAGE PHOTOS --}}
                @if($damage->photos && count($damage->photos) > 0)
                    <div class="flex gap-2 mt-3 flex-wrap">
                        @foreach($damage->photos as $photoIndex => $photo)
                            <div class="relative group">
                                <img src="{{ route('admin.bookings.damages.photos.show', [$damage, $photoIndex]) }}"
                                     class="w-16 h-16 rounded-lg object-cover border-2 border-red-700 cursor-pointer hover:scale-125 transition-transform"
                                     onclick="window.open('{{ route('admin.bookings.damages.photos.show', [$damage, $photoIndex]) }}', '_blank')"
                                     title="Click to view full size">
                                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </div>
                            </div>
                        @endforeach
                        <div class="flex items-center text-xs text-gray-500">
                            {{ count($damage->photos) }} {{ Str::plural('photo', count($damage->photos)) }}
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>  
    
    {{-- Total Damages --}}
    <div class="flex justify-between pt-4 border-t border-red-500/30">
        <span class="text-red-300 font-bold">Total Damage Cost:</span>
        <span class="text-red-400 font-bold text-xl">
            {{ number_format($invoice->booking->total_checkout_damage, 2) }} MAD
        </span>
    </div>
</div>
@endif

        {{-- Payments History --}}
        @if($invoice->payments->count() > 0)
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-white mb-4">Payment History ({{ $invoice->payments->count() }})</h3>
            
            <div class="space-y-3">
                @foreach($invoice->payments as $payment)
                    <div class="p-4 bg-[#0f1520] border border-gray-700 rounded-lg flex justify-between items-center">
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <span class="px-2 py-1 text-xs rounded font-semibold
                                    @if($payment->type == 'payment') bg-emerald-900/30 text-emerald-400
                                    @else bg-purple-900/30 text-purple-400
                                    @endif">
                                    {{ ucfirst($payment->type) }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $payment->paid_at ? $payment->paid_at->format('M d, Y H:i') : 'N/A' }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-400">
                                Method: <span class="text-white">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</span>
                                @if($payment->transaction_id)
                                    • TXN: <span class="text-orange-400">{{ $payment->transaction_id }}</span>
                                @endif
                            </p>
                            @if($payment->notes)
                                <p class="text-xs text-gray-500 mt-1">{{ $payment->notes }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-bold {{ $payment->type == 'refund' ? 'text-purple-400' : 'text-emerald-400' }}">
                                {{ $payment->type == 'refund' ? '-' : '+' }}{{ number_format($payment->amount, 2) }} MAD
                            </p>
                            <span class="text-xs px-2 py-1 rounded
                                @if($payment->status == 'completed') bg-emerald-900/20 text-emerald-400
                                @elseif($payment->status == 'pending') bg-yellow-900/20 text-yellow-400
                                @else bg-red-900/20 text-red-400
                                @endif">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

{{-- Record Payment Form --}}
@if(in_array($invoice->status, ['pending', 'partial']) && $invoice->balance > 0)
<div class="bg-[#1a2332] border border-emerald-800 rounded-xl p-6">
    <h3 class="text-lg font-bold text-emerald-400 mb-4">💳 Record Payment</h3>

    @php
        $booking = $invoice->booking;
        $minimumAdvancePayment = $minimumAdvancePayment ?? 0;
        $currentPaid = $invoice->paid_amount;
        $remaining = max(0, $minimumAdvancePayment - $currentPaid);
    @endphp

    {{-- Advance Payment Info --}}
    @if($booking->status === 'pending')
        <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4 mb-4">
            <p class="text-yellow-300 text-sm mb-2">
                <strong>⚠️ Booking Pending Confirmation</strong>
            </p>
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                    <p class="text-yellow-200">Minimum Required:</p>
                    <p class="text-yellow-300 font-bold">{{ number_format($minimumAdvancePayment, 2) }} MAD</p>
                </div>
                <div>
                    <p class="text-yellow-200">Already Paid:</p>
                    <p class="text-emerald-400 font-bold">{{ number_format($currentPaid, 2) }} MAD</p>
                </div>
                <div>
                    <p class="text-yellow-200">Still Needed:</p>
                    <p class="text-red-400 font-bold">{{ number_format($remaining, 2) }} MAD</p>
                </div>
                <div>
                    <p class="text-yellow-200">Deadline:</p>
                    <p class="text-white font-bold">{{ $booking->advance_payment_due_at?->diffForHumans() ?? '-' }}</p>
                </div>
            </div>
            <p class="text-yellow-200 text-xs mt-3">
                💡 Customer can pay any amount. Booking confirms when total reaches {{ number_format($minimumAdvancePayment, 2) }} MAD.
            </p>
        </div>
    @else
        <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 mb-4">
            <p class="text-emerald-300 text-sm">
                <strong>Balance Due:</strong> {{ number_format($invoice->balance, 2) }} MAD
            </p>
        </div>
    @endif

    <form action="{{ route('admin.invoices.payment', $invoice) }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label class="block text-xs text-gray-400 mb-2">Payment Amount (MAD)</label>
            <input type="number" 
                   step="0.01" 
                   name="amount"
                   placeholder="Enter any amount"
                   max="{{ $invoice->balance }}"
                   required
                   class="w-full px-4 py-3 bg-black/40 border border-emerald-500/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
            
            {{-- Quick Amount Buttons --}}
            @if($booking->status === 'pending' && $remaining > 0)
            <div class="flex gap-2 mt-2">
                <button type="button" 
                        onclick="document.querySelector('input[name=amount]').value = {{ $remaining }}"
                        class="px-3 py-1 bg-yellow-600 hover:bg-yellow-700 text-white text-xs rounded transition">
                    Min: {{ number_format($remaining, 2) }}
                </button>
                <button type="button" 
                        onclick="document.querySelector('input[name=amount]').value = {{ $invoice->balance / 2 }}"
                        class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded transition">
                    Half: {{ number_format($invoice->balance / 2, 2) }}
                </button>
                <button type="button" 
                        onclick="document.querySelector('input[name=amount]').value = {{ $invoice->balance }}"
                        class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs rounded transition">
                    Full: {{ number_format($invoice->balance, 2) }}
                </button>
            </div>
            @endif
        </div>

        <div>
            <label class="block text-xs text-gray-400 mb-2">Payment Method</label>
            <select name="method" required
                    class="w-full px-4 py-3 bg-black/40 border border-emerald-500/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="">Select method...</option>
                <option value="cash">💵 Cash</option>
                <option value="card">💳 Card</option>
                <option value="bank_transfer">🏦 Bank Transfer</option>
                <option value="online">🌐 Online</option>
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-400 mb-2">Notes (optional)</label>
            <textarea name="notes"
                      rows="2"
                      placeholder="Payment notes..."
                      class="w-full px-4 py-3 bg-black/40 border border-emerald-500/30 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
        </div>

        <button type="submit"
                class="w-full px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition-all flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Record Payment
        </button>
    </form>
</div>
@endif

        {{-- Refund Form --}}
        @if(in_array($invoice->status, ['paid', 'partial']) && $invoice->paid_amount > 0)
        <div class="bg-[#1a2332] border border-red-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-red-400 mb-4">⚠️ Process Refund</h3>

            <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 mb-4">
                <p class="text-red-300 text-sm">
                    <strong>Maximum refundable:</strong> {{ number_format($invoice->paid_amount, 2) }} MAD
                </p>
            </div>

            <form action="{{ route('admin.invoices.refund', $invoice) }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs text-gray-400 mb-2">Refund Amount (MAD)</label>
                    <input type="number" 
                           step="0.01" 
                           name="amount"
                           max="{{ $invoice->paid_amount }}"
                           required
                           class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                           placeholder="0.00">
                </div>

                <div>
                    <label class="block text-xs text-gray-400 mb-2">Reason (optional)</label>
                    <textarea name="reason"
                              rows="3"
                              placeholder="Customer requested refund..."
                              class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                </div>

                <button type="submit"
                        onclick="return confirm('Are you sure you want to process this refund?')"
                        class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition-all flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Process Refund
                </button>
            </form>
        </div>
        @endif

        {{-- Timeline --}}
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <h3 class="text-sm font-semibold text-gray-400 uppercase mb-4">Timeline</h3>
            
            <div class="space-y-4">
                <div class="flex gap-3">
                    <div class="text-orange-400 text-sm">📅</div>
                    <div>
                        <p class="text-white text-sm font-medium">Invoice Created</p>
                        <p class="text-xs text-gray-400">{{ $invoice->created_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>

                @if($invoice->issued_at)
                <div class="flex gap-3">
                    <div class="text-blue-400 text-sm">📤</div>
                    <div>
                        <p class="text-white text-sm font-medium">Invoice Issued</p>
                        <p class="text-xs text-gray-400">{{ $invoice->issued_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
                @endif

                @if($invoice->due_date)
                <div class="flex gap-3">
                    <div class="text-yellow-400 text-sm">⏰</div>
                    <div>
                        <p class="text-white text-sm font-medium">Due Date</p>
                        <p class="text-xs text-gray-400">{{ $invoice->due_date->format('M d, Y') }}</p>
                    </div>
                </div>
                @endif

                @foreach($invoice->payments->where('status', 'completed') as $payment)
                <div class="flex gap-3">
                    <div class="{{ $payment->type == 'refund' ? 'text-purple-400' : 'text-emerald-400' }} text-sm">
                        {{ $payment->type == 'refund' ? '↩️' : '✓' }}
                    </div>
                    <div>
                        <p class="text-white text-sm font-medium">
                            {{ $payment->type == 'refund' ? 'Refund Processed' : 'Payment Received' }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ number_format($payment->amount, 2) }} MAD • {{ $payment->paid_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', __('messages.payment_history_page_title'))

@section('content')

@push('styles')
<style>
/* Premium Payment History Styles */
.payment-card-glow {
    position: relative;
}
.payment-card-glow::before {
    content: '';
    position: absolute;
    inset: -1px;
    background: linear-gradient(135deg, #C89D66 0%, transparent 50%, #C89D66 100%);
    border-radius: 24px;
    opacity: 0.2;
    z-index: -1;
    filter: blur(20px);
}

.stat-card {
    transition: all 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-4px);
}

@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fade-in 0.5s ease-out; }

.status-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-pending {
    background: rgba(251, 191, 36, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.3);
}

.status-paid {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.status-refunded {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.status-cancelled {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.status-failed {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}
</style>
@endpush

<div class="min-h-screen bg-[#0a0a0a] py-12">
    <div class="max-w-7xl mx-auto px-6">

        <!-- Header -->
        <div class="mb-10">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 bg-[#C89D66]/10 rounded-2xl flex items-center justify-center">
                    <svg class="w-9 h-9 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-5xl font-bold text-white">{{ __('messages.payment_history_page_title') }}</h1>
                    <p class="text-gray-400 text-lg mt-1">{{ __('messages.payment_history_page_subtitle') }}</p>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <!-- Total Payments -->
            <div class="stat-card bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl shadow-2xl border border-gray-800 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-[#C89D66]/10 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-[#C89D66]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">{{ __('messages.payment_history_total_payments') }}</p>
                        <p class="text-3xl font-bold text-white">{{ $totalPayments }}</p>
                    </div>
                </div>
            </div>

            <!-- Total Spent -->
            <div class="stat-card bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl shadow-2xl border border-gray-800 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-emerald-500/10 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">{{ __('messages.payment_history_total_spent') }}</p>
                        <p class="text-3xl font-bold text-white">{{ number_format($totalSpent, 0) }} {{ __('messages.currency') }}</p>
                    </div>
                </div>
            </div>

            <!-- Paid Payments -->
            <div class="stat-card bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl shadow-2xl border border-gray-800 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-green-500/10 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">{{ __('messages.payment_history_paid_payments') }}</p>
                        <p class="text-3xl font-bold text-white">{{ $paidPayments }}</p>
                    </div>
                </div>
            </div>

            <!-- Pending Payments -->
            <div class="stat-card bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl shadow-2xl border border-gray-800 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-yellow-500/10 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">{{ __('messages.payment_history_pending_payments') }}</p>
                        <p class="text-3xl font-bold text-white">{{ $pendingPayments }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <form method="GET" action="{{ route('profile.payment-history') }}" class="mb-8">
            <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl shadow-2xl border border-gray-800 p-6">
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Search -->
                    <div class="flex-1">
                        <div class="relative">
                            <input type="text"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="{{ __('messages.payment_history_search_placeholder') }}"
                                   class="w-full bg-gray-900 text-white rounded-xl px-5 py-4 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition pl-14">
                            <svg class="w-5 h-5 text-gray-500 absolute left-5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="md:w-64">
                        <select name="status"
                                class="w-full bg-gray-900 text-white rounded-xl px-5 py-4 border border-gray-700 focus:border-[#C89D66] focus:ring-2 focus:ring-[#C89D66]/20 outline-none transition cursor-pointer">
                            <option value="">{{ __('messages.payment_history_all_statuses') }}</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('messages.payment_history_status_pending') }}</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>{{ __('messages.payment_history_status_paid') }}</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>{{ __('messages.payment_history_status_failed') }}</option>
                        </select>
                    </div>

                    <!-- Search Button -->
                    <button type="submit"
                            class="px-8 py-4 bg-gradient-to-r from-[#C89D66] to-[#B8935E] hover:from-[#B8935E] hover:to-[#A8835E] text-white font-bold rounded-xl transition-all duration-300 shadow-lg shadow-[#C89D66]/30 hover:shadow-[#C89D66]/50 transform hover:scale-105 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        {{ __('messages.payment_history_search_btn') }}
                    </button>
                </div>
            </div>
        </form>

        <!-- Payments Table -->
        <div class="bg-gradient-to-br from-[#1a1a1a] to-[#0f0f0f] rounded-3xl shadow-2xl border border-gray-800 overflow-hidden">
            @if($payments->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-800">
                                <th class="text-left px-6 py-4 text-gray-400 font-semibold text-sm uppercase tracking-wider">{{ __('messages.payment_history_th_date') }}</th>
                                <th class="text-left px-6 py-4 text-gray-400 font-semibold text-sm uppercase tracking-wider">{{ __('messages.payment_history_th_booking_id') }}</th>
                                <th class="text-left px-6 py-4 text-gray-400 font-semibold text-sm uppercase tracking-wider">{{ __('messages.payment_history_th_car') }}</th>
                                <th class="text-left px-6 py-4 text-gray-400 font-semibold text-sm uppercase tracking-wider">{{ __('messages.payment_history_th_rental_period') }}</th>
                                <th class="text-left px-6 py-4 text-gray-400 font-semibold text-sm uppercase tracking-wider">{{ __('messages.payment_history_th_amount') }}</th>
                                <th class="text-left px-6 py-4 text-gray-400 font-semibold text-sm uppercase tracking-wider">{{ __('messages.payment_history_th_payment_method') }}</th>
                                <th class="text-left px-6 py-4 text-gray-400 font-semibold text-sm uppercase tracking-wider">{{ __('messages.payment_history_th_status') }}</th>
                                <th class="text-left px-6 py-4 text-gray-400 font-semibold text-sm uppercase tracking-wider">{{ __('messages.payment_history_th_invoice_status') }}</th>
                                <th class="text-left px-6 py-4 text-gray-400 font-semibold text-sm uppercase tracking-wider">{{ __('messages.payment_history_th_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                                <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition">
                                    <td class="px-6 py-4 text-white">
                                        {{ $payment->paid_at ? $payment->paid_at->format('M d, Y') : '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-[#C89D66] font-semibold">#{{ $payment->invoice->booking->id ?? '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-white">
                                        @if($payment->invoice && $payment->invoice->booking && $payment->invoice->booking->car)
                                            {{ $payment->invoice->booking->car->brand }} {{ $payment->invoice->booking->car->model }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-400 text-sm">
                                        @if($payment->invoice && $payment->invoice->booking)
                                            {{ $payment->invoice->booking->start_date?->format('M d') }} -
                                            {{ $payment->invoice->booking->end_date?->format('M d, Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-white font-semibold">
                                        {{ number_format($payment->amount, 2) }} {{ __('messages.currency') }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-400 capitalize">
                                        {{ __('messages.payment_method_' . $payment->method) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $statusClass = match($payment->status) {
                                                'pending' => 'status-pending',
                                                'completed' => 'status-paid',
                                                'failed' => 'status-failed',
                                                default => 'status-cancelled'
                                            };
                                        @endphp
                                        <span class="status-badge {{ $statusClass }}">
                                            {{ __('messages.payment_history_status_' . $payment->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($payment->invoice)
                                            @php
                                                $invoiceStatusClass = match($payment->invoice->status) {
                                                    'pending' => 'status-pending',
                                                    'paid' => 'status-paid',
                                                    'refunded' => 'status-refunded',
                                                    'cancelled' => 'status-cancelled',
                                                    default => 'status-pending'
                                                };
                                            @endphp
                                            <span class="status-badge {{ $invoiceStatusClass }}">
                                                {{ __('messages.payment_history_invoice_status_' . $payment->invoice->status) }}
                                            </span>
                                        @else
                                            <span class="text-gray-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            @if($payment->invoice && $payment->invoice->booking)
                                                <a href="{{ route('bookings.show', $payment->invoice->booking) }}"
                                                   class="p-2 bg-gray-800 hover:bg-[#C89D66]/20 rounded-lg transition text-gray-400 hover:text-[#C89D66]"
                                                   title="{{ __('messages.payment_history_action_view_booking') }}">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </a>
                                            @endif
                                            @if($payment->invoice)
                                                <a href="{{ route('customer.invoices.download', $payment->invoice) }}"
                                                   class="p-2 bg-gray-800 hover:bg-[#C89D66]/20 rounded-lg transition text-gray-400 hover:text-[#C89D66]"
                                                   title="{{ __('messages.payment_history_action_download_invoice') }}">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-800 flex items-center justify-between">
                    <p class="text-gray-400 text-sm">
                        {{ __('messages.payment_history_showing') }} {{ $payments->firstItem() }} {{ __('messages.payment_history_to') }} {{ $payments->lastItem() }} {{ __('messages.payment_history_of') }} {{ $payments->total() }} {{ __('messages.payment_history_results') }}
                    </p>
                    {{ $payments->appends(request()->query())->links() }}
                </div>
            @else
                <!-- Empty State -->
                <div class="p-12 text-center">
                    <div class="w-24 h-24 bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">{{ __('messages.payment_history_empty_title') }}</h3>
                    <p class="text-gray-400">{{ __('messages.payment_history_empty_desc') }}</p>
                </div>
            @endif
        </div>

    </div>
</div>

@endsection

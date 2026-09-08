@extends('admin.layouts.app')

@section('content')
<div class="bg-[#07090f] min-h-screen p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">Refund Management</h1>
            <p class="text-gray-400">View and manage all refunds</p>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-[#0b0d12] rounded-lg p-6 border border-gray-800">
                <div class="text-gray-400 text-sm mb-2">Total Refunds</div>
                <div class="text-2xl font-bold text-white">{{ $stats['total_refunds'] }}</div>
            </div>
            <div class="bg-[#0b0d12] rounded-lg p-6 border border-gray-800">
                <div class="text-gray-400 text-sm mb-2">Total Refunded Amount</div>
                <div class="text-2xl font-bold text-green-400">{{ number_format($stats['total_refunded_amount'], 2) }} MAD</div>
            </div>
            <div class="bg-[#0b0d12] rounded-lg p-6 border border-gray-800">
                <div class="text-gray-400 text-sm mb-2">Cash Refunds</div>
                <div class="text-2xl font-bold text-yellow-400">{{ $stats['cash_refunds'] }}</div>
            </div>
            <div class="bg-[#0b0d12] rounded-lg p-6 border border-gray-800">
                <div class="text-gray-400 text-sm mb-2">Stripe Refunds</div>
                <div class="text-2xl font-bold text-blue-400">{{ $stats['stripe_refunds'] }}</div>
            </div>
            <div class="bg-[#0b0d12] rounded-lg p-6 border border-gray-800">
                <div class="text-gray-400 text-sm mb-2">Partial Refunds</div>
                <div class="text-2xl font-bold text-orange-400">{{ $stats['partial_refunds'] }}</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-[#0b0d12] rounded-lg p-6 border border-gray-800 mb-6">
            <form method="GET" action="{{ route('admin.refunds.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-gray-400 text-sm mb-2">Customer</label>
                        <input type="text" name="customer" value="{{ request('customer') }}"
                               class="w-full bg-[#07090f] border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:border-yellow-400"
                               placeholder="Search customer...">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-2">Booking Reference</label>
                        <input type="text" name="booking_reference" value="{{ request('booking_reference') }}"
                               class="w-full bg-[#07090f] border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:border-yellow-400"
                               placeholder="Booking #">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-2">Refund Method</label>
                        <select name="method" class="w-full bg-[#07090f] border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:border-yellow-400">
                            <option value="">All Methods</option>
                            <option value="cash" {{ request('method') == 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="card" {{ request('method') == 'card' ? 'selected' : '' }}>Card</option>
                            <option value="stripe" {{ request('method') == 'stripe' ? 'selected' : '' }}>Stripe</option>
                            <option value="bank_transfer" {{ request('method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-2">Refund Type</label>
                        <select name="refund_type" class="w-full bg-[#07090f] border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:border-yellow-400">
                            <option value="">All Types</option>
                            <option value="full" {{ request('refund_type') == 'full' ? 'selected' : '' }}>Full Refund</option>
                            <option value="partial" {{ request('refund_type') == 'partial' ? 'selected' : '' }}>Partial Refund</option>
                            <option value="none" {{ request('refund_type') == 'none' ? 'selected' : '' }}>No Refund</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-2">From Date</label>
                        <input type="date" name="from_date" value="{{ request('from_date') }}"
                               class="w-full bg-[#07090f] border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:border-yellow-400">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-2">To Date</label>
                        <input type="date" name="to_date" value="{{ request('to_date') }}"
                               class="w-full bg-[#07090f] border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:border-yellow-400">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-gray-400 text-sm mb-2">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="w-full bg-[#07090f] border border-gray-700 rounded px-3 py-2 text-white focus:outline-none focus:border-yellow-400"
                           placeholder="Search by reference or notes...">
                </div>
                <div class="mt-4 flex gap-2">
                    <button type="submit" class="bg-yellow-400 hover:bg-yellow-500 text-black font-bold py-2 px-4 rounded">
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.refunds.index') }}" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                        Clear Filters
                    </a>
                </div>
            </form>
        </div>

        <!-- Refunds Table -->
        <div class="bg-[#0b0d12] rounded-lg border border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-[#07090f]">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Refund Reference</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Booking Reference</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Car</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Refund Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Refund Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Refund Method</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Refund Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Refund Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Receipt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @forelse($refunds as $refund)
                        <tr class="hover:bg-[#07090f]">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-white">
                                {{ $refund->refundReference }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-white">
                                {{ $refund->customerName }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-white">
                                {{ $refund->bookingReference }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-white">
                                {{ $refund->carName }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($refund->refundType === 'full')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-900 text-green-300">Full Refund</span>
                                @elseif($refund->refundType === 'partial')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-900 text-orange-300">Partial Refund</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-900 text-red-300">No Refund</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-white">
                                {{ number_format($refund->refundAmount, 2) }} MAD
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-white">
                                {{ $refund->refundMethod }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($refund->refundStatus === 'Completed')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-900 text-green-300">Completed</span>
                                @elseif($refund->refundStatus === 'Pending')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-900 text-yellow-300">Pending</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-900 text-red-300">Failed</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-white">
                                {{ $refund->refundDate }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <a href="{{ route('admin.refunds.receipt', $refund->paymentId) }}"
                                   class="text-yellow-400 hover:text-yellow-300 font-medium">
                                    Download
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                <form method="POST" action="{{ route('admin.refunds.resend-email', $refund->paymentId) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-blue-400 hover:text-blue-300 font-medium">
                                        Resend Email
                                    </button>
                                </form>
                                <a href="{{ route('admin.bookings.show', $refund->bookingId) }}"
                                   class="text-green-400 hover:text-green-300 font-medium">
                                    View Booking
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="px-6 py-4 text-center text-gray-400">
                                No refunds found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($refunds->hasPages())
            <div class="bg-[#07090f] px-6 py-4 border-t border-gray-800">
                {{ $refunds->appends(request()->all())->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

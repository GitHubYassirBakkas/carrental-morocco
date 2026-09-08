@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] text-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-white">Invoices</h1>
        </div>

        <!-- Invoices Table -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-[#0f1520] border-b border-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Booking</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Customer</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Amount</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Date</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-[#0f1520] transition">
                        <td class="px-6 py-4 text-sm text-white font-medium">
                            #{{ str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-300">
                            Booking #{{ $invoice->booking_id }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-300">
                            {{ $invoice->booking->user->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-orange-400 font-bold">
                            {{ number_format($invoice->total_amount, 2) }} MAD
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 text-xs rounded-lg font-semibold
                                @if($invoice->status == 'paid') bg-emerald-900/30 text-emerald-400 border border-emerald-700/50
                                @elseif($invoice->status == 'pending') bg-red-900/30 text-red-400 border border-red-700/50
                                @elseif($invoice->status == 'partial') bg-yellow-900/30 text-yellow-400 border border-yellow-700/50
                                @else bg-gray-700 text-gray-300 border border-gray-600
                                @endif">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-400">
                            {{ $invoice->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.invoices.show', $invoice) }}" 
                               class="text-orange-400 hover:text-orange-300 text-sm font-medium">
                                View →
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                            No invoices found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $invoices->links() }}
        </div>

    </div>
</div>
@endsection

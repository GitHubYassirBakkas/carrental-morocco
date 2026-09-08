@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] p-8">
    
    <!-- Header with Back Button -->
    <div class="mb-8 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.coupons.index') }}" 
               class="p-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-4xl font-bold text-white mb-2">Coupon Details</h1>
                <p class="text-gray-400">Complete information and usage history</p>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
            <a href="{{ route('admin.coupons.edit', $coupon) }}" 
               class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>

            <form action="{{ route('admin.coupons.toggle', $coupon) }}" method="POST" class="inline">
                @csrf
                <button type="submit" 
                        class="px-6 py-3 {{ $coupon->is_active ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white font-semibold rounded-lg transition">
                    {{ $coupon->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- LEFT: Coupon Info (2/3) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Coupon Code Card -->
            <div class="bg-gradient-to-br from-[#1a2332] to-[#0f1520] rounded-3xl shadow-2xl border border-gray-800 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600/20 to-purple-600/20 px-8 py-6 border-b border-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-4xl font-bold text-white font-mono mb-2">{{ $coupon->code }}</h2>
                            <p class="text-gray-400">{{ $coupon->category_label }}</p>
                        </div>
                        <div class="text-right">
                            @if($coupon->is_active)
                                <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold bg-green-900/30 text-green-400 border border-green-700/50">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold bg-gray-900/30 text-gray-400 border border-gray-700/50">
                                    Inactive
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="p-8">
                    <!-- Discount Display -->
                    <div class="text-center mb-8 bg-yellow-500/10 border border-yellow-500/30 rounded-2xl p-8">
                        <div class="text-7xl font-bold text-yellow-400 mb-2">
                            {{ $coupon->discount_display }}
                        </div>
                        <div class="text-gray-400 text-lg">
                            {{ $coupon->discount_type == 'percentage' ? 'Percentage' : 'Fixed Amount' }} Discount
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-2 gap-6">
                        
                        <!-- Valid From -->
                        <div class="bg-[#0a0e1a] rounded-xl p-4 border border-gray-800">
                            <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Valid From</div>
                            <div class="text-white font-bold text-lg">{{ $coupon->valid_from->format('d M Y') }}</div>
                        </div>

                        <!-- Valid Until -->
                        <div class="bg-[#0a0e1a] rounded-xl p-4 border border-gray-800">
                            <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Valid Until</div>
                            <div class="text-white font-bold text-lg">{{ $coupon->valid_until->format('d M Y') }}</div>
                            @if($coupon->valid_until->isPast())
                                <div class="text-xs text-red-400 mt-1">Expired</div>
                            @elseif($coupon->valid_until->diffInDays(now()) <= 7)
                                <div class="text-xs text-orange-400 mt-1">Expires in {{ $coupon->valid_until->diffInDays(now()) }} days</div>
                            @endif
                        </div>

                        <!-- Usage -->
                        <div class="bg-[#0a0e1a] rounded-xl p-4 border border-gray-800">
                            <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Usage</div>
                            <div class="text-white font-bold text-lg">
                                {{ $coupon->used_count }}
                                @if($coupon->max_uses)
                                    / {{ $coupon->max_uses }}
                                @else
                                    / ∞
                                @endif
                            </div>
                        </div>

                        <!-- Per User -->
                        <div class="bg-[#0a0e1a] rounded-xl p-4 border border-gray-800">
                            <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Max Per User</div>
                            <div class="text-white font-bold text-lg">{{ $coupon->max_uses_per_user }}x</div>
                        </div>

                    </div>

                    <!-- Description -->
                    @if($coupon->description)
                        <div class="mt-6 bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
                            <div class="text-sm font-semibold text-blue-400 mb-2">Description</div>
                            <p class="text-gray-300">{{ $coupon->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Requirements -->
            @if($coupon->min_booking_amount || $coupon->min_bookings || $coupon->min_total_spent || $coupon->allowed_car_types)
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Requirements
                </h3>

                <div class="space-y-3">
                    @if($coupon->min_booking_amount)
                        <div class="flex items-center gap-3 text-gray-300">
                            <svg class="w-5 h-5 text-yellow-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Minimum booking amount: <strong class="text-white">{{ number_format($coupon->min_booking_amount, 0) }} MAD</strong></span>
                        </div>
                    @endif

                    @if($coupon->min_bookings)
                        <div class="flex items-center gap-3 text-gray-300">
                            <svg class="w-5 h-5 text-yellow-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Minimum completed bookings: <strong class="text-white">{{ $coupon->min_bookings }}</strong></span>
                        </div>
                    @endif

                    @if($coupon->min_total_spent)
                        <div class="flex items-center gap-3 text-gray-300">
                            <svg class="w-5 h-5 text-yellow-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Minimum total spent: <strong class="text-white">{{ number_format($coupon->min_total_spent, 0) }} MAD</strong></span>
                        </div>
                    @endif

                    @if($coupon->allowed_car_types)
                        <div class="flex items-start gap-3 text-gray-300">
                            <svg class="w-5 h-5 text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <div class="mb-1">Allowed car types:</div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($coupon->allowed_car_types as $type)
                                        <span class="px-3 py-1 bg-blue-900/30 text-blue-400 rounded-full text-xs font-semibold border border-blue-700/50 capitalize">
                                            {{ $type }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Usage History -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-800 bg-[#0f1520]">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Usage History
                    </h3>
                </div>

                @if($coupon->usages->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-[#0a0e1a] text-xs">
                                <tr>
                                    <th class="px-6 py-3 text-left text-gray-400 font-semibold uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-gray-400 font-semibold uppercase tracking-wider">Booking</th>
                                    <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Original</th>
                                    <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Discount</th>
                                    <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Final</th>
                                    <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800">
                                @foreach($coupon->usages as $usage)
                                    <tr class="hover:bg-[#0f1520] transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-white">{{ $usage->user->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $usage->user->email }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="{{ route('admin.bookings.show', $usage->booking) }}" 
                                               class="text-blue-400 hover:text-blue-300 font-semibold">
                                                #{{ $usage->booking_id }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="text-gray-400">{{ number_format($usage->original_amount, 0) }} MAD</div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="text-green-400 font-bold">-{{ number_format($usage->discount_amount, 0) }} MAD</div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="text-white font-bold">{{ number_format($usage->final_amount, 0) }} MAD</div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="text-gray-300 text-sm">{{ $usage->created_at->format('d M Y') }}</div>
                                            <div class="text-gray-500 text-xs">{{ $usage->created_at->format('H:i') }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-6 py-12 text-center">
                        <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p class="text-gray-500">This coupon hasn't been used yet.</p>
                    </div>
                @endif
            </div>

        </div>

        <!-- RIGHT: Stats (1/3) -->
        <div class="lg:col-span-1 space-y-6">
            
            <!-- Stats Cards -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <h3 class="text-lg font-bold text-white mb-4">Statistics</h3>
                
                <div class="space-y-4">
                    <!-- Total Uses -->
                    <div class="bg-[#0a0e1a] rounded-xl p-4 border border-gray-800">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Uses</div>
                        <div class="text-3xl font-bold text-white">{{ $stats['total_uses'] }}</div>
                    </div>

                    <!-- Unique Users -->
                    <div class="bg-[#0a0e1a] rounded-xl p-4 border border-gray-800">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Unique Users</div>
                        <div class="text-3xl font-bold text-purple-400">{{ $stats['unique_users'] }}</div>
                    </div>

                    <!-- Total Discount Given -->
                    <div class="bg-[#0a0e1a] rounded-xl p-4 border border-gray-800">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Discount Given</div>
                        <div class="text-2xl font-bold text-yellow-400">{{ number_format($stats['total_discount'], 0) }} MAD</div>
                    </div>

                    <!-- Average Discount -->
                    <div class="bg-[#0a0e1a] rounded-xl p-4 border border-gray-800">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Average Discount</div>
                        <div class="text-2xl font-bold text-green-400">{{ number_format($stats['avg_discount'], 0) }} MAD</div>
                    </div>
                </div>
            </div>

            <!-- User Specific -->
            @if($coupon->user_id)
                <div class="bg-purple-500/10 border border-purple-500/30 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-purple-400 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        User-Specific Coupon
                    </h3>
                    <p class="text-gray-300 text-sm mb-2">This coupon is assigned to:</p>
                    <div class="font-semibold text-white">{{ $coupon->user->name }}</div>
                    <div class="text-sm text-gray-400">{{ $coupon->user->email }}</div>
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <h3 class="text-lg font-bold text-white mb-4">Quick Actions</h3>
                
                <div class="space-y-2">
                    <a href="{{ route('admin.coupons.edit', $coupon) }}" 
                       class="block w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition text-center">
                        Edit Coupon
                    </a>

                    <form action="{{ route('admin.coupons.toggle', $coupon) }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="w-full px-4 py-3 {{ $coupon->is_active ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white font-semibold rounded-lg transition">
                            {{ $coupon->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>

                    @if($coupon->used_count == 0)
                        <form action="{{ route('admin.coupons.destroy', $coupon) }}" 
                              method="POST"
                              onsubmit="return confirm('Are you sure you want to delete this coupon?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="w-full px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition">
                                Delete Coupon
                            </button>
                        </form>
                    @endif
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

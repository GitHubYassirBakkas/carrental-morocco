@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] p-8">
    
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-bold text-white mb-2">Coupons Management</h1>
            <p class="text-gray-400">Create and manage discount coupons</p>
        </div>
        <a href="{{ route('admin.coupons.create') }}" 
           class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create Coupon
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        
        <!-- Total Coupons -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['total'] }}</div>
            <div class="text-sm text-gray-400">Total Coupons</div>
        </div>

        <!-- Active -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['active'] }}</div>
            <div class="text-sm text-gray-400">Active Coupons</div>
        </div>

        <!-- Expired -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-red-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['expired'] }}</div>
            <div class="text-sm text-gray-400">Expired</div>
        </div>

        <!-- Total Uses -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ number_format($stats['total_used']) }}</div>
            <div class="text-sm text-gray-400">Total Uses</div>
        </div>

        <!-- Total Discount -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-yellow-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ number_format($stats['total_discount_given'], 0) }}</div>
            <div class="text-sm text-gray-400">MAD Given Away</div>
        </div>

    </div>

    <!-- Filters -->
    <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            
            <!-- Search -->
            <div>
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Search by code..."
                       class="w-full px-4 py-2 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white placeholder-gray-500">
            </div>

            <!-- Category -->
            <div>
                <select name="category" 
                        class="w-full px-4 py-2 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white">
                    <option value="">All Categories</option>
                    <option value="welcome" {{ request('category') == 'welcome' ? 'selected' : '' }}>Welcome</option>
                    <option value="loyalty" {{ request('category') == 'loyalty' ? 'selected' : '' }}>Loyalty</option>
                    <option value="seasonal" {{ request('category') == 'seasonal' ? 'selected' : '' }}>Seasonal</option>
                    <option value="referral" {{ request('category') == 'referral' ? 'selected' : '' }}>Referral</option>
                    <option value="retention" {{ request('category') == 'retention' ? 'selected' : '' }}>Retention</option>
                    <option value="corporate" {{ request('category') == 'corporate' ? 'selected' : '' }}>Corporate</option>
                    <option value="apology" {{ request('category') == 'apology' ? 'selected' : '' }}>Apology</option>
                </select>
            </div>

            <!-- Status -->
            <div>
                <select name="status" 
                        class="w-full px-4 py-2 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="valid" {{ request('status') == 'valid' ? 'selected' : '' }}>Currently Valid</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>

            <!-- Submit -->
            <div class="flex gap-2">
                <button type="submit" 
                        class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                    Filter
                </button>
                <a href="{{ route('admin.coupons.index') }}" 
                   class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-lg transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Coupons Table -->
    <div class="bg-[#1a2332] border border-gray-800 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-[#0f1520] text-xs">
                    <tr>
                        <th class="px-6 py-3 text-left text-gray-400 font-semibold uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-left text-gray-400 font-semibold uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Discount</th>
                        <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Usage</th>
                        <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Valid Dates</th>
                        <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @forelse($coupons as $coupon)
                        <tr class="hover:bg-[#0f1520] transition-colors">
                            <!-- Code -->
                            <td class="px-6 py-4">
                                <div class="font-bold text-white text-lg font-mono">{{ $coupon->code }}</div>
                                @if($coupon->user_id)
                                    <div class="text-xs text-purple-400 mt-1">👤 User-specific</div>
                                @endif
                            </td>

                            <!-- Category -->
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold 
                                    @if($coupon->category == 'welcome') bg-blue-900/30 text-blue-400 border border-blue-700/50
                                    @elseif($coupon->category == 'loyalty') bg-purple-900/30 text-purple-400 border border-purple-700/50
                                    @elseif($coupon->category == 'seasonal') bg-green-900/30 text-green-400 border border-green-700/50
                                    @elseif($coupon->category == 'referral') bg-yellow-900/30 text-yellow-400 border border-yellow-700/50
                                    @else bg-gray-900/30 text-gray-400 border border-gray-700/50
                                    @endif">
                                    {{ $coupon->category_label }}
                                </span>
                            </td>

                            <!-- Discount -->
                            <td class="px-6 py-4 text-center">
                                <div class="text-yellow-400 font-bold text-lg">
                                    @if($coupon->discount_type == 'percentage')
                                        {{ $coupon->discount_value }}%
                                    @else
                                        {{ number_format($coupon->discount_value, 0) }} MAD
                                    @endif
                                </div>
                            </td>

                            <!-- Usage -->
                            <td class="px-6 py-4 text-center">
                                <div class="text-white font-semibold">
                                    {{ $coupon->used_count }}
                                    @if($coupon->max_uses)
                                        / {{ $coupon->max_uses }}
                                    @else
                                        / ∞
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $coupon->max_uses_per_user }}x per user
                                </div>
                            </td>

                            <!-- Valid Dates -->
                            <td class="px-6 py-4 text-center">
                                <div class="text-sm text-gray-300">{{ $coupon->valid_from->format('d M Y') }}</div>
                                <div class="text-xs text-gray-500">to</div>
                                <div class="text-sm text-gray-300">{{ $coupon->valid_until->format('d M Y') }}</div>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 text-center">
                                @if($coupon->valid_until->isPast())
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-red-900/30 text-red-400 border border-red-700/50">
                                        Expired
                                    </span>
                                @elseif(!$coupon->is_active)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-900/30 text-gray-400 border border-gray-700/50">
                                        Inactive
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-900/30 text-green-400 border border-green-700/50">
                                        Active
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    <!-- View -->
                                    <a href="{{ route('admin.coupons.show', $coupon) }}" 
                                       class="p-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition"
                                       title="View Details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    <!-- Edit -->
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}" 
                                       class="p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
                                       title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>

                                    <!-- Toggle Status -->
                                    <form action="{{ route('admin.coupons.toggle', $coupon) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="p-2 {{ $coupon->is_active ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-lg transition"
                                                title="{{ $coupon->is_active ? 'Deactivate' : 'Activate' }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                            </svg>
                                        </button>
                                    </form>

                                    <!-- Delete -->
                                    @if($coupon->used_count == 0)
                                        <form action="{{ route('admin.coupons.destroy', $coupon) }}" 
                                              method="POST" 
                                              class="inline"
                                              onsubmit="return confirm('Are you sure you want to delete this coupon?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="p-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition"
                                                    title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                No coupons found. Create your first coupon!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($coupons->hasPages())
            <div class="px-6 py-4 border-t border-gray-800">
                {{ $coupons->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

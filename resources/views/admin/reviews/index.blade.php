@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] p-8">
    
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">Reviews Management</h1>
        <p class="text-gray-400">Manage customer reviews and ratings</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <!-- Total Reviews -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['total'] }}</div>
            <div class="text-sm text-gray-400">Total Reviews</div>
        </div>

        <!-- Pending -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-yellow-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['pending'] }}</div>
            <div class="text-sm text-gray-400">Pending Approval</div>
        </div>

        <!-- Approved -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-green-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['approved'] }}</div>
            <div class="text-sm text-gray-400">Approved</div>
        </div>

        <!-- Average Rating -->
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-500/10 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $stats['average_rating'] }} ⭐</div>
            <div class="text-sm text-gray-400">Average Rating</div>
        </div>

    </div>

    <!-- Filters & Search -->
    <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6 mb-6">
        <form method="GET" class="flex flex-wrap gap-4">
            
            <!-- Search -->
            <div class="flex-1 min-w-[200px]">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Search by customer, car, or comment..." 
                       class="w-full px-4 py-2 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Status Filter -->
            <select name="status" 
                    class="px-4 py-2 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-blue-500">
                <option value="">All Reviews</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
            </select>

            <!-- Submit -->
            <button type="submit" 
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                Filter
            </button>

            <!-- Reset -->
            @if(request('search') || request('status'))
                <a href="{{ route('admin.reviews.index') }}" 
                   class="px-6 py-2 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-lg transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Reviews Table -->
    <div class="bg-[#1a2332] border border-gray-800 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-[#0f1520] text-xs">
                    <tr>
                        <th class="px-6 py-3 text-left text-gray-400 font-semibold uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-gray-400 font-semibold uppercase tracking-wider">Car</th>
                        <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Rating</th>
                        <th class="px-6 py-3 text-left text-gray-400 font-semibold uppercase tracking-wider">Comment</th>
                        <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-center text-gray-400 font-semibold uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @forelse($reviews as $review)
                        <tr class="hover:bg-[#0f1520] transition-colors">
                            <!-- Customer -->
                            <td class="px-6 py-4">
                                <div class="text-white font-semibold">{{ $review->user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $review->user->email }}</div>
                            </td>

                            <!-- Car -->
                            <td class="px-6 py-4">
                                <div class="text-white">{{ $review->car->brand }} {{ $review->car->model }}</div>
                                <div class="text-xs text-gray-500">Booking #{{ $review->booking_id }}</div>
                            </td>

                            <!-- Rating -->
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </div>
                                <div class="text-xs text-gray-500 mt-1">{{ $review->rating }}/5</div>
                            </td>

                            <!-- Comment -->
                            <td class="px-6 py-4">
                                <div class="text-gray-300 text-sm max-w-xs truncate">
                                    {{ Str::limit($review->comment, 50) }}
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 text-center">
                                @if($review->is_approved)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-900/30 text-green-400 border border-green-700/50">
                                        ✓ Approved
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-yellow-900/30 text-yellow-400 border border-yellow-700/50">
                                        ⏳ Pending
                                    </span>
                                @endif
                            </td>

                            <!-- Date -->
                            <td class="px-6 py-4 text-center text-sm text-gray-400">
                                {{ $review->created_at->format('d M Y') }}
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    
                                    <!-- View -->
                                    <a href="{{ route('admin.reviews.show', $review) }}" 
                                       class="p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition"
                                       title="View Details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    <!-- Approve/Reject -->
                                    @if(!$review->is_approved)
                                        <form action="{{ route('admin.reviews.approve', $review) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="p-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition"
                                                    title="Approve">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.reviews.reject', $review) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="p-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition"
                                                    title="Un-approve">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Delete -->
                                    <form action="{{ route('admin.reviews.destroy', $review) }}" 
                                          method="POST" 
                                          class="inline"
                                          onsubmit="return confirm('Are you sure you want to delete this review?')">
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

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                No reviews found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($reviews->hasPages())
            <div class="px-6 py-4 border-t border-gray-800">
                {{ $reviews->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

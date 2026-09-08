@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] p-8">
    
    <!-- Header with Back Button -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.reviews.index') }}" 
                   class="p-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-4xl font-bold text-white">Review Details</h1>
            </div>
            <p class="text-gray-400">Review #{{ $review->id }} - {{ $review->created_at->format('d M Y, H:i') }}</p>
        </div>

        <!-- Status Badge -->
        <div>
            @if($review->is_approved)
                <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold bg-green-900/30 text-green-400 border border-green-700/50">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Approved
                </span>
            @else
                <span class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold bg-yellow-900/30 text-yellow-400 border border-yellow-700/50">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    Pending Approval
                </span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- LEFT: Review Content (2/3) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Rating & Review -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    Customer Review
                </h2>

                <!-- Star Rating -->
                <div class="flex items-center gap-3 mb-4">
                    <div class="flex items-center gap-1">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-8 h-8 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                    <span class="text-2xl font-bold text-white">{{ $review->rating }}/5</span>
                </div>

                <!-- Comment -->
                <div class="bg-[#0a0e1a] rounded-lg p-6 border border-gray-700">
                    <p class="text-gray-300 leading-relaxed text-lg">
                        "{{ $review->comment }}"
                    </p>
                </div>
            </div>

            <!-- Admin Response Section -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                    Admin Response
                </h2>

                @if($review->response)
                    <!-- Existing Response -->
                    <div class="bg-blue-900/20 border border-blue-700/50 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm text-blue-400 font-semibold">Admin replied on {{ $review->response_date->format('d M Y') }}</span>
                        </div>
                        <p class="text-gray-300">{{ $review->response }}</p>
                    </div>
                @endif

                <!-- Response Form -->
                <form action="{{ route('admin.reviews.respond', $review) }}" method="POST">
                    @csrf
                    <textarea name="response" 
                              rows="4" 
                              class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Write your response to the customer...">{{ old('response', $review->response) }}</textarea>
                    @error('response')
                        <p class="text-red-400 text-sm mt-2">{{ $message }}</p>
                    @enderror

                    <button type="submit" 
                            class="mt-3 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                        {{ $review->response ? 'Update Response' : 'Send Response' }}
                    </button>
                </form>
            </div>

        </div>

        <!-- RIGHT: Customer & Booking Info (1/3) -->
        <div class="lg:col-span-1 space-y-6">
            
            <!-- Customer Info -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                    Customer
                </h2>

                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Name</p>
                        <p class="text-white font-semibold">{{ $review->user->name }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Email</p>
                        <p class="text-gray-300">{{ $review->user->email }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Member Since</p>
                        <p class="text-gray-300">{{ $review->user->created_at->format('d M Y') }}</p>
                    </div>

                    <div class="pt-3 border-t border-gray-700">
                        <a href="{{ route('admin.users.show', $review->user) }}" 
                           class="text-blue-400 hover:text-blue-300 text-sm font-semibold flex items-center gap-1">
                            View Profile
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Car Info -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                        <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                    </svg>
                    Rental Car
                </h2>

                <!-- Car Image -->
                @if($review->car)
                    <div class="mb-4 rounded-lg overflow-hidden">
                        <img src="{{ $review->car->image_url }}" 
                             alt="{{ $review->car->brand }} {{ $review->car->model }}" 
                             class="w-full h-40 object-cover">
                    </div>
                @endif

                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Vehicle</p>
                        <p class="text-white font-semibold text-lg">{{ $review->car->brand }} {{ $review->car->model }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Rental Price</p>
                        <p class="text-gray-300">{{ number_format($review->car->price_per_day, 0) }} MAD</p>
                    </div>

                    <div class="pt-3 border-t border-gray-700">
                        <a href="{{ route('admin.cars.show', $review->car) }}" 
                           class="text-blue-400 hover:text-blue-300 text-sm font-semibold flex items-center gap-1">
                            View Car Details
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Booking Info -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                    </svg>
                    Booking Details
                </h2>

                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Booking ID</p>
                        <p class="text-white font-semibold">#{{ $review->booking_id }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Rental Period</p>
                        <p class="text-gray-300">
                            {{ $review->booking->start_date->format('d M Y') }} - {{ $review->booking->end_date->format('d M Y') }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $review->booking->total_days }} days</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Amount</p>
                        <p class="text-white font-bold text-lg">{{ number_format($review->booking->total_amount, 2) }} MAD</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Status</p>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-900/30 text-green-400 border border-green-700/50">
                            {{ ucfirst($review->booking->status) }}
                        </span>
                    </div>

                    <div class="pt-3 border-t border-gray-700">
                        <a href="{{ route('admin.bookings.show', $review->booking) }}" 
                           class="text-blue-400 hover:text-blue-300 text-sm font-semibold flex items-center gap-1">
                            View Booking
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <h2 class="text-lg font-bold text-white mb-4">Quick Actions</h2>

                <div class="space-y-2">
                    
                    @if(!$review->is_approved)
                        <!-- Approve -->
                        <form action="{{ route('admin.reviews.approve', $review) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Approve Review
                            </button>
                        </form>
                    @else
                        <!-- Reject -->
                        <form action="{{ route('admin.reviews.reject', $review) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="w-full px-4 py-3 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg transition flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Un-approve Review
                            </button>
                        </form>
                    @endif

                    <!-- Delete -->
                    <form action="{{ route('admin.reviews.destroy', $review) }}" 
                          method="POST"
                          onsubmit="return confirm('Are you sure you want to delete this review? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="w-full px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete Review
                        </button>
                    </form>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

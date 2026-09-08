@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] p-8">
    
    <!-- Header with Back Button -->
    <div class="mb-8 flex items-center gap-3">
        <a href="{{ route('admin.coupons.index') }}" 
           class="p-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-4xl font-bold text-white">Edit Coupon</h1>
            <p class="text-gray-400">Update coupon: {{ $coupon->code }}</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="max-w-5xl">
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-8">
            
            <form action="{{ route('admin.coupons.update', $coupon) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- LEFT COLUMN -->
                    <div class="space-y-6">

                        <!-- Coupon Code -->
                        <div>
                            <label class="block text-gray-300 font-semibold mb-2">
                                Coupon Code <span class="text-red-400">*</span>
                            </label>
                            <input type="text" 
                                   name="code" 
                                   value="{{ old('code', $coupon->code) }}"
                                   class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase font-mono"
                                   required>
                            @error('code')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="block text-gray-300 font-semibold mb-2">
                                Category <span class="text-red-400">*</span>
                            </label>
                            <select name="category" 
                                    class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    required>
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}" {{ old('category', $coupon->category) == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Discount Type & Value -->
                        <div>
                            <label class="block text-gray-300 font-semibold mb-2">
                                Discount <span class="text-red-400">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <select name="discount_type" 
                                        id="discountType"
                                        class="px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        required>
                                    <option value="percentage" {{ old('discount_type', $coupon->discount_type) == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                    <option value="fixed" {{ old('discount_type', $coupon->discount_type) == 'fixed' ? 'selected' : '' }}>Fixed Amount (MAD)</option>
                                </select>
                                <input type="number" 
                                       name="discount_value" 
                                       id="discountValue"
                                       value="{{ old('discount_value', $coupon->discount_value) }}"
                                       step="0.01"
                                       min="0"
                                       class="px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       required>
                            </div>
                            <p class="text-xs text-gray-500 mt-1" id="discountHint">
                                {{ $coupon->discount_type == 'percentage' ? 'Enter percentage (0-100)' : 'Enter fixed amount in MAD' }}
                            </p>
                            @error('discount_value')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Validity Dates -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-gray-300 font-semibold mb-2">
                                    Valid From <span class="text-red-400">*</span>
                                </label>
                                <input type="date" 
                                       name="valid_from" 
                                       value="{{ old('valid_from', $coupon->valid_from->format('Y-m-d')) }}"
                                       class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       required>
                                @error('valid_from')
                                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-gray-300 font-semibold mb-2">
                                    Valid Until <span class="text-red-400">*</span>
                                </label>
                                <input type="date" 
                                       name="valid_until" 
                                       value="{{ old('valid_until', $coupon->valid_until->format('Y-m-d')) }}"
                                       class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       required>
                                @error('valid_until')
                                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-gray-300 font-semibold mb-2">
                                Description
                            </label>
                            <textarea name="description" 
                                      rows="3"
                                      class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="Internal notes about this coupon...">{{ old('description', $coupon->description) }}</textarea>
                            @error('description')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Usage Stats (Read-only) -->
                        <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
                            <h4 class="text-sm font-bold text-blue-400 mb-3">Usage Statistics</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-400">Times Used:</span>
                                    <span class="text-white font-bold ml-2">{{ $coupon->used_count }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-400">Unique Users:</span>
                                    <span class="text-white font-bold ml-2">{{ $coupon->usages()->distinct('user_id')->count() }}</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="space-y-6">

                        <!-- Usage Limits -->
                        <div class="bg-[#0a0e1a] border border-gray-700 rounded-xl p-6">
                            <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                Usage Limits
                            </h3>

                            <div class="space-y-4">
                                <!-- Max Total Uses -->
                                <div>
                                    <label class="block text-gray-300 text-sm font-semibold mb-2">
                                        Maximum Total Uses
                                    </label>
                                    <input type="number" 
                                           name="max_uses" 
                                           value="{{ old('max_uses', $coupon->max_uses) }}"
                                           min="1"
                                           class="w-full px-4 py-2 bg-[#1a2332] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500"
                                           placeholder="Leave empty for unlimited">
                                    <p class="text-xs text-gray-500 mt-1">Total times this coupon can be used</p>
                                    @error('max_uses')
                                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Max Uses Per User -->
                                <div>
                                    <label class="block text-gray-300 text-sm font-semibold mb-2">
                                        Max Uses Per User <span class="text-red-400">*</span>
                                    </label>
                                    <input type="number" 
                                           name="max_uses_per_user" 
                                           value="{{ old('max_uses_per_user', $coupon->max_uses_per_user) }}"
                                           min="1"
                                           class="w-full px-4 py-2 bg-[#1a2332] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500"
                                           required>
                                    <p class="text-xs text-gray-500 mt-1">How many times ONE user can use it</p>
                                    @error('max_uses_per_user')
                                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Requirements -->
                        <div class="bg-[#0a0e1a] border border-gray-700 rounded-xl p-6">
                            <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                Requirements
                            </h3>

                            <div class="space-y-4">
                                <!-- Min Booking Amount -->
                                <div>
                                    <label class="block text-gray-300 text-sm font-semibold mb-2">
                                        Min Booking Amount (MAD)
                                    </label>
                                    <input type="number" 
                                           name="min_booking_amount" 
                                           value="{{ old('min_booking_amount', $coupon->min_booking_amount) }}"
                                           step="0.01"
                                           min="0"
                                           class="w-full px-4 py-2 bg-[#1a2332] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">Minimum booking value required</p>
                                </div>

                                <!-- Min Bookings -->
                                <div>
                                    <label class="block text-gray-300 text-sm font-semibold mb-2">
                                        Min Completed Bookings
                                    </label>
                                    <input type="number" 
                                           name="min_bookings" 
                                           value="{{ old('min_bookings', $coupon->min_bookings) }}"
                                           min="1"
                                           class="w-full px-4 py-2 bg-[#1a2332] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">Customer must have X completed bookings</p>
                                </div>

                                <!-- Min Total Spent -->
                                <div>
                                    <label class="block text-gray-300 text-sm font-semibold mb-2">
                                        Min Total Spent (MAD)
                                    </label>
                                    <input type="number" 
                                           name="min_total_spent" 
                                           value="{{ old('min_total_spent', $coupon->min_total_spent) }}"
                                           step="0.01"
                                           min="0"
                                           class="w-full px-4 py-2 bg-[#1a2332] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">Customer must have spent X total</p>
                                </div>
                            </div>
                        </div>

                        <!-- Car Type Restrictions -->
                        <div class="bg-[#0a0e1a] border border-gray-700 rounded-xl p-6">
                            <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                                </svg>
                                Car Type Restrictions
                            </h3>

                            <div class="space-y-2">
                                @foreach($carTypes as $type)
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" 
                                               name="allowed_car_types[]" 
                                               value="{{ $type }}"
                                               {{ in_array($type, old('allowed_car_types', $coupon->allowed_car_types ?? [])) ? 'checked' : '' }}
                                               class="w-4 h-4 bg-[#1a2332] border border-gray-700 rounded text-blue-600 focus:ring-2 focus:ring-blue-500">
                                        <span class="text-gray-300 capitalize">{{ $type }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-500 mt-3">Leave all unchecked to allow all car types</p>
                        </div>

                        <!-- Active Status -->
                        <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-6">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" 
                                       name="is_active" 
                                       value="1"
                                       {{ old('is_active', $coupon->is_active) ? 'checked' : '' }}
                                       class="w-5 h-5 bg-[#1a2332] border border-gray-700 rounded text-emerald-600 focus:ring-2 focus:ring-emerald-500">
                                <span class="text-emerald-400 font-semibold">
                                    Active Coupon
                                    <span class="block text-xs text-gray-400 font-normal">
                                        Customers can use this coupon
                                    </span>
                                </span>
                            </label>
                        </div>

                    </div>

                </div>

                <!-- Submit Buttons -->
                <div class="flex gap-4 mt-8 pt-8 border-t border-gray-700">
                    <button type="submit" 
                            class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                        Update Coupon
                    </button>
                    <a href="{{ route('admin.coupons.index') }}" 
                       class="px-8 py-3 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-lg transition">
                        Cancel
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>

<script>
// Update discount hint based on type
document.getElementById('discountType').addEventListener('change', function() {
    const hint = document.getElementById('discountHint');
    const valueInput = document.getElementById('discountValue');
    
    if (this.value === 'percentage') {
        hint.textContent = 'Enter percentage (0-100)';
        valueInput.max = 100;
    } else {
        hint.textContent = 'Enter fixed amount in MAD';
        valueInput.removeAttribute('max');
    }
});
</script>
@endsection

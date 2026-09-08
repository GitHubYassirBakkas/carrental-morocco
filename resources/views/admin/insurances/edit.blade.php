@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] text-gray-100">
    <div class="max-w-4xl mx-auto p-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm mb-6">
            <a href="{{ route('admin.insurances.index') }}" class="text-gray-400 hover:text-white">Insurances</a>
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-white font-medium">Edit {{ $insurance->name }}</span>
        </nav>

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">Edit Insurance Plan</h1>
            <p class="text-gray-400">Update insurance details</p>
        </div>

        <!-- Form -->
        <form action="{{ route('admin.insurances.update', $insurance) }}" method="POST" class="bg-[#1a2332] border border-gray-800 rounded-xl p-8 space-y-6">
            @csrf
            @method('PUT')

            <!-- Name & Type -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        Insurance Name *
                    </label>
                    <input type="text" 
                           name="name" 
                           value="{{ old('name', $insurance->name) }}"
                           required 
                           placeholder="e.g., Basic Insurance"
                           class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                    @error('name')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        Insurance Type *
                    </label>
                    <select name="type" 
                            required
                            class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white focus:ring-2 focus:ring-orange-500">
                        <option value="">Select type...</option>
                        <option value="basic" {{ old('type', $insurance->type) == 'basic' ? 'selected' : '' }}>Basic</option>
                        <option value="standard" {{ old('type', $insurance->type) == 'standard' ? 'selected' : '' }}>Standard</option>
                        <option value="premium" {{ old('type', $insurance->type) == 'premium' ? 'selected' : '' }}>Premium</option>
                    </select>
                    @error('type')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-semibold text-gray-300 mb-2">
                    Description *
                </label>
                <textarea name="description" 
                          rows="3" 
                          required
                          placeholder="Describe what this insurance covers..."
                          class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-orange-500">{{ old('description', $insurance->description) }}</textarea>
                @error('description')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Pricing -->
            <div class="grid grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        Daily Rate (MAD) *
                    </label>
                    <input type="number" 
                           name="daily_rate" 
                           value="{{ old('daily_rate', $insurance->daily_rate) }}"
                           step="0.01" 
                           min="0" 
                           required
                           placeholder="100"
                           class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white focus:ring-2 focus:ring-orange-500">
                    @error('daily_rate')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        Max Coverage (MAD) *
                    </label>
                    <input type="number" 
                           name="max_coverage" 
                           value="{{ old('max_coverage', $insurance->max_coverage) }}"
                           step="0.01" 
                           min="0" 
                           required
                           placeholder="2000"
                           class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white focus:ring-2 focus:ring-orange-500">
                    @error('max_coverage')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        Deductible (MAD) *
                    </label>
                    <input type="number" 
                           name="deductible" 
                           value="{{ old('deductible', $insurance->deductible) }}"
                           step="0.01" 
                           min="0" 
                           required
                           placeholder="500"
                           class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white focus:ring-2 focus:ring-orange-500">
                    @error('deductible')
                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Features (Dynamic) -->
            <div x-data="{ features: {{ json_encode(old('features', $insurance->features ?? [''])) }} }">
                <label class="block text-sm font-semibold text-gray-300 mb-2">
                    Features (Optional)
                </label>

                <template x-for="(feature, index) in features" :key="index">
                    <div class="flex gap-2 mb-2">
                        <input type="text" 
                               :name="'features[' + index + ']'" 
                               x-model="features[index]"
                               placeholder="e.g., Third-party liability coverage"
                               class="flex-1 px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-orange-500">
                        
                        <button type="button" 
                                @click="features.splice(index, 1)"
                                x-show="features.length > 1"
                                class="px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>

                <button type="button" 
                        @click="features.push('')"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Feature
                </button>
            </div>

            <!-- Settings -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        Sort Order
                    </label>
                    <input type="number" 
                           name="sort_order" 
                           value="{{ old('sort_order', $insurance->sort_order) }}"
                           min="0"
                           placeholder="0"
                           class="w-full px-4 py-3 bg-black/40 border border-gray-700 rounded-xl text-white focus:ring-2 focus:ring-orange-500">
                </div>

                <div class="flex items-end">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" 
                               name="is_active" 
                               value="1" 
                               {{ old('is_active', $insurance->is_active) ? 'checked' : '' }}
                               class="w-5 h-5 rounded border-gray-700 text-orange-600 focus:ring-orange-500">
                        <span class="text-white font-medium">Active</span>
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-4 pt-6 border-t border-gray-700">
                <button type="submit" 
                        class="flex-1 px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-semibold rounded-xl transition-all">
                    Update Insurance
                </button>
                
                <a href="{{ route('admin.insurances.index') }}" 
                   class="flex-1 px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-xl transition-all text-center">
                    Cancel
                </a>
            </div>
        </form>

    </div>
</div>
@endsection

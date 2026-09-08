@extends('admin.layouts.app')

@section('content')
<div class="min-h-screen bg-[#0a0e1a] p-8">
    
    <!-- Header with Back Button -->
    <div class="mb-8 flex items-center gap-3">
        <a href="{{ route('admin.locations.index') }}" 
           class="p-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-4xl font-bold text-white">Edit Location</h1>
            <p class="text-gray-400">Update {{ $location->name }}</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="max-w-4xl">
        <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-8">
            
            <form action="{{ route('admin.locations.update', $location) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Same form fields as create.blade.php but with values -->
                
                <!-- Basic Information -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Basic Information
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Location Name -->
                        <div class="md:col-span-2">
                            <label class="block text-gray-300 font-semibold mb-2">
                                Location Name <span class="text-red-400">*</span>
                            </label>
                            <input type="text" 
                                   name="name" 
                                   value="{{ old('name', $location->name) }}"
                                   class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
                            @error('name')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Address -->
                        <div class="md:col-span-2">
                            <label class="block text-gray-300 font-semibold mb-2">
                                Address <span class="text-red-400">*</span>
                            </label>
                            <input type="text" 
                                   name="address" 
                                   value="{{ old('address', $location->address) }}"
                                   class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
                            @error('address')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- City -->
                        <div>
                            <label class="block text-gray-300 font-semibold mb-2">
                                City <span class="text-red-400">*</span>
                            </label>
                            <input type="text" 
                                   name="city" 
                                   value="{{ old('city', $location->city) }}"
                                   class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
                            @error('city')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Country -->
                        <div>
                            <label class="block text-gray-300 font-semibold mb-2">
                                Country <span class="text-red-400">*</span>
                            </label>
                            <input type="text" 
                                   name="country" 
                                   value="{{ old('country', $location->country) }}"
                                   class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
                            @error('country')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Postal Code -->
                        <div>
                            <label class="block text-gray-300 font-semibold mb-2">
                                Postal Code
                            </label>
                            <input type="text" 
                                   name="postal_code" 
                                   value="{{ old('postal_code', $location->postal_code) }}"
                                   class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('postal_code')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                <!-- Contact Information -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Contact Information
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Phone -->
                        <div>
                            <label class="block text-gray-300 font-semibold mb-2">
                                Phone <span class="text-red-400">*</span>
                            </label>
                            <input type="text" 
                                   name="phone" 
                                   value="{{ old('phone', $location->phone) }}"
                                   class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
                            @error('phone')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-gray-300 font-semibold mb-2">
                                Email
                            </label>
                            <input type="email" 
                                   name="email" 
                                   value="{{ old('email', $location->email) }}"
                                   class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('email')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                <!-- Working Hours -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Working Hours
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Opening Time -->
                        <div>
                            <label class="block text-gray-300 font-semibold mb-2">
                                Opening Time
                            </label>
                            <input type="time" 
                                   name="opening_time" 
                                   value="{{ old('opening_time', $location->opening_time ? substr($location->opening_time, 0, 5) : '') }}"
                                   class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('opening_time')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Closing Time -->
                        <div>
                            <label class="block text-gray-300 font-semibold mb-2">
                                Closing Time
                            </label>
                            <input type="time" 
                                   name="closing_time" 
                                   value="{{ old('closing_time', $location->closing_time ? substr($location->closing_time, 0, 5) : '') }}"
                                   class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('closing_time')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                <!-- GPS Coordinates -->
                <div class="mb-8">
                    <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        GPS Coordinates
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Latitude -->
                        <div>
                            <label class="block text-gray-300 font-semibold mb-2">
                                Latitude
                            </label>
                            <input type="number" 
                                   name="latitude" 
                                   value="{{ old('latitude', $location->latitude) }}"
                                   step="0.00000001"
                                   class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('latitude')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Longitude -->
                        <div>
                            <label class="block text-gray-300 font-semibold mb-2">
                                Longitude
                            </label>
                            <input type="number" 
                                   name="longitude" 
                                   value="{{ old('longitude', $location->longitude) }}"
                                   step="0.00000001"
                                   class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('longitude')
                                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-8">
                    <label class="block text-gray-300 font-semibold mb-2">
                        Internal Notes
                    </label>
                    <textarea name="notes" 
                              rows="3"
                              class="w-full px-4 py-3 bg-[#0a0e1a] border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('notes', $location->notes) }}</textarea>
                    @error('notes')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
<div class="mb-8">
    <label class="flex items-center gap-3 cursor-pointer">
        <!-- Hidden input for unchecked state -->
        <input type="hidden" name="is_active" value="0">
        
        <input type="checkbox" 
               name="is_active" 
               value="1"
               {{ old('is_active', $location->is_active) ? 'checked' : '' }}
               class="w-5 h-5 bg-[#0a0e1a] border border-gray-700 rounded text-blue-600 focus:ring-2 focus:ring-blue-500">
        <span class="text-gray-300 font-semibold">
            Active Location
            <span class="block text-xs text-gray-500 font-normal">
                When active, this location will be available for bookings
            </span>
        </span>
    </label>
</div>

                <!-- Submit Buttons -->
                <div class="flex gap-4">
                    <button type="submit" 
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                        Update Location
                    </button>
                    <a href="{{ route('admin.locations.index') }}" 
                       class="px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-lg transition">
                        Cancel
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>
@endsection

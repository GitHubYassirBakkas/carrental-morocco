{{-- 🔧 CHECK-OUT DAMAGES --}}
@if($booking->checkoutInspection)
<div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-white">🔧 Check-out Damages</h3>
        @if($booking->total_checkout_damage > 0)
            <span class="px-3 py-1 bg-red-900/30 text-red-400 border border-red-700 rounded-lg font-bold">
                Total: {{ number_format($booking->total_checkout_damage, 2) }} MAD
            </span>
        @endif
    </div>

    {{-- Existing Damages List --}}
    @if($booking->checkoutDamages->count() > 0)
        <div class="space-y-3 mb-4">
            @foreach($booking->checkoutDamages as $damage)
                <div class="bg-[#0f1520] border border-red-700 rounded-lg p-4">
                    {{-- Damage Info --}}
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-1 bg-red-500/20 text-red-400 text-xs font-bold rounded">
                                    {{ strtoupper($damage->part) }}
                                </span>
                                <span class="text-gray-500">•</span>
                                <span class="text-gray-300 text-sm">{{ ucfirst($damage->type) }}</span>
                            </div>
                            <p class="text-gray-400 text-sm">{{ $damage->description }}</p>
                        </div>
                        <div class="text-right ml-4">
                            <p class="text-red-400 font-bold text-lg">{{ number_format($damage->estimated_cost, 2) }} MAD</p>
                            <span class="px-2 py-1 text-xs rounded {{ $damage->is_chargeable ? 'bg-red-900/30 text-red-400' : 'bg-green-900/30 text-green-400' }}">
                                {{ $damage->is_chargeable ? 'Customer Pays' : 'Waived' }}
                            </span>
                        </div>
                    </div>
                    
                    {{-- ✅ DISPLAY PHOTOS (INSIDE CARD) --}}
                    @if($damage->photos && count($damage->photos) > 0)
                        <div class="flex gap-2 mt-3 flex-wrap">
                            @foreach($damage->photos as $photoIndex => $photo)
                                <div class="relative group">
                                    <img src="{{ route('admin.bookings.damages.photos.show', [$damage, $photoIndex]) }}"
                                         class="w-20 h-20 rounded-lg object-cover border-2 border-red-700 cursor-pointer hover:scale-110 transition-transform"
                                         onclick="window.open('{{ route('admin.bookings.damages.photos.show', [$damage, $photoIndex]) }}', '_blank')">
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                        </svg>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-500 text-sm mb-4">No damages recorded at check-out</p>
    @endif

    {{-- ✅ ADD DAMAGE FORM (WITH ENCTYPE) --}}
    <form action="{{ route('admin.bookings.damages.store', $booking) }}" 
          method="POST" 
          enctype="multipart/form-data"
          class="space-y-4 bg-[#0f1520] border border-red-700 rounded-lg p-4">
        @csrf
        <input type="hidden" name="stage" value="checkout">
        
        <h4 class="text-red-400 font-bold mb-3">➕ Add Check-out Damage</h4>

        {{-- Part & Type --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-gray-400 mb-2">Part *</label>
                <select name="part" required class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white">
                    <option value="">Select part...</option>
                    <option value="Front Bumper">Front Bumper</option>
                    <option value="Rear Bumper">Rear Bumper</option>
                    <option value="Left Door">Left Door</option>
                    <option value="Right Door">Right Door</option>
                    <option value="Hood">Hood</option>
                    <option value="Trunk">Trunk</option>
                    <option value="Left Mirror">Left Mirror</option>
                    <option value="Right Mirror">Right Mirror</option>
                    <option value="Windshield">Windshield</option>
                    <option value="Left Headlight">Left Headlight</option>
                    <option value="Right Headlight">Right Headlight</option>
                    <option value="Wheel">Wheel</option>
                    <option value="Interior">Interior</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-400 mb-2">Type *</label>
                <select name="type" required class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white">
                    <option value="">Select type...</option>
                    <option value="Scratch">Scratch</option>
                    <option value="Dent">Dent</option>
                    <option value="Crack">Crack</option>
                    <option value="Broken">Broken</option>
                    <option value="Missing">Missing</option>
                    <option value="Stain">Stain</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>

        {{-- Description --}}
        <div>
            <label class="block text-xs text-gray-400 mb-2">Description *</label>
            <textarea name="description" rows="2" required 
                      placeholder="Describe the damage..."
                      class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white placeholder-gray-500"></textarea>
        </div>

        {{-- Photos Upload --}}
        <div>
            <label class="block text-xs text-gray-400 mb-2">📸 Damage Photos</label>
            <input type="file" 
                   name="photos[]" 
                   multiple 
                   accept="image/jpeg,image/png,image/jpg,image/webp"
                   class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white 
                          file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 
                          file:bg-red-600 file:text-white file:font-semibold 
                          hover:file:bg-red-700 file:cursor-pointer">
            <p class="text-xs text-gray-500 mt-1">
                Upload photos (JPG, PNG) - Max 5MB each
            </p>
        </div>

        {{-- Cost & Chargeable --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-gray-400 mb-2">Repair Cost (MAD) *</label>
                <input type="number" step="0.01" name="estimated_cost" required min="0" value="0"
                       class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white">
            </div>

            <div class="flex items-end">
                <label class="flex items-center gap-2 text-white cursor-pointer">
                    <input type="checkbox" name="is_chargeable" value="1" checked class="w-4 h-4 rounded">
                    <span class="text-sm font-bold text-red-400">Charge Customer</span>
                </label>
            </div>
        </div>

        {{-- Submit --}}
        <button type="submit" class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition">
            💾 Add Damage & Photos
        </button>
    </form>
</div>
@endif

                                {{-- 🔧 CHECK-IN DAMAGES --}}
@if($booking->checkinInspection)
<div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-white">🔧 Check-in Damages</h3>
    </div>

    {{-- Existing Damages List --}}
    @if($booking->checkinDamages->count() > 0)
        <div class="space-y-3 mb-4">
            @foreach($booking->checkinDamages as $damage)
                <div class="bg-[#0f1520] border border-gray-700 rounded-lg p-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-white font-bold">{{ $damage->part }} - {{ $damage->type }}</p>
                            <p class="text-gray-400 text-sm">{{ $damage->description }}</p>
                            <p class="text-yellow-400 text-sm mt-1">Cost: {{ number_format($damage->estimated_cost, 2) }} MAD</p>
                        </div>
                        <span class="px-2 py-1 text-xs rounded {{ $damage->is_chargeable ? 'bg-red-900/30 text-red-400' : 'bg-green-900/30 text-green-400' }}">
                            {{ $damage->is_chargeable ? 'Chargeable' : 'Not Chargeable' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-500 text-sm mb-4">No damages recorded at check-in</p>
    @endif

    {{-- Add Damage Form --}}
    <form action="{{ route('admin.bookings.damages.store', $booking) }}" method="POST" class="space-y-4 bg-[#0f1520] border border-emerald-700 rounded-lg p-4">
        @csrf
        <input type="hidden" name="stage" value="checkin">
        
        <h4 class="text-emerald-400 font-bold mb-3">➕ Add Check-in Damage</h4>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-gray-400 mb-2">Part</label>
                <select name="part" required class="w-full px-4 py-3 bg-black/40 border border-emerald-500/30 rounded-xl text-white">
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
                    <option value="Rear Window">Rear Window</option>
                    <option value="Left Headlight">Left Headlight</option>
                    <option value="Right Headlight">Right Headlight</option>
                    <option value="Wheel">Wheel</option>
                    <option value="Tire">Tire</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div>
                <label class="block text-xs text-gray-400 mb-2">Type</label>
                <select name="type" required class="w-full px-4 py-3 bg-black/40 border border-emerald-500/30 rounded-xl text-white">
                    <option value="">Select type...</option>
                    <option value="Scratch">Scratch</option>
                    <option value="Dent">Dent</option>
                    <option value="Crack">Crack</option>
                    <option value="Broken">Broken</option>
                    <option value="Missing">Missing</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs text-gray-400 mb-2">Description</label>
            <textarea name="description" rows="2" class="w-full px-4 py-3 bg-black/40 border border-emerald-500/30 rounded-xl text-white"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-gray-400 mb-2">Estimated Cost (MAD)</label>
                <input type="number" step="0.01" name="estimated_cost" value="0" class="w-full px-4 py-3 bg-black/40 border border-emerald-500/30 rounded-xl text-white">
            </div>

            <div class="flex items-end">
                <label class="flex items-center gap-2 text-white">
                    <input type="checkbox" name="is_chargeable" value="0" class="w-4 h-4">
                    <span class="text-sm">Not Chargeable (Pre-existing)</span>
                </label>
            </div>
        </div>

        <button type="submit" class="w-full px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl">
            Add Damage
        </button>
    </form>
</div>
@endif

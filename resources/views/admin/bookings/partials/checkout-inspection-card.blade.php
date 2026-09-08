            {{-- ================= CHECK-OUT ================= --}}
            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-white">Check-out Inspection</h3>
                    @if($booking->checkoutInspection)
                        <span class="px-3 py-1 text-xs rounded-lg bg-emerald-900/30 text-emerald-400 border border-emerald-700/50 font-semibold">Completed</span>
                    @else
                        <span class="px-3 py-1 text-xs rounded-lg bg-gray-700 text-gray-400 border border-gray-600 font-semibold">Not Done</span>
                    @endif
                </div>

                {{-- ===== IF CHECKOUT EXISTS ===== --}}
                @if($booking->checkoutInspection)
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Mileage</p>
                            <p class="text-white font-bold">
                                {{ number_format($booking->checkoutInspection->mileage) }} km
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Fuel</p>
                            <p class="text-white font-bold">
                                {{ $booking->checkoutInspection->fuel_level }}%
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Photos</p>
                            <p class="text-orange-400 font-bold">
                                {{ $booking->checkoutInspection->photos->count() }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">New Damages</p>
                            <p class="text-red-400 font-bold">
                                {{ $booking->damages->where('stage','checkout')->count() }}
                            </p>
                        </div>
                    </div>

                    {{-- FUEL LEVEL BARS --}}
                    @php
                        $fuelPercent = $booking->checkoutInspection->fuel_level ?? 0;
                        $bars = ceil($fuelPercent / 10);
                    @endphp

                    <div class="mt-4">
                        <p class="text-xs text-gray-400 mb-2">Fuel Level</p>

                        <div class="flex items-center gap-1">
                            @php
                                $color = $fuelPercent > 60 ? 'bg-green-500' :
                                        ($fuelPercent > 30 ? 'bg-yellow-500' : 'bg-red-500');
                            @endphp
                            @for($i = 1; $i <= 10; $i++)
                                <div class="h-4 w-6 rounded-sm {{ $i <= $bars ? $color : 'bg-gray-700' }}"></div>
                            @endfor

                            <span class="ml-3 text-sm font-bold text-white">
                                {{ $fuelPercent }}%
                            </span>
                        </div>
                    </div>

                    {{-- BONUS: MISSING FUEL --}}
                    @php
                        $missingPercent = max(
                            0,
                            ($booking->fuel_at_pickup_percent ?? 0)
                            - ($booking->fuel_at_return_percent ?? 0)
                        );

                        $missingLiters = ($booking->car->fuel_tank_capacity * $missingPercent) / 100;
                    @endphp

                    @if($missingPercent > 0)
                        <p class="text-xs text-red-400 mt-2">
                            ⛽ Missing fuel: {{ number_format($missingLiters, 1) }} L
                        </p>
                    @endif

                    {{-- LATE RETURN BARS --}}
                    @php
                        $lateMinutes = $booking->late_minutes ?? 0;
                        $lateBars = min(10, ceil($lateMinutes / 30));
                        $lateHours = floor($lateMinutes / 60);
                        $lateRemainMinutes = $lateMinutes % 60;
                    @endphp

                    @if($lateMinutes > 0)
                        <div class="mt-4 p-4 bg-red-500/10 border border-red-500/30 rounded-xl">
                            <p class="text-xs text-gray-400 mb-2">⏱️ Late Return</p>
                            {{-- Bars --}}
                            <div class="flex gap-1 mb-2">
                                @for($i = 1; $i <= 10; $i++)
                                    <div class="h-4 w-6 rounded-sm {{ $i <= $lateBars ? 'bg-red-500' : 'bg-gray-700' }}"></div>
                                @endfor
                            </div>
                            {{-- Text --}}
                            <p class="text-sm text-red-400 font-semibold">
                                Late by {{ $lateHours }}h {{ $lateRemainMinutes }}min
                            </p>
                            {{-- Fee --}}
                            <p class="text-sm text-red-300 mt-1">
                                Late Fee: <strong>{{ number_format($booking->late_fee, 2) }} MAD</strong>
                            </p>
                        </div>
                    @endif

                    {{-- PHOTOS GRID --}}
                    @if($booking->checkoutInspection->photos->count())
                        <div class="grid grid-cols-3 gap-2 mb-4 mt-4">
                            @foreach($booking->checkoutInspection->photos as $photo)
                                <img src="{{ route('admin.bookings.inspection.photos.show', $photo) }}"
                                     class="rounded-lg h-20 object-cover border border-gray-700">
                            @endforeach
                        </div>
                    @endif

                    {{-- USAGE SUMMARY --}}
                    @if($booking->checkinInspection)
                        <div class="mt-4 p-4 bg-black/30 border border-gray-700 rounded-xl">
                            <h4 class="text-sm font-bold text-orange-400 mb-3">
                                📊 Usage Summary
                            </h4>

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-400">Distance Driven</p>
                                    <p class="text-white font-bold">
                                        {{ number_format($booking->mileage_difference) }} km
                                    </p>
                                </div>

                                <div>
                                    <p class="text-gray-400">Fuel Used</p>
                                    <p class="text-white font-bold">
                                        {{ $booking->fuel_difference }} %
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- FUEL CHARGE --}}
                    @if($booking->fuel_used > 0)
                        <div class="mt-4 p-4 bg-red-500/10 border border-red-500/30 rounded-xl">
                            <h4 class="text-sm font-bold text-red-400 mb-3">
                                ⛽ Fuel Charge
                            </h4>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-300">
                                    Fuel used ({{ $booking->fuel_used }}%)
                                </span>
                                <span class="text-red-400 font-bold">
                                    {{ number_format($booking->fuel_charge, 2) }} MAD
                                </span>
                            </div>
                        </div>
                    @endif

                {{-- ===== IF CHECKOUT NOT EXISTS → FORM ===== --}}
                @elseif(!$booking->checkoutInspection && $booking->status === 'active')

                    <form method="POST"
                          action="{{ route('admin.inspection.store', $booking) }}"
                          class="space-y-4">
                        @csrf
                        <input type="hidden" name="type" value="checkout">

                        {{-- Mileage --}}
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">
                                Mileage (km)
                            </label>
                            <input type="number"
                                   name="mileage"
                                   required
                                   min="{{ optional($booking->checkinInspection)->mileage ?? 0 }}"
                                   class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white focus:ring-2 focus:ring-red-500">
                        </div>

                        {{-- Fuel --}}
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">
                                Fuel at Return (%)
                            </label>
                            <input type="number"
                                   name="fuel_level"
                                   required
                                   min="0"
                                   max="100"
                                   class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white focus:ring-2 focus:ring-red-500">
                        </div>

                        {{-- Damage notes --}}
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">
                                Damage Notes (optional)
                            </label>
                            <textarea name="damage_notes"
                                      rows="3"
                                      placeholder="New scratches, dents…"
                                      class="w-full px-4 py-3 bg-black/40 border border-red-500/30 rounded-xl text-white"></textarea>
                        </div>

                        <button
                            class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition">
                            Save Check-out Inspection
                            
                        </button>
                    </form>
                        @if($booking->checkoutInspection)

<div class="mt-6 bg-black/30 p-4 rounded-xl border border-gray-700">
    <h4 class="text-sm font-bold text-red-400 mb-3">
        ➕ Add New Damage
    </h4>

    <form method="POST"
          action="{{ route('admin.bookings.damages.store', $booking) }}"
          class="space-y-3">
        @csrf

        <input type="hidden" name="stage" value="checkout">

        <input type="text"
               name="part"
               placeholder="Part (door, bumper...)"
               required
               class="w-full bg-black/40 border border-gray-700 rounded-lg p-2 text-white">

        <input type="text"
               name="type"
               placeholder="Type (scratch, dent...)"
               required
               class="w-full bg-black/40 border border-gray-700 rounded-lg p-2 text-white">

        <input type="number"
               name="estimated_cost"
               placeholder="Estimated Cost"
               min="0"
               class="w-full bg-black/40 border border-gray-700 rounded-lg p-2 text-white">

        <textarea name="description"
                  rows="2"
                  placeholder="Description (optional)"
                  class="w-full bg-black/40 border border-gray-700 rounded-lg p-2 text-white"></textarea>

        <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
            Save Damage
        </button>
    </form>
</div>

@endif
                @else
                    <p class="text-sm text-gray-400 text-center py-8">
                        Available after rental start
                    </p>
                @endif

                {{-- ===== UPLOAD PHOTOS (CHECKOUT) ===== --}}
                @if($booking->checkoutInspection)
                    <div class="bg-[#0f172a] p-5 rounded-xl border border-gray-700 mt-6">
                        <h4 class="text-sm font-bold text-red-400 mb-4">📸 Check-out Photos</h4>

                        <form method="POST"
                              action="{{ route('admin.bookings.inspection.photos.store', $booking->checkoutInspection) }}"
                              enctype="multipart/form-data"
                              class="space-y-3">
                            @csrf

                            <input type="file"
                                   name="photos[]"
                                   multiple
                                   required
                                   accept="image/jpeg,image/png,image/webp"
                                   class="w-full text-sm bg-black/40 border border-gray-700 rounded-lg p-2 text-white">

                            <input type="text"
                                   name="type"
                                   placeholder="Type (scratch, bumper, interior...)"
                                   class="w-full text-sm bg-black/40 border border-gray-700 rounded-lg p-2 text-white">

                            <textarea name="notes"
                                      rows="2"
                                      placeholder="Notes (optional)"
                                      class="w-full text-sm bg-black/40 border border-gray-700 rounded-lg p-2 text-white"></textarea>

                            <button
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg">
                                Upload Photos
                            </button>
                        </form>
                    </div>
                @endif

            </div>

        </div>

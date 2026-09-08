        <!-- Inspections -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Check-in -->
            @if ($errors->any())
                <div class="bg-red-500/20 border border-red-500 p-3 rounded mb-4">
                    @foreach ($errors->all() as $error)
                        <p class="text-red-400 text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-[#1a2332] border border-gray-800 rounded-xl p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-white">Check-in Inspection</h3>
                    @if($booking->checkinInspection)
                        <span class="px-3 py-1 text-xs rounded-lg bg-emerald-900/30 text-emerald-400 border border-emerald-700/50 font-semibold">Completed</span>
                    @else
                        <span class="px-3 py-1 text-xs rounded-lg bg-yellow-900/30 text-yellow-400 border border-yellow-700/50 font-semibold">Pending</span>
                    @endif
                </div>

                @if($booking->checkinInspection)
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Mileage</p>
                            <p class="text-white font-bold">{{ number_format($booking->checkinInspection->mileage) }} km</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Fuel</p>
                            <p class="text-white font-bold">{{ $booking->checkinInspection->fuel_level }}%</p>
                            
                            {{-- FUEL BARS CHECK-IN --}}
                            @php
                                $fuelPercent = $booking->checkinInspection->fuel_level ?? 0;
                                $bars = ceil($fuelPercent / 10);
                            @endphp
                            <div class="mt-3">
                                <div class="flex items-center gap-1">
                                    @for($i = 1; $i <= 10; $i++)
                                        <div class="h-4 w-6 rounded-sm {{ $i <= $bars ? 'bg-green-500' : 'bg-gray-700' }}"></div>
                                    @endfor
                                    <span class="ml-3 text-sm font-bold text-white">
                                        {{ $fuelPercent }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Photos</p>
                            <p class="text-orange-400 font-bold">{{ $booking->checkinInspection->photos->count() }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Damages</p>
                            <p class="text-red-400 font-bold">{{ $booking->damages->where('stage','checkin')->count() }}</p>
                        </div>
                    </div>

                    @if($booking->checkinInspection->photos->count() > 0)
                    <div class="grid grid-cols-3 gap-2">
                        @foreach($booking->checkinInspection->photos as $photo)
                            <img src="{{ asset('storage/'.$photo->path) }}" class="rounded-lg h-20 object-cover border border-gray-700">
                        @endforeach
                    </div>
                    @endif

                    {{-- CODE JDID LI ZEDTIH --}}
                    <div class="bg-[#0f172a] p-5 rounded-xl border border-gray-700 mt-6">
                        <h4 class="text-sm font-bold text-orange-400 mb-4">📸 Inspection Photos</h4>
                        {{-- Upload form --}}
                        <form method="POST"
                              action="{{ route('admin.bookings.inspection.photos.store', $booking->checkinInspection) }}"
                              enctype="multipart/form-data"
                              class="space-y-3">
                            @csrf
                            <input type="file"
                                   name="photos[]"
                                   multiple
                                   required
                                   class="w-full text-sm bg-black/40 border border-gray-700 rounded-lg p-2 text-white">
                            <input type="text"
                                   name="type"
                                   placeholder="Type (front, scratch, tire...)"
                                   class="w-full text-sm bg-black/40 border border-gray-700 rounded-lg p-2 text-white">
                            <textarea name="notes"
                                      rows="2"
                                      placeholder="Notes (optional)"
                                      class="w-full text-sm bg-black/40 border border-gray-700 rounded-lg p-2 text-white"></textarea>
                            <button
                                class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-black font-semibold rounded-lg transition">
                                Upload Photos
                            </button>
                        </form>
                        {{-- Photos grid --}}
                        @if($booking->checkinInspection->photos->count())
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-5">
                                @foreach($booking->checkinInspection->photos as $photo)
                                    <div>
                                        <img src="{{ asset('storage/'.$photo->path) }}"
                                             class="rounded-lg h-28 w-full object-cover border border-gray-700">
                                        <p class="text-xs text-gray-400 mt-1">
                                            {{ $photo->type ?? '—' }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    {{-- END CODE JDID --}}

                    
              @else
            <form method="POST"
                action="{{ route('admin.inspection.store', $booking) }}"
                class="space-y-4">
                @csrf

                <input type="hidden" name="type" value="checkin">

                <!-- Mileage -->
                <div>
                    <label class="block text-xs text-gray-400 mb-1">
                        Mileage (km)
                    </label>
                    <input type="number"
                        name="mileage"
                        required
                        min="0"
                        class="w-full px-4 py-3 bg-black/40 border border-[#C89D66]/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[#C89D66]">
                </div>

                <!-- Fuel -->
                <div>
                    <label class="block text-xs text-gray-400 mb-1">
                        Fuel Level (%)
                    </label>
                    <input type="number"
                        name="fuel_level"
                        required
                        min="0"
                        max="100"
                        class="w-full px-4 py-3 bg-black/40 border border-[#C89D66]/30 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-[#C89D66]">
                </div>

                <!-- Damage notes -->
                <div>
                    <label class="block text-xs text-gray-400 mb-1">
                        Damage Notes (optional)
                    </label>
                    <textarea name="damage_notes"
                            rows="3"
                            placeholder="Scratches, dents, cracks..."
                            class="w-full px-4 py-3 bg-black/40 border border-[#C89D66]/30 rounded-xl text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#C89D66]"></textarea>
                </div>

                <!-- Submit -->
                <button
                    class="w-full px-6 py-3 bg-gradient-to-r from-[#C89D66] to-[#d4ab76] hover:from-[#d4ab76] hover:to-[#C89D66] text-black font-semibold rounded-xl transition-all shadow-lg shadow-[#C89D66]/20">
                    Save Check-in Inspection
                </button>
            </form>
            @endif

            </div>
